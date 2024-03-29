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
        private string $apiName,
        private Request $request,
        private PromiseInterface $promise
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function apiName(): string
    {
        return $this->apiName;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function wait(): Response
    {
        /** @var ResponseInterface $response */
        $response = $this->promise->wait();
        return new Response($this->request, $response);
    }
}
