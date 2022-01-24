<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory;

use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\Validator\Factory\Exception;

final class ApiClientException extends Exception
{
    private ?Response $clientResponse;

    public function __construct(?Response $clientResponse)
    {
        $this->clientResponse = $clientResponse;
    }

    public function create(string $message): \Exception
    {
        return new \SimpleAsFuck\ApiToolkit\Model\Client\ApiException($message, $this->clientResponse);
    }
}
