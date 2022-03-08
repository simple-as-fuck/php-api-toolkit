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
     */
    public static function parseJsonFromBody(Exception $exceptionFactory, MessageInterface $message, string $messageBodyName, bool $allowInvalidJson): Rules
    {
        $content = $message->getBody()->getContents();
        $content = \json_decode($content);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            if ($allowInvalidJson) {
                $content = null;
            } else {
                throw $exceptionFactory->create($messageBodyName.' must be valid json');
            }
        }

        return new Rules($exceptionFactory, $messageBodyName.': json', new Validated($content));
    }
}
