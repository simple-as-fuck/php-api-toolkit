<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\String\StringRule;
use SimpleAsFuck\Validator\Rule\Url\ParseUrl;

final class Request
{
    /** @var non-empty-string */
    private string $url;
    /** @var non-empty-string */
    private string $method;
    /** @var array<string, string|array<string>> */
    private array $headers;
    private ?StreamInterface $body;
    /** @var non-empty-string|null */
    private ?string $baseUrl;

    /**
     * @param non-empty-string $method
     * @param non-empty-string $url
     * @param array<mixed> $query
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(string $method, string $url, array $query = [], ?StreamInterface $body = null, array $headers = [])
    {
        if (count($query) !== 0) {
            $url = ParseUrl::make($url, [], [PHP_URL_QUERY, PHP_URL_FRAGMENT], 'Parameter $url')->notNull();
            $url .= '?'.\http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $this->method = $method;
        $this->url = ParseUrl::make($url, [], [PHP_URL_SCHEME, PHP_URL_USER, PHP_URL_PASS, PHP_URL_HOST, PHP_URL_PORT], 'Parameter $url')->notNull();
        $this->headers = $headers;
        $this->body = $body;
        $this->baseUrl = null;
    }

    /**
     * @param non-empty-string $baseUrl
     */
    public function withBaseUrl(string $baseUrl): self
    {
        $request = new self($this->method, $this->url, [], $this->body, $this->headers);
        $request->baseUrl = StringRule::make($baseUrl, 'Parameter $baseUrl')->parseHttpUrl([], [PHP_URL_QUERY, PHP_URL_FRAGMENT])->notNull();
        return $request;
    }

    /**
     * @template TData
     * @param TData $jsonData
     * @param Transformer<TData>|null $transformer
     */
    public function withJson($jsonData, ?Transformer $transformer): self
    {
        if ($transformer !== null) {
            $jsonData = $transformer->toApi($jsonData);
        }

        $headers = $this->headers;
        $headers['Content-Type'] = 'application/json';
        $stream = Utils::streamFor(\GuzzleHttp\Utils::jsonEncode($jsonData));
        $request = new self($this->method, $this->url, [], $stream, $headers);
        $request->baseUrl = $this->baseUrl;
        return $request;
    }

    public function hasBaseUrl(): bool
    {
        return $this->baseUrl !== null;
    }

    public function createPsr(RequestFactoryInterface $factory): RequestInterface
    {
        if ($this->baseUrl === null) {
            throw new \LogicException('Can not create psr request without baseUrl');
        }

        $request = $factory->createRequest($this->method, $this->baseUrl.$this->url);
        foreach ($this->headers as $name => $header) {
            $request = $request->withHeader($name, $header);
        }

        if ($this->body !== null) {
            $request = $request->withBody($this->body);
        }

        return $request;
    }
}
