<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use SimpleAsFuck\Validator\Factory\Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class BadRequestException extends Exception
{
    public function create(string $message): \Exception
    {
        return new BadRequestHttpException($message);
    }
}
