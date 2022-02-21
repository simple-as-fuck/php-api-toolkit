<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

use SimpleAsFuck\ApiToolkit\Model\Client\Config;
use SimpleAsFuck\ApiToolkit\Model\Server\Config as ServerConfig;

abstract class Repository
{
    abstract public function getClientConfig(string $apiName): Config;

    public function getServerConfig(): ServerConfig
    {
        return new ServerConfig(false);
    }
}
