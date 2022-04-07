<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\ArrayRule\Key;
use SimpleAsFuck\Validator\Rule\ArrayRule\StringTypedKey;

final class QueryRule
{
    private Exception $exceptionFactory;
    /** @var Validated<array<mixed>> */
    private Validated $queryParams;

    /**
     * @param Validated<array<mixed>> $queryParams
     */
    public function __construct(Exception $exceptionFactory, Validated $queryParams)
    {
        $this->exceptionFactory = $exceptionFactory;
        $this->queryParams = $queryParams;
    }

    /**
     * @param non-empty-string $key
     */
    public function key(string $key): StringTypedKey
    {
        /** @var Validated<mixed> $validated */
        $validated = $this->queryParams;
        $ruleChain = new RuleChain();
        return new StringTypedKey(
            $this->exceptionFactory,
            $ruleChain,
            $validated,
            'Request query parameter: '.$key,
            new Key($this->exceptionFactory, $ruleChain, $validated, '', '')
        );
    }
}
