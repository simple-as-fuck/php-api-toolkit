<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

class ResponseApiException extends ApiException
{
    private Response $response;

    final public function __construct(Response $response, string $message = '', \Throwable $previous = null)
    {
        parent::__construct($message, $response, $previous);
        $this->response = $response;
    }

    final public function response(): Response
    {
        return $this->response;
    }
}
