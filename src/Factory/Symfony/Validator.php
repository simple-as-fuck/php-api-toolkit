<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use GuzzleHttp\Psr7\HttpFactory;
use SimpleAsFuck\ApiToolkit\Model\Server\RequestRules;
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
}
