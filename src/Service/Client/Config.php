<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

abstract class Config
{
    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    abstract public function getBaseUrl(string $apiName): string;

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string|null
     */
    abstract public function getBearerToken(string $apiName): ?string;

    /**
     * @param non-empty-string $apiName
     */
    public function getVerifyCerts(string $apiName): bool
    {
        return true;
    }

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    public function getDeprecatedHeader(string $apiName): string
    {
        return 'Deprecated';
    }
}
