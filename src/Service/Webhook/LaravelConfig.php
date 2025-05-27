<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;
use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;

final class LaravelConfig extends Config
{
    public function __construct(
        private readonly LaravelAdapter $laravelAdapter,
    ) {
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

    public function getDefaultOptions(): ArrayRule
    {
        return $this->laravelAdapter->get('webhook.dispatch.default_options')->array();
    }

    /**
     * @return array<string>
     */
    public function getDefaultHeaders(): array
    {
        return $this->laravelAdapter->get('webhook.dispatch.default_headers')->array()->ofString()->nullable() ?? [];
    }
}
