<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

final class Config
{
    /** @var non-empty-string */
    private string $baseUrl;
    /** @var non-empty-string|null */
    private ?string $bearerToken;
    private bool $verifyCerts;

    /**
     * @param non-empty-string $baseUrl
     * @param non-empty-string|null $bearerToken
     */
    public function __construct(string $baseUrl, ?string $bearerToken, bool $verifyCerts = true)
    {
        $this->baseUrl = $baseUrl;
        $this->bearerToken = $bearerToken;
        $this->verifyCerts = $verifyCerts;
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
}
