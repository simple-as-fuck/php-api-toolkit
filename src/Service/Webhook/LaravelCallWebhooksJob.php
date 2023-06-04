<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use GuzzleHttp\RequestOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;

final class LaravelCallWebhooksJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param iterable<Webhook> $webhooks
     * @param array<string, string|array<string>> $callHeaders
     * @param array<RequestOptions::*, mixed> $callOptions
     */
    public function __construct(
        private iterable $webhooks,
        private int $webhooksTries,
        private array $callHeaders,
        private array $callOptions,
    ) {
    }

    public function handle(Client $client): void
    {
        $client->callWebhooks($this->webhooks, $this->webhooksTries, $this->callHeaders, $this->callOptions);
    }
}
