<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\Model\Server\ApiException;
use SimpleAsFuck\Validator\Factory\Exception;

final class ApiValidationException extends Exception
{
    public function create(string $message): \Exception
    {
        return new ApiException($message, HttpCodes::HTTP_BAD_REQUEST);
    }
}
