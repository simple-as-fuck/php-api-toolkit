<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Client;

/**
 * @template Tout
 */
final readonly class Stream
{
    /**
     * @param \Iterator<int, Tout> $iterator
     */
    public function __construct(
        private \Iterator $iterator,
    ) {
    }

    /**
     * @return \Iterator<int, Tout>
     */
    public function notNull(): \Iterator
    {
        return $this->iterator;
    }
}
