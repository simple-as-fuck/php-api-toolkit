<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

use Psr\EventDispatcher\StoppableEventInterface;

final readonly class Result implements StoppableEventInterface
{
    public function __construct(
        private bool $stopDispatching,
    ) {
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopDispatching;
    }
}
