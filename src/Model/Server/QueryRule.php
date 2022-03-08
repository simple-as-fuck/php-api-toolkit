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
        $ruleChain = new RuleChain();
        /** @phpstan-ignore-next-line */
        return new StringTypedKey($ruleChain, new Key($this->exceptionFactory, $ruleChain, $this->queryParams, 'Request query parameter: '.$key, $key));
    }
}
