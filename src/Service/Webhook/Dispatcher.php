<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

class Dispatcher
{
    public function __construct(
        private Repository $repository,
        private Client $client
    ) {
    }

    /**
     * @param non-empty-string $type
     * @param array<string, string> $attributes
     * @param array<non-empty-string, string|array<string>> $headers to apply for http request
     * @param array<non-empty-string, mixed> $options to apply for http request, see \GuzzleHttp\RequestOptions
     */
    public function dispatch(string $type, array $attributes, bool $synchronouslyFirstTry = false, array $headers = [], array $options = []): void
    {
        $webhooks = $this->repository->loadForDispatching($type, $attributes);

        if ($synchronouslyFirstTry) {
            $this->client->callWebhooks($webhooks, 0, $headers, $options);
            return;
        }

        $this->client->dispatchWebhooks($webhooks, 0, 0, $headers, $options);
    }

    /**
     * @param non-empty-string $type
     * @param array<string, string> $attributes
     * @param positive-int $delayInSeconds
     * @param array<non-empty-string, string|array<string>> $headers to apply on http requests
     * @param array<non-empty-string, mixed> $options to apply on http requests, see \GuzzleHttp\RequestOptions
     */
    public function dispatchWithDelay(string $type, array $attributes, int $delayInSeconds, array $headers = [], array $options = []): void
    {
        $webhooks = $this->repository->loadForDispatching($type, $attributes);

        $this->client->dispatchWebhooks($webhooks, $delayInSeconds, 0, $headers, $options);
    }
}
