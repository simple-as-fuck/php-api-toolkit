<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use GuzzleHttp\Psr7\HttpFactory;
use SimpleAsFuck\ApiToolkit\Model\Server\RequestRules;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\String\StringRule;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

final class Validator
{
    public static function make(Request $request): RequestRules
    {
        $psrFactory = new HttpFactory();
        $factory = new PsrHttpFactory($psrFactory, $psrFactory, $psrFactory, $psrFactory);
        $request = $factory->createRequest($request);
        return new RequestRules(new BadRequestException(), $request);
    }

    /**
     * @param non-empty-string $name
     * @return StringRule
     */
    public static function parameter(?string $value, string $name): StringRule
    {
        /** @var Validated<mixed> $value */
        $value = new Validated($value);
        return new StringRule(new BadRequestException(), new RuleChain(), $value, 'Url path parameter: '.$name);
    }
}
