<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\Validator\Factory\Validator;

abstract class Client
{
    public function __construct(
        private Config $config,
        private \GuzzleHttp\Client $client,
        private ?LoggerInterface $logger,
    ) {
    }

    /**
     * @param iterable<Webhook> $webhooks
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     */
    final public function callWebhooks(iterable $webhooks, int $tries, array $headers, array $options): void
    {
        $requestHeaders = $headers;
        $requestOptions = $options;

        $token = $this->config->getBearerToken();
        if (! array_key_exists('Authorization', $requestHeaders) && $token !== null) {
            $requestHeaders['Authorization'] = 'Bearer '.$token;
        }

        $requestOptions[RequestOptions::HEADERS] = $requestHeaders;
        $nextTryWebhooks = [];

        foreach ($webhooks as $webhook) {
            if (count($nextTryWebhooks) !== 0) {
                $nextTryWebhooks[] = $webhook;
                continue;
            }

            try {
                $requestOptions[RequestOptions::JSON] = (new WebhookTransformer())->toApi($webhook);

                $response = $this->client->request('POST', $webhook->params()->listeningUrl(), $requestOptions);
                $callResult = Validator::make(Utils::jsonDecode($response->getBody()->getContents()), 'Webhook response body')
                    ->object()->class(new ResultTransformer())
                    ->notNull()
                ;

                if ($callResult->stopDispatching()) {
                    break;
                }
            } catch (\Throwable $exception) {
                $this->logger?->warning('Call webhook: "'.$webhook->id().'" fail message: "'.$exception->getMessage().'", webhook url: "'.$webhook->params()->listeningUrl().'"', [
                    'webhookId' => $webhook->id(),
                    'exception' => $exception
                ]);
                $nextTryWebhooks[] = $webhook;
            }
        }

        if (count($nextTryWebhooks) !== 0) {
            ++$tries;
            if ($tries >= $this->config->getMaxTries()) {
                return;
            }
            $this->dispatchWebhooks($nextTryWebhooks, $this->config->getDelayBetweenTries(), $tries, $headers, $options);
        }
    }

    /**
     * method SHOULD add iterable with webhooks into some queue for asynchronous or delayed calls
     *
     * while webhook iterable is ready for calls from queue, queue worker MUST call static::callWebhooks method
     *
     * @param iterable<Webhook> $webhooks
     * @param int<0, max> $delayInSeconds
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     */
    abstract public function dispatchWebhooks(iterable $webhooks, int $delayInSeconds, int $tries, array $headers, array $options): void;
}
