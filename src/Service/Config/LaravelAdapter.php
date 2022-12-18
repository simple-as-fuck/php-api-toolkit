<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Config;

use SimpleAsFuck\ApiToolkit\Model\Client\Config;
use SimpleAsFuck\ApiToolkit\Model\Server\Config as ServerConfig;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\General\Rules;

final class LaravelAdapter extends Repository
{
    public function __construct(
        private \Illuminate\Contracts\Config\Repository $repository
    ) {
    }

    public function getClientConfig(string $apiName): Config
    {
        return new Config(
            $this->getValue('services.'.$apiName.'.base_url')->string()->notEmpty()->notNull(),
            $this->getValue('services.'.$apiName.'.token')->string()->notEmpty()->nullable(),
            $this->getValue('services.'.$apiName.'.verify')->bool()->nullable() ?? true,
            $this->getValue('services.'.$apiName.'deprecated_header')->string()->notEmpty()->nullable() ?? 'Deprecated'
        );
    }

    public function getServerConfig(): ServerConfig
    {
        return new ServerConfig(
            $this->getValue('app.debug')->bool()->nullable() ?? false
        );
    }

    /**
     * @param non-empty-string $key
     */
    private function getValue(string $key): Rules
    {
        return Validator::make($this->repository->get($key), 'Config key '.$key);
    }
}
