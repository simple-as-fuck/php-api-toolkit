<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Client;

use SimpleAsFuck\ApiToolkit\Data\Transformation\Iterator;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\General\Rule;
use SimpleAsFuck\Validator\Rule\General\Rules;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;
use SimpleAsFuck\Validator\Rule\String\ParseJson;

/**
 * @template TRule of Rules|ParseJson
 */
final readonly class StreamRules
{
    /**
     * @param \Iterator<int, TRule> $iterator
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
        return new Iterator($this->iterator, static fn (Rule $rule): mixed => $rule->nullable());
    }

    /**
     * @template TMapped
     * @param callable(TRule): TMapped $callable
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
        return $this->of(static fn (Rule $rules): object => $rules->object()->class($rule)->notNull());
    }

    /**
     * @return Stream<ObjectRule>
     */
    public function ofObject(): Stream
    {
        return $this->of(static fn (Rule $rule): object => $rule->object());
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    /**
     * @return TRule
     */
    public function fetch(): Rule
    {
        $rules = $this->iterator->current();
        $this->iterator->next();
        return $rules;
    }
}
