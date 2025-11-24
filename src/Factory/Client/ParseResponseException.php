<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Client;

use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\Validator\Factory\Exception;

final class ParseResponseException extends Exception
{
    public function __construct(
        private readonly Request $request,
        private readonly Response $response
    ) {
    }

    /**
     * @param non-empty-string $message
     */
    public function create(string $message): \Exception
    {
        return new \SimpleAsFuck\ApiToolkit\Data\Client\ParseResponseException(
            $message,
            $this->response->getStatusCode(),
            $this->request,
            $this->response,
            null,
            null,
        );
    }
}
