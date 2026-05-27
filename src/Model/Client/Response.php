<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\ApiToolkit\Data\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\StreamRules;
use SimpleAsFuck\ApiToolkit\Factory\Client\ParseResponseException;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\String\ParseJson;

final readonly class Response implements ResponseInterface
{
    public function __construct(
        private Request $request,
        private ResponseInterface $response
    ) {
    }

    public function withProtocolVersion(string $version): self
    {
        return new self($this->request, $this->response->withProtocolVersion($version));
    }

    /**
     * @param string|array<string> $value
     */
    public function withHeader(string $name, $value): self
    {
        return new self($this->request, $this->response->withHeader($name, $value));
    }

    /**
     * @param string $name
     * @param string|array<string> $value
     */
    public function withAddedHeader(string $name, $value): self
    {
        return new self($this->request, $this->response->withAddedHeader($name, $value));
    }

    public function withoutHeader(string $name): self
    {
        return new self($this->request, $this->response->withoutHeader($name));
    }

    public function withBody(StreamInterface $body): self
    {
        return new self($this->request, $this->response->withBody($body));
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
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

    /**
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function getJson(
        bool $allowInvalidJson = false,
        bool $emptyStringAsNull = false,
        int $jsonDecodeFlags = 0,
    ): ParseJson {
        return ParseJson::make(
            $this->response->getBody()->getContents(),
            'Response body',
            new ParseResponseException($this->request, $this),
            allowInvalidJson: $allowInvalidJson,
            emptyStringAsNull: $emptyStringAsNull,
            jsonDecodeFlags: $jsonDecodeFlags,
        )
            ->cache()
        ;
    }

    /**
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @return StreamRules<ParseJson>
     * @throws ApiException
     */
    public function getJsonl(
        bool $allowInvalidJson = false,
        bool $emptyStringAsNull = false,
        int $jsonDecodeFlags = 0,
    ): StreamRules {
        return new StreamRules(
            Validator::jsonl(
                $this->response->getBody(),
                'Response body',
                new ParseResponseException($this->request, $this),
                allowInvalidJson: $allowInvalidJson,
                emptyStringAsNull: $emptyStringAsNull,
                jsonDecodeFlags: $jsonDecodeFlags,
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
