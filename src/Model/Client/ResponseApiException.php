<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

class ResponseApiException extends ApiException
{
    final public function __construct(
        string $message,
        Request $request,
        private Response $response,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $request, $response, $previous);
    }

    final public function response(): Response
    {
        return $this->response;
    }
}
