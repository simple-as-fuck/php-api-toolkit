<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

abstract class Config
{
    /**
     * @return array<string>
     */
    public function getDefaultHeaders(): array
    {
        return [];
    }

    /**
     * @return positive-int
     */
    abstract public function getMaxTries(): int;

    /**
     * @return positive-int delay in seconds
     */
    abstract public function getDelayBetweenTries(): int;
}
