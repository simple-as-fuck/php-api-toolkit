<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

final class Config
{
    /**
     * @param non-empty-string $baseUrl
     * @param non-empty-string|null $bearerToken
     * @param non-empty-string $deprecatedHeader
     */
    public function __construct(
        private string $baseUrl,
        private ?string $bearerToken,
        private bool $verifyCerts = true,
        private string $deprecatedHeader = 'Deprecated'
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return non-empty-string|null
     */
    public function bearerToken(): ?string
    {
        return $this->bearerToken;
    }

    public function verifyCerts(): bool
    {
        return $this->verifyCerts;
    }

    /**
     * @return non-empty-string
     */
    public function deprecatedHeader(): string
    {
        return $this->deprecatedHeader;
    }
}
