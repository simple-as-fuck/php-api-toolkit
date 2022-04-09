<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use Psr\Http\Message\ServerRequestInterface;
use SimpleAsFuck\ApiToolkit\Model\Server\RequestRules;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\String\StringRule;

final class Validator
{
    public static function make(ServerRequestInterface $request): RequestRules
    {
        return new RequestRules(new ApiValidationException(), $request);
    }

    /**
     * @param non-empty-string $name
     * @return StringRule
     */
    public static function parameter(?string $value, string $name): StringRule
    {
        /** @var Validated<mixed> $value */
        $value = new Validated($value);
        return new StringRule(new ApiValidationException(), new RuleChain(), $value, 'Url path parameter: '.$name);
    }
}
