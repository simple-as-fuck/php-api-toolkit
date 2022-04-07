<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\ArrayRule\Collection;
use SimpleAsFuck\Validator\Rule\ArrayRule\TypedKey;
use SimpleAsFuck\Validator\Rule\String\StringRule;

final class HeaderRule
{
    private Exception $exceptionFactory;
    /** @var Validated<array<array<string>>> */
    private Validated $validated;

    /**
     * @param Validated<array<array<string>>> $validated
     */
    public function __construct(Exception $exceptionFactory, Validated $validated)
    {
        $this->exceptionFactory = $exceptionFactory;
        $this->validated = $validated;
    }

    /**
     * @param non-empty-string $key
     */
    public function key(string $key): StringRule
    {
        $value = $this->validated->value();
        $value = $value[$key] ?? [];
        $value = array_pop($value);
        /** @var mixed $value */
        return new StringRule($this->exceptionFactory, new RuleChain(), new Validated($value), 'Request header: '.$key);
    }

    /**
     * @template TMapped
     * @param non-empty-string $key
     * @param callable(StringRule): TMapped $callable
     * @return Collection<TMapped>
     */
    public function keyOf(string $key, callable $callable): Collection
    {
        $value = $this->validated->value();
        $values = $value[$key] ?? null;
        /** @var mixed $values */
        return new Collection(
            $this->exceptionFactory,
            new RuleChain(),
            new Validated($values),
            'Request header: '.$key,
            fn (TypedKey $index) => $callable($index->string())
        );
    }
}
