<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Client;

use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\Validator\Factory\Exception;

final class ParseResponseException extends Exception
{
    public function __construct(
        private Response $clientResponse
    ) {
    }

    public function create(string $message): \Exception
    {
        return new \SimpleAsFuck\ApiToolkit\Model\Client\ParseResponseException($this->clientResponse, $message);
    }
}
