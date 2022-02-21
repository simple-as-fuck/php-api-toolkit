<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use Psr\Http\Message\ServerRequestInterface;
use SimpleAsFuck\ApiToolkit\Model\Server\RequestRules;

final class Validator
{
    public static function make(ServerRequestInterface $request): RequestRules
    {
        return new RequestRules(new ApiValidationException(), $request);
    }
}
