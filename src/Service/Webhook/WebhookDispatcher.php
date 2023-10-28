<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use GuzzleHttp\RequestOptions;

class WebhookDispatcher
{
    public function __construct(
        private Repository $repository,
        private Client $client,
    ) {
    }

    /**
     * @param non-empty-string $type
     * @param array<non-empty-string, non-empty-string> $attributes
     * @param array<non-empty-string, string|array<string>> $headers to apply for http request
     * @param array<RequestOptions::*, mixed> $options to apply for http request
     */
    public function dispatch(string $type, array $attributes = [], bool $synchronouslyFirstTry = false, array $headers = [], array $options = []): void
    {
        $webhooks = $this->repository->loadForDispatching($type, $attributes);
        if (count($webhooks) === 0) {
            return;
        }

        if ($synchronouslyFirstTry) {
            $this->client->callWebhooks($webhooks, 0, $headers, $options);
            return;
        }

        $this->client->dispatchWebhooks($webhooks, 0, 0, $headers, $options);
    }

    /**
     * @param positive-int $delayInSeconds
     * @param non-empty-string $type
     * @param array<non-empty-string, non-empty-string> $attributes
     * @param array<non-empty-string, string|array<string>> $headers to apply on http requests
     * @param array<RequestOptions::*, mixed> $options to apply on http requests
     */
    public function dispatchWithDelay(int $delayInSeconds, string $type, array $attributes = [], array $headers = [], array $options = []): void
    {
        $webhooks = $this->repository->loadForDispatching($type, $attributes);
        if (count($webhooks) === 0) {
            return;
        }

        $this->client->dispatchWebhooks($webhooks, $delayInSeconds, 0, $headers, $options);
    }
}
