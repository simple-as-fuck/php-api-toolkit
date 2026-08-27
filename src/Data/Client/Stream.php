<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Client;

/**
 * @todo 0.9 swith template position and make Tkey require
 * @template Tout
 * @template Tkey = int of array-key
 */
final readonly class Stream
{
    /**
     * @param \Iterator<Tkey, Tout> $iterator
     */
    public function __construct(
        private \Iterator $iterator,
    ) {
    }

    /**
     * @return \Iterator<Tkey, Tout>
     */
    public function notNull(): \Iterator
    {
        return $this->iterator;
    }
}
