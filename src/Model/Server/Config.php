<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

final class Config
{
    private bool $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function debug(): bool
    {
        return $this->debug;
    }
}
