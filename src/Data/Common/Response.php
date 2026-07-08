<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Common;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

abstract readonly class Response implements ResponseInterface
{
    public function __construct(
        private ResponseInterface $response,
    ) {
    }

    public function withProtocolVersion(string $version): static
    {
        return $this->clone($this->response->withProtocolVersion($version));
    }

    /**
     * @param string|array<string> $value
     */
    public function withHeader(string $name, $value): static
    {
        return $this->clone($this->response->withHeader($name, $value));
    }

    /**
     * @param string $name
     * @param string|array<string> $value
     */
    public function withAddedHeader(string $name, $value): static
    {
        return $this->clone($this->response->withAddedHeader($name, $value));
    }

    public function withoutHeader(string $name): static
    {
        return $this->clone($this->response->withoutHeader($name));
    }

    public function withBody(StreamInterface $body): static
    {
        return $this->clone($this->response->withBody($body));
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        return $this->clone($this->response->withStatus($code, $reasonPhrase));
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        /** @var array<string, array<string>> */
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * @return array<string>
     */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    abstract protected function clone(ResponseInterface $response): static;
}
