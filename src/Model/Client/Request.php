<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use SimpleAsFuck\Validator\Rule\String\StringRule;
use SimpleAsFuck\Validator\Rule\Url\ParseUrl;

final class Request
{
    /** @var non-empty-string */
    private string $url;
    /** @var non-empty-string */
    private string $method;
    private MessageInterface $message;
    /** @var non-empty-string|null */
    private ?string $baseUrl;

    /**
     * @param non-empty-string $method
     * @param non-empty-string $url
     */
    public function __construct(string $method, string $url, MessageInterface $message)
    {
        $this->method = $method;
        /** @phpstan-ignore-next-line */
        $this->url = ParseUrl::make($url, [], [PHP_URL_SCHEME, PHP_URL_USER, PHP_URL_PASS, PHP_URL_HOST, PHP_URL_PORT], '$url')->notNull();
        $this->message = $message;
        $this->baseUrl = null;
    }

    /**
     * @param non-empty-string $baseUrl
     */
    public function withBaseUrl(string $baseUrl): self
    {
        $request = new self($this->method, $this->url, $this->message);
        $request->baseUrl = StringRule::make($baseUrl, '$baseUrl')->parseHttpUrl([], [PHP_URL_QUERY, PHP_URL_FRAGMENT])->notNull();
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
        foreach ($this->message->getHeaders() as $name => $header) {
            $request = $request->withHeader($name, $header);
        }

        return $request
            ->withProtocolVersion($this->message->getProtocolVersion())
            ->withBody($this->message->getBody())
        ;
    }
}
