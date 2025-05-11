<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

abstract class Repository
{
    public function isDebug(): bool
    {
        return false;
    }
}
