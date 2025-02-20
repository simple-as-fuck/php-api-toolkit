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
     * @deprecated in 0.6 will be removed use $this->getDefaultHeaders
     * @param non-empty-string $apiName
     * @return non-empty-string|null
     */
    abstract public function getBearerToken(string $apiName): ?string;

    /**
     * @param non-empty-string $apiName
     * @return array<string>
     */
    public function getDefaultHeaders(string $apiName): array
    {
        /** @phpstan-ignore-next-line */
        $token = $this->getBearerToken($apiName);
        if ($token !== null) {
            return ['Authorization' => 'Bearer '.$token];
        }

        return [];
    }

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
