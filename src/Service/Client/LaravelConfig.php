<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;
use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;

final class LaravelConfig extends Config
{
    public function __construct(
        private readonly LaravelAdapter $laravelAdapter
    ) {
    }

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    public function getBaseUrl(string $apiName): string
    {
        return $this->laravelAdapter->get('services.'.$apiName.'.base_url')->string()->notEmpty()->notNull();
    }

    /**
     * @param non-empty-string $apiName
     */
    public function getDefaultOptions($apiName): ArrayRule
    {
        return $this->laravelAdapter->get('services.'.$apiName.'.default_options')->array();
    }

    /**
     * @param non-empty-string $apiName
     * @return array<string>
     */
    public function getDefaultHeaders(string $apiName): array
    {
        return $this->laravelAdapter->get('services.'.$apiName.'.default_headers')->array()->ofString()->nullable() ?? [];
    }

    /**
     * @param non-empty-string $apiName
     * @return non-empty-string
     */
    public function getDeprecatedHeader(string $apiName): string
    {
        return $this->laravelAdapter->get('services.'.$apiName.'.deprecated_header')->string()->notEmpty()->nullable() ?? parent::getDeprecatedHeader($apiName);
    }
}
