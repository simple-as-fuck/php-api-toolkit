<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Http;

use Psr\Http\Message\MessageInterface;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\General\Rules;

class MessageService
{
    /**
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
        $contentRaw = $message->getBody()->getContents();
        $content = \json_decode($contentRaw, flags: $jsonDecodeFlags);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            if ($allowInvalidJson) {
                $content = null;
            } else {
                $truncated = strlen($contentRaw) > 200;
                $contentRaw = substr($contentRaw, 0, 200);
                throw $exceptionFactory->create($messageBodyName.' must be valid json, invalid content: \''.$contentRaw.'\''.($truncated ? ' (truncated)' : ''));
            }
        }

        return new Rules($exceptionFactory, $messageBodyName.': json', new Validated($content));
    }
}
