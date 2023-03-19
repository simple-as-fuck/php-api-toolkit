<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private Request $request,
        private ?Response $response = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $response?->getStatusCode() ?? 0, $previous);
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function response(): ?Response
    {
        return $this->response;
    }
}
