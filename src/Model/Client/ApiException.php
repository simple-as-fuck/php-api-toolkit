<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

final class ApiException extends \RuntimeException
{
    private ?Response $response;

    public function __construct(string $message = '', ?Response $response = null, \Throwable $previous = null)
    {
        parent::__construct($message, $response ? $response->getStatusCode() : 0, $previous);
        $this->response = $response;
    }

    public function response(): ?Response
    {
        return $this->response;
    }
}
