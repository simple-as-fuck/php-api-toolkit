<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Client;

use SimpleAsFuck\ApiToolkit\Data\Transformation\Iterator;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\General\Rules;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

final readonly class StreamRules
{
    /**
     * @param \Iterator<int, Rules> $iterator
     */
    public function __construct(
        private \Iterator $iterator,
    ) {
    }

    /**
     * @return \Iterator<int, mixed>
     */
    public function notNull(): \Iterator
    {
        return new Iterator($this->iterator, static fn (Rules $rules): mixed => $rules->nullable());
    }

    /**
     * @template TMapped
     * @param callable(Rules): TMapped $callable
     * @return Stream<TMapped>
     */
    public function of(callable $callable): Stream
    {
        return new Stream(new Iterator($this->iterator, $callable));
    }

    /**
     * @template TClass of object
     * @param UserClassRule<TClass> $rule
     * @return Stream<TClass>
     */
    public function ofClass(UserClassRule $rule): Stream
    {
        return $this->of(static fn (Rules $rules): object => $rules->object()->class($rule)->notNull());
    }

    /**
     * @return Stream<ObjectRule>
     */
    public function ofObject(): Stream
    {
        return $this->of(static fn (Rules $rules): object => $rules->object());
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function fetch(): Rules
    {
        $rules = $this->iterator->current();
        $this->iterator->next();
        return $rules;
    }
}
