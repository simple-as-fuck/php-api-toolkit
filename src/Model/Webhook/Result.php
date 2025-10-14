<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

use Psr\EventDispatcher\StoppableEventInterface;

final class Result implements StoppableEventInterface
{
    public function __construct(
        /** @deprecated will be private use PRS-14 interface $this::isPropagationStopped() */
        public readonly bool $stopDispatching,
    ) {
    }

    public function isPropagationStopped(): bool
    {
        /** @phpstan-ignore-next-line property.deprecated */
        return $this->stopDispatching;
    }
}
