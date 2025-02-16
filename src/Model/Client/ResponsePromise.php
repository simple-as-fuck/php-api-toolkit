<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use GuzzleHttp\Promise\PromiseInterface;

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
}
