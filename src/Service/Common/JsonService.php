<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Common;

use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\General\Rules;
use SimpleAsFuck\Validator\Rule\String\ParseJson;

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
        return Validator::make(
            ParseJson::make(
                $string,
                $stringName,
                $exceptionFactory,
                allowInvalidJson: $allowInvalidJson,
                jsonDecodeFlags: $jsonDecodeFlags,
            )
                ->nullable(),
            $stringName . ' json',
            $exceptionFactory,
        );
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
        return new class (
            Validator::jsonl($stream, $streamName, $exceptionFactory, allowInvalidJson: $allowInvalidJson, jsonDecodeFlags: $jsonDecodeFlags),
            $streamName,
            $exceptionFactory,
        ) extends \IteratorIterator {
            public function __construct(
                \Iterator $iterator,
                private readonly string $streamName,
                private readonly Exception $exceptionFactory,
            ) {
                parent::__construct($iterator);
            }
            public function current(): Rules
            {
                return Validator::make(
                    /** @phpstan-ignore-next-line method.nonObject */
                    $this->getInnerIterator()->current()->nullable(),
                    /** @phpstan-ignore-next-line binaryOp.invalid */
                    $this->streamName . ' value ' . $this->getInnerIterator()->key() . ' json',
                    $this->exceptionFactory,
                );
            }
        };
    }
}
