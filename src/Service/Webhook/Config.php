<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

abstract class Config
{
    /**
     * @return non-empty-string|null
     */
    abstract public function getBearerToken(): ?string;

    /**
     * @return positive-int
     */
    abstract public function getMaxTries(): int;

    /**
     * @return positive-int delay in seconds
     */
    abstract public function getDelayBetweenTries(): int;
}
