<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use SimpleAsFuck\ApiToolkit\Service\Server\UserQueryRule;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\ArrayRule\Key;
use SimpleAsFuck\Validator\Rule\ArrayRule\StringTypedKey;
use SimpleAsFuck\Validator\Rule\Object\ClassFromArray;

final class QueryRule
{
    /**
     * @param Validated<array<mixed>> $queryParams
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
        /** @var Validated<mixed> $validated */
        $validated = $this->queryParams;
        $ruleChain = new RuleChain();
        $valueName = 'Request query parameter: '.$key;
        return new StringTypedKey(
            $this->exceptionFactory,
            $ruleChain,
            $validated,
            $valueName,
            new Key($this->exceptionFactory, $ruleChain, $validated, $valueName, $key)
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
            new RuleChain(),
            $this->queryParams,
            'Request query',
            $this,
            $userQueryRule
        );
    }
}
