<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\ApiToolkit\Factory\Client\ParseResponseException;
use SimpleAsFuck\ApiToolkit\Service\Http\MessageService;
use SimpleAsFuck\Validator\Rule\General\Rules;

final class Response implements ResponseInterface
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @param string $version
     */
    public function withProtocolVersion($version): self
    {
        return new self($this->response->withProtocolVersion($version));
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    public function withHeader($name, $value): self
    {
        return new self($this->response->withHeader($name, $value));
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    public function withAddedHeader($name, $value): self
    {
        return new self($this->response->withAddedHeader($name, $value));
    }

    /**
     * @param string $name
     */
    public function withoutHeader($name): self
    {
        return new self($this->response->withoutHeader($name));
    }

    public function withBody(StreamInterface $body): self
    {
        return new self($this->response->withBody($body));
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        return new self($this->response->withStatus($code, $reasonPhrase));
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
        return $this->response->getHeaders();
    }

    /**
     * @param string $name
     */
    public function hasHeader($name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * @param string $name
     * @return array<string>
     */
    public function getHeader($name): array
    {
        return $this->response->getHeader($name);
    }

    /**
     * @param string $name
     */
    public function getHeaderLine($name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * @throws ApiException
     */
    public function getJson(bool $allowInvalidJson = false): Rules
    {
        return MessageService::parseJsonFromBody(new ParseResponseException($this), $this->response, 'Response body', $allowInvalidJson);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }
}
