<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;

final class LaravelConfig extends Config
{
    public function __construct(
        private LaravelAdapter $laravelAdapter,
    ) {
    }

    /**
     * @return non-empty-string|null
     */
    public function getBearerToken(): ?string
    {
        return $this->laravelAdapter->get('webhook.dispatch.token')->string()->notEmpty()->nullable();
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
