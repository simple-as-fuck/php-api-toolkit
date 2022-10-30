<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Collection;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\Validator\Factory\Validator;

final class LaravelMysqlRepository extends Repository
{
    private ConnectionResolverInterface $connectionResolver;
    private \Illuminate\Contracts\Config\Repository $config;

    public function __construct(
        ConnectionResolverInterface $connectionResolver,
        \Illuminate\Contracts\Config\Repository $config
    ) {
        $this->connectionResolver = $connectionResolver;
        $this->config = $config;
    }

    /**
     * @param non-empty-string $type
     * @param array<string, string> $attributes
     * @return iterable<Webhook>
     */
    public function loadForDispatching(string $type, array $attributes): iterable
    {
        $connection = $this->getLaravelConnection();

        $webhookMatches = [];
        $webhookIds = $this->getWebhooksForAttributes($connection, $attributes);
        foreach ($webhookIds as $webhookId) {
            $requiredAttributes = $this->getWebhookRequiredAttributes($connection, $webhookId);
            if ($this->haveAttributesRequiredAttributes($requiredAttributes, $attributes)) {
                $webhookMatches[$webhookId] = count($requiredAttributes);
            }
        }

        foreach ($connection->table('WebhookWithoutAttribute')->get() as $webhookMap) {
            /** @var \stdClass $webhookMap */
            if (array_key_exists($webhookMap->WebhookId, $webhookMatches)) {
                continue;
            }

            $webhookMatches[$webhookMap->WebhookId] = 0;
        }

        $webhooks = [];
        foreach ($webhookMatches as $webhookId => $matchCount) {
            /** @var \stdClass|null $webhook */
            $webhook = $connection->table('Webhook')->where('id', '=', $webhookId)->where('type', '=', $type)->first();
            if ($webhook === null) {
                continue;
            }

            $webhooks[] = new Webhook($webhook->id, $webhook->type, new Params($webhook->listeningUrl, $webhook->priority, $attributes));
        }

        usort($webhooks, static function (Webhook $a, Webhook $b) use ($webhookMatches): int {
            $diff = $b->params()->priority() - $a->params()->priority();
            if ($diff === 0) {
                $diff = $webhookMatches[$b->id()] - $webhookMatches[$a->id()];
            }
            return $diff;
        });

        return $webhooks;
    }

    /**
     * @param non-empty-string $type
     * @return Webhook|null null if unique webhook already exists
     */
    public function save(string $type, Params $params): ?Webhook
    {
        $connection = $this->getLaravelConnection();

        $webhookAttributeIds = [];
        foreach ($params->attributes() as $key => $value) {
            $webhookAttributeId = $connection->table('WebhookAttribute')
                ->where('key', '=', $key)
                ->where('value', '=', $value)
                ->value('id')
            ;
            if ($webhookAttributeId === null) {
                $webhookAttributeId = $connection->table('WebhookAttribute')->insertGetId([
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            $webhookAttributeIds[] = $webhookAttributeId;
            unset($webhookAttributeId);
        }

        $connection->statement('set autocommit=0');
        $connection->statement('lock tables Webhook write');
        try {
            $existingWebhook = $connection->table('Webhook')
                ->where('type', '=', $type)
                ->where('listeningUrl', '=', $params->listeningUrl())
                ->first()
            ;
            if ($existingWebhook !== null) {
                return null;
            }

            $connection->beginTransaction();
            try {
                $webhookId = $connection->table('Webhook')->insertGetId([
                    'type' => $type,
                    'listeningUrl' => $params->listeningUrl(),
                    'priority' => $params->priority(),
                ]);

                if (count($webhookAttributeIds) === 0) {
                    $connection->table('WebhookWithoutAttribute')->insert(['WebhookId' => $webhookId]);
                } else {
                    $connection->statement('lock tables WebhookRequiredAttribute write');
                    foreach ($webhookAttributeIds as $webhookAttributeId) {
                        $connection->table('WebhookRequiredAttribute')->insert([
                            'WebhookId' => $webhookId,
                            'WebhookAttributeId' => $webhookAttributeId,
                        ]);
                    }
                }

                $connection->commit();
            } catch (\Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }
        } finally {
            $connection->statement('unlock tables');
        }

        return new Webhook((string) $webhookId, $type, $params);
    }

    public function delete(string $webhookId): void
    {
        $connection = $this->getLaravelConnection();

        $connection->beginTransaction();
        try {
            $connection->table('WebhookRequiredAttribute')->where('WebhookId', '=', $webhookId)->delete();
            $connection->table('WebhookWithoutAttribute')->where('WebhookId', '=', $webhookId)->delete();
            $connection->table('Webhook')->where('id', '=', $webhookId)->delete();

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array<string, string> $attributes
     * @return array<int>
     */
    private function getWebhooksForAttributes(ConnectionInterface $connection, array $attributes): array
    {
        $dispatchedWebhookIds = [];
        foreach ($attributes as $key => $value) {
            $attributeIds = $connection->table('WebhookAttribute')
                ->where('key', '=', $key)
                ->where('value', '=', $value)
                ->get()
                ->map(static fn ($row): int => ((object) $row)->id)
            ;
            foreach ($attributeIds as $attributeId) {
                /** @var Collection<int, int> $webhookIds */
                $webhookIds = $connection->table('WebhookRequiredAttribute')
                    ->where('WebhookAttributeId', '=', $attributeId)
                    ->get()
                    ->map(static fn ($row): int => ((object) $row)->WebhookId)
                ;

                foreach ($webhookIds as $webhookId) {
                    if (! in_array($webhookId, $dispatchedWebhookIds, true)) {
                        $dispatchedWebhookIds[] = $webhookId;
                    }
                }
            }
        }

        return $dispatchedWebhookIds;
    }

    /**
     * @return array<string, string>
     */
    private function getWebhookRequiredAttributes(ConnectionInterface $connection, int $webhookId): array
    {
        $attributes = [];
        foreach ($connection->table('WebhookRequiredAttribute')->where('WebhookId', '=', $webhookId)->get() as $requiredAttribute) {
            /** @var \stdClass $requiredAttribute */
            /** @var \stdClass|null $storedAttribute */
            $storedAttribute = $connection->table('WebhookAttribute')->where('id', '=', $requiredAttribute->WebhookAttributeId)->first();
            if ($storedAttribute !== null) {
                $attributes[$storedAttribute->key] = $storedAttribute->value;
            }
        }

        /** @var array<string, string> */
        return $attributes;
    }

    /**
     * @param array<string, string> $requiredAttributes
     * @param array<string, string> $attributes
     */
    private function haveAttributesRequiredAttributes(array $requiredAttributes, array $attributes): bool
    {
        if (count($requiredAttributes) > count($attributes)) {
            return false;
        }

        foreach ($requiredAttributes as $key => $value) {
            if (array_key_exists($key, $attributes) && $value === $attributes[$key]) {
                continue;
            }

            return false;
        }

        return true;
    }

    private function getLaravelConnection(): ConnectionInterface
    {
        return $this->connectionResolver->connection(
            Validator::make(
                $this->config->get('webhook.repository.database-connection'),
                'Config key: webhook.repository.database-connection'
            )
            ->string()->notEmpty()->nullable()
        );
    }
}
