<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Http;

use Psr\Http\Message\MessageInterface;
use SimpleAsFuck\ApiToolkit\Service\Common\JsonService;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Rule\General\Rules;

/**
 * @deprecated use SimpleAsFuck\ApiToolkit\Service\Common\JsonService
 */
class MessageService
{
    /**
     * @deprecated use SimpleAsFuck\ApiToolkit\Service\Common\JsonService::parseJsonFromStream
     * @param non-empty-string $messageBodyName
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     */
    public static function parseJsonFromBody(
        Exception $exceptionFactory,
        MessageInterface $message,
        string $messageBodyName,
        bool $allowInvalidJson,
        int $jsonDecodeFlags = 0
    ): Rules {
        return JsonService::jsonDecode($message->getBody()->getContents(), $messageBodyName, $exceptionFactory, $allowInvalidJson, $jsonDecodeFlags);
    }
}
