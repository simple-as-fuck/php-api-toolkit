<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;
use SimpleAsFuck\Validator\Factory\Validator;

abstract class WebhookClient
{
    public function __construct(
        private readonly Config $config,
        private readonly \GuzzleHttp\Client $client,
        private readonly ?LoggerInterface $logger,
    ) {
    }

    /**
     * @param iterable<Webhook> $webhooks
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     */
    final public function callWebhooks(
        iterable $webhooks,
        #[\SensitiveParameter] array $headers = [],
        #[\SensitiveParameter] array $options = [],
        int $tries = 0,
    ): void {
        $requestHeaders = $headers;
        $requestOptions = $options;

        $defaultHeaders = $this->config->getDefaultHeaders();
        foreach ($defaultHeaders as $defaultHeader => $value) {
            $defaultHeader = (string) $defaultHeader;
            if (! array_key_exists($defaultHeader, $headers)) {
                $requestHeaders[$defaultHeader] = $value;
            }
        }

        $defaultOptions = $this->config->getDefaultOptions();
        $defaultOptions->key(RequestOptions::TIMEOUT)->float()->min(0)->notNull(if: ($options[RequestOptions::TIMEOUT] ?? null) === null);
        foreach ($defaultOptions->notNull() as $key => $defaultOption) {
            if (! array_key_exists($key, $options)) {
                $requestOptions[$key] = $defaultOption;
            }
        }

        $requestHeaders['Content-Type'] = 'application/json';
        $nextTryWebhooks = [];

        foreach ($webhooks as $webhook) {
            if (count($nextTryWebhooks) !== 0) {
                $nextTryWebhooks[] = $webhook;
                continue;
            }

            try {
                $request = new Request(
                    'POST',
                    $webhook->params->listeningUrl,
                    $requestHeaders,
                    \json_encode((new WebhookTransformer())->toApi($webhook), \JSON_THROW_ON_ERROR)
                );
                $response = $this->client->send($request, $requestOptions);

                $callResult = Validator::json($response->getBody()->getContents(), 'Webhook response body', new UnexpectedValueException())
                    ->object()
                    ->class(new ResultTransformer())
                    ->notNull()
                ;

                if ($callResult->isPropagationStopped()) {
                    break;
                }
            } catch (\Throwable $exception) {
                $this->logger?->warning('Call webhook: "'.$webhook->id.'" fail message: "'.$exception->getMessage().'", webhook url: "'.$webhook->params->listeningUrl.'"', [
                    'webhookId' => $webhook->id,
                    'exception' => $exception
                ]);
                $nextTryWebhooks[] = $webhook;
            }
        }

        if (count($nextTryWebhooks) !== 0) {
            ++$tries;
            $maximumTries = $this->config->getMaxTries();
            if ($tries >= $maximumTries) {
                $webhookIdsWithoutRetry = implode(', ', array_map(static fn (Webhook $webhook): string => '"'.$webhook->id.'"', $nextTryWebhooks));
                $this->logger?->error('Call webhooks: '.$webhookIdsWithoutRetry.' fail without any retry, maximum tries: '.$maximumTries);
                return;
            }
            $this->dispatchWebhooks($nextTryWebhooks, $headers, $options, $this->config->getDelayBetweenTries(), $tries);
        }
    }

    /**
     * method SHOULD add iterable with webhooks into some queue for asynchronous or delayed calls
     *
     * while webhook iterable is ready for calls from queue, queue worker MUST call static::callWebhooks method
     *
     * @param iterable<Webhook> $webhooks
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @param int<0, max> $delayInSeconds
     */
    abstract public function dispatchWebhooks(iterable $webhooks, array $headers = [], array $options = [], int $delayInSeconds = 0, int $tries = 0): void;
}
