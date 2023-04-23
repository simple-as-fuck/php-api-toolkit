<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final class Result
{
    public function __construct(
        private bool $shopDispatching = false
    ) {
    }

    public function shopDispatching(): bool
    {
        return $this->shopDispatching;
    }
}
