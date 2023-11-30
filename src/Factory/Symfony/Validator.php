<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use GuzzleHttp\Psr7\HttpFactory;
use SimpleAsFuck\ApiToolkit\Model\Server\RequestRules;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\String\StringRule;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

final class Validator
{
    public static function make(Request $request, Exception $exception = new BadRequestException()): RequestRules
    {
        $psrFactory = new HttpFactory();
        $factory = new PsrHttpFactory($psrFactory, $psrFactory, $psrFactory, $psrFactory);
        $request = $factory->createRequest($request);
        return new RequestRules($exception, $request);
    }

    /**
     * @param non-empty-string $name
     */
    public static function parameter(?string $value, string $name, Exception $exception = new BadRequestException()): StringRule
    {
        /** @var Validated<mixed> $value */
        $value = new Validated($value);
        return new StringRule($exception, new RuleChain(), $value, 'Url path parameter: '.$name);
    }
}
