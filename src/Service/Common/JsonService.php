<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Common;

use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\General\Rules;

/**
 * @deprecated use SimpleAsFuck\Validator\Factory\Validator from simple-as-fuck/php-validator
 */
class JsonService
{
    /**
     * @deprecated use SimpleAsFuck\Validator\Factory\Validator::json from simple-as-fuck/php-validator
     * @param non-empty-string $stringName
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     */
    public static function jsonDecode(
        string $string,
        string $stringName = 'String',
        Exception $exceptionFactory = new UnexpectedValueException(),
        bool $allowInvalidJson = false,
        int $jsonDecodeFlags = 0,
    ): Rules {
        return Validator::json($string, $stringName, $exceptionFactory, $allowInvalidJson, $jsonDecodeFlags);
    }

    /**
     * https://jsonlines.org/
     * @deprecated use SimpleAsFuck\Validator\Factory\Validator::jsonl from simple-as-fuck/php-validator
     * @param non-empty-string $streamName
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @return \Iterator<int, Rules>
     */
    public static function jsonlDecode(
        StreamInterface $stream,
        string $streamName = 'Stream content',
        Exception $exceptionFactory = new UnexpectedValueException(),
        bool $allowInvalidJson = false,
        int $jsonDecodeFlags = 0,
    ): \Iterator {
        return Validator::jsonl($stream, $streamName, $exceptionFactory, $allowInvalidJson, $jsonDecodeFlags);
    }
}
