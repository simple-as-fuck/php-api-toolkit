<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;

final class LaravelConfig extends Config
{
    public function __construct(
        private readonly LaravelAdapter $laravelAdapter,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getDefaultHeaders(): array
    {
        return $this->laravelAdapter->get('webhook.dispatch.default_headers')->array()->ofString()->nullable() ?? [];
    }

    /**
     * @return positive-int
     */
    public function getMaxTries(): int
    {
        return $this->laravelAdapter->get('webhook.dispatch.max-tries')->int()->positive()->notNull();
    }

    /**
     * @return positive-int delay in seconds
     */
    public function getDelayBetweenTries(): int
    {
        return $this->laravelAdapter->get('webhook.dispatch.tries-delay')->int()->positive()->notNull();
    }
}
