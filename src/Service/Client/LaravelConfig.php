<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use Illuminate\Contracts\Config\Repository;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\General\Rules;

final class LaravelConfig extends Config
{
    public function __construct(
        private Repository $repository
    ) {
    }

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    public function getBaseUrl(string $apiName): string
    {
        return $this->getValue('services.'.$apiName.'.base_url')->string()->notEmpty()->notNull();
    }

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string|null
     */
    public function getBearerToken(string $apiName): ?string
    {
        return $this->getValue('services.'.$apiName.'.token')->string()->notEmpty()->nullable();
    }

    /**
     * @param non-empty-string $apiName
     */
    public function getVerifyCerts(string $apiName): bool
    {
        return $this->getValue('services.'.$apiName.'.verify')->bool()->nullable() ?? parent::getVerifyCerts($apiName);
    }

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    public function getDeprecatedHeader(string $apiName): string
    {
        return $this->getValue('services.'.$apiName.'deprecated_header')->string()->notEmpty()->nullable() ?? parent::getDeprecatedHeader($apiName);
    }

    /**
     * @param non-empty-string $key
     */
    private function getValue(string $key): Rules
    {
        return Validator::make($this->repository->get($key), 'Config key '.$key);
    }
}
