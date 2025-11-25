<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use SimpleAsFuck\ApiToolkit\Service\Server\UserQueryRule;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\ArrayRule\Key;
use SimpleAsFuck\Validator\Rule\ArrayRule\StringTypedKey;
use SimpleAsFuck\Validator\Rule\Object\ClassFromArray;

final readonly class QueryRule
{
    /**
     * @param Validated<covariant array<mixed>> $queryParams
     */
    public function __construct(
        private Exception $exceptionFactory,
        private Validated $queryParams
    ) {
    }

    /**
     * @param non-empty-string $key
     */
    public function key(string $key): StringTypedKey
    {
        $ruleChain = new RuleChain();
        $valueName = 'Request query parameter: '.$key;
        return new StringTypedKey(
            $this->exceptionFactory,
            /** @phpstan-ignore-next-line */
            $ruleChain,
            $this->queryParams,
            $valueName,
            new Key(
                $this->exceptionFactory,
                /** @phpstan-ignore-next-line */
                $ruleChain,
                $this->queryParams,
                $valueName,
                $key
            )
        );
    }

    /**
     * @template TClass of object
     * @param UserQueryRule<TClass> $userQueryRule
     * @return ClassFromArray<QueryRule, TClass>
     */
    public function class(UserQueryRule $userQueryRule): ClassFromArray
    {
        return new ClassFromArray(
            $this->exceptionFactory,
            /** @phpstan-ignore-next-line */
            new RuleChain(),
            $this->queryParams,
            'Request query',
            $this,
            $userQueryRule
        );
    }
}
