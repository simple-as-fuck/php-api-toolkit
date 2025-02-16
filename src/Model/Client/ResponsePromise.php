<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

final class ResponsePromise
{
    /**
     * @param non-empty-string $apiName
     */
    public function __construct(
        public readonly string $apiName,
        public readonly Request $request,
        public readonly PromiseInterface $promise,
    ) {
    }

    /**
     * @deprecated will be removed
     * @return non-empty-string
     */
    public function apiName(): string
    {
        return $this->apiName;
    }

    /**
     * @deprecated will be removed
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * @deprecated will be removed
     */
    public function wait(): Response
    {
        /** @var ResponseInterface $response */
        $response = $this->promise->wait();
        return new Response($this->request, $response);
    }
}
