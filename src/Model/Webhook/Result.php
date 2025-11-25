<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

use Psr\EventDispatcher\StoppableEventInterface;

final class Result implements StoppableEventInterface
{
    public function __construct(
        private readonly bool $stopDispatching,
    ) {
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopDispatching;
    }
}
