<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;

abstract class Config
{
    /**
     * @return positive-int
     */
    abstract public function getMaxTries(): int;

    /**
     * @return positive-int delay in seconds
     */
    abstract public function getDelayBetweenTries(): int;

    abstract public function getDefaultOptions(): ArrayRule;

    /**
     * @return array<string>
     */
    public function getDefaultHeaders(): array
    {
        return [];
    }
}
