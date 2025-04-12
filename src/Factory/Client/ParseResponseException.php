<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Client;

use SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail;
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

    public function create(string $message): \Exception
    {
        return new \SimpleAsFuck\ApiToolkit\DataObject\Client\ParseResponseException(
            $message,
            $this->response->getStatusCode(),
            $this->request,
            $this->response,
            new ProblemDetail(
                null,
                null,
                null,
                null,
                $this->request->url(),
            ),
        );
    }
}
