<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Transformation;

/**
 * @template Tin
 * @template Tout
 * @implements \Iterator<array-key, Tout>
 */
final class Iterator implements \Iterator
{
    /**
     * @param \Iterator<array-key, Tin> $iterator
     * @param callable(Tin): Tout $callable
     */
    public function __construct(
        private readonly \Iterator $iterator,
        private readonly mixed $callable,
    ) {
    }

    /**
     * @return Tout
     */
    public function current(): mixed
    {
        return ($this->callable)($this->iterator->current());
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
