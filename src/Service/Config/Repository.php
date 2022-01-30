<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

use SimpleAsFuck\ApiToolkit\Model\Client\Config;

abstract class Repository
{
    abstract public function getClientConfig(string $apiName): Config;
}
