<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

use SimpleAsFuck\ApiToolkit\Model\Client\Config;
use SimpleAsFuck\ApiToolkit\Model\Server\Config as ServerConfig;
use SimpleAsFuck\Validator\Factory\Validator;

final class LaravelAdapter extends Repository
{
    private \Illuminate\Contracts\Config\Repository $repository;

    public function __construct(\Illuminate\Contracts\Config\Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getClientConfig(string $apiName): Config
    {
        return new Config(
            Validator::make($this->repository->get('services.'.$apiName.'.base_url'))->string()->notEmpty()->notNull(),
            Validator::make($this->repository->get('services.'.$apiName.'.token'))->string()->notEmpty()->nullable(),
            Validator::make($this->repository->get('services.'.$apiName.'.verify'))->bool()->nullable() ?? true,
        );
    }

    public function getServerConfig(): ServerConfig
    {
        return new ServerConfig(
            Validator::make($this->repository->get('app.debug'))->bool()->nullable() ?? false
        );
    }
}
