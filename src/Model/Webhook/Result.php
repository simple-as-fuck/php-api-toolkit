<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final class Result
{
    public function __construct(
        private bool $stopDispatching = false,
    ) {
    }

    public function stopDispatching(): bool
    {
        return $this->stopDispatching;
    }
}
