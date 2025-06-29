<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;

final class LaravelClient extends WebhookClient
{
    public function __construct(
        Config $config,
        \GuzzleHttp\Client $httpClient,
        ?LoggerInterface $logger,
        private readonly Dispatcher $dispatcher,
        private readonly LaravelAdapter $laravelAdapter,
    ) {
        parent::__construct($config, $httpClient, $logger);
    }

    public function dispatchWebhooks(iterable $webhooks, array $headers = [], array $options = [], int $delayInSeconds = 0, int $tries = 0): void
    {
        $job = new LaravelCallWebhooksJob($webhooks, $tries, $headers, $options);
        $job->onConnection($this->laravelAdapter->get('webhook.dispatch.queue-connection')->string()->notEmpty()->nullable());
        $job->onQueue($this->laravelAdapter->get('webhook.dispatch.queue-name')->string()->notEmpty()->nullable());
        $job->delay($delayInSeconds);

        $this->dispatcher->dispatch($job);
    }
}
