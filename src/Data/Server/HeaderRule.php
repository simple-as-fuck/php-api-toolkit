<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\ArrayRule\Collection;
use SimpleAsFuck\Validator\Rule\ArrayRule\TypedKey;
use SimpleAsFuck\Validator\Rule\String\RegexMatch;
use SimpleAsFuck\Validator\Rule\String\StringRule;

final readonly class HeaderRule
{
    /**
     * @param Validated<covariant array<array<string>>> $validated
     */
    public function __construct(
        private Exception $exceptionFactory,
        private Validated $validated,
    ) {
    }

    /**
     * @param non-empty-string $key
     */
    public function key(string $key, bool $caseSensitive = false, bool $sensitiveValue = false): StringRule
    {
        $values = $this->headerValues($caseSensitive);
        $values = $values[$caseSensitive ? $key : strtolower($key)] ?? [];
        $value = array_pop($values);
        return new StringRule(
            $this->exceptionFactory,
            new RuleChain(),
            new Validated($value),
            'Request header: '.$key,
            $sensitiveValue,
        );
    }

    /**
     * @template TMapped
     * @param non-empty-string $key
     * @param callable(StringRule): TMapped $callable
     * @return Collection<TMapped>
     */
    public function keyOf(string $key, callable $callable, bool $caseSensitive = false, bool $sensitiveValues = false): Collection
    {
        $values = $this->headerValues($caseSensitive);
        $values = $values[$caseSensitive ? $key : strtolower($key)] ?? null;
        return new Collection(
            $this->exceptionFactory,
            /** @phpstan-ignore-next-line */
            new RuleChain(),
            new Validated($values),
            'Request header: '.$key,
            static fn (TypedKey $index) => $callable($index->string($sensitiveValues))
        );
    }

    /**
     * method will return rule with regex match for value of http standard authorization header
     *
     * @param non-empty-string $type
     */
    public function authorization(string $type): RegexMatch
    {
        return $this
            ->key('Authorization', sensitiveValue: true)
            ->parseRegex('/^' . preg_quote($type, '/') . ' (?P<value>.+)$/')
            ->match('value')
        ;
    }

    /**
     * @return array<array<string>>|null
     */
    private function headerValues(bool $caseSensitive): ?array
    {
        $values = $this->validated->value;
        if ($caseSensitive) {
            return $values;
        }

        return $values !== null ? array_change_key_case($values, CASE_LOWER) : $values;
    }
}
