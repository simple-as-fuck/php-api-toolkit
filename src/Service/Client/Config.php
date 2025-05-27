<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;

abstract class Config
{
    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    abstract public function getBaseUrl(string $apiName): string;

    /**
     * @param non-empty-string $apiName
     */
    abstract public function getDefaultOptions(string $apiName): ArrayRule;

    /**
     * @param non-empty-string $apiName
     * @return array<string>
     */
    public function getDefaultHeaders(string $apiName): array
    {
        return [];
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
