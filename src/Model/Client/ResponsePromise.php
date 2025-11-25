<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use GuzzleHttp\Promise\PromiseInterface;

final readonly class ResponsePromise
{
    /**
     * @param non-empty-string $apiName
     */
    public function __construct(
        public string $apiName,
        public Request $request,
        public PromiseInterface $promise,
    ) {
    }
}
