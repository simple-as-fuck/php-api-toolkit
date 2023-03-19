<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

use SimpleAsFuck\ApiToolkit\Model\Server\Config as ServerConfig;

abstract class Repository
{
    public function getServerConfig(): ServerConfig
    {
        return new ServerConfig(false);
    }
}
