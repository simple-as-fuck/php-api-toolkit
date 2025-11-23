<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\ApiToolkit\Data\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\StreamRules;
use SimpleAsFuck\ApiToolkit\Factory\Client\ParseResponseException;
use SimpleAsFuck\ApiToolkit\Service\Common\JsonService;
use SimpleAsFuck\Validator\Rule\General\Rules;

final class Response implements ResponseInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly ResponseInterface $response
    ) {
    }

    /**
     * @param string $version
     */
    public function withProtocolVersion($version): self
    {
        return new self($this->request, $this->response->withProtocolVersion($version));
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    public function withHeader($name, $value): self
    {
        return new self($this->request, $this->response->withHeader($name, $value));
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    public function withAddedHeader($name, $value): self
    {
        return new self($this->request, $this->response->withAddedHeader($name, $value));
    }

    /**
     * @param string $name
     */
    public function withoutHeader($name): self
    {
        return new self($this->request, $this->response->withoutHeader($name));
    }

    public function withBody(StreamInterface $body): self
    {
        return new self($this->request, $this->response->withBody($body));
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        return new self($this->request, $this->response->withStatus($code, $reasonPhrase));
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
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function getJson(bool $allowInvalidJson = false, int $jsonDecodeFlags = 0): Rules
    {
        return JsonService::jsonDecode(
            $this->response->getBody()->getContents(),
            'Response body',
            new ParseResponseException($this->request, $this),
            $allowInvalidJson,
            $jsonDecodeFlags
        );
    }

    /**
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function getJsonl(bool $allowInvalidJson = false, int $jsonDecodeFlags = 0): StreamRules
    {
        return new StreamRules(
            JsonService::jsonlDecode(
                $this->response->getBody(),
                'Response body',
                new ParseResponseException($this->request, $this),
                $allowInvalidJson,
                $jsonDecodeFlags,
            ),
        );
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
