<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use Kayex\HttpCodes;
use SimpleAsFuck\Validator\Factory\Exception;

final class ApiValidationException extends Exception
{
    public function create(string $message): \Exception
    {
        return new \SimpleAsFuck\ApiToolkit\Model\Server\ApiException($message, HttpCodes::HTTP_BAD_REQUEST);
    }
}
