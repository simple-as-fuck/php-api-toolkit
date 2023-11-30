<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use Psr\Http\Message\ServerRequestInterface;
use SimpleAsFuck\ApiToolkit\Model\Server\RequestRules;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\String\StringRule;

final class Validator
{
    public static function make(ServerRequestInterface $request, Exception $exception = new ApiValidationException()): RequestRules
    {
        return new RequestRules($exception, $request);
    }

    /**
     * @param non-empty-string $name
     */
    public static function parameter(?string $value, string $name, Exception $exception = new ApiValidationException()): StringRule
    {
        /** @var Validated<mixed> $value */
        $value = new Validated($value);
        return new StringRule($exception, new RuleChain(), $value, 'Url path parameter: '.$name);
    }
}
