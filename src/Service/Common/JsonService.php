<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Common;

use Psr\Http\Message\StreamInterface;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\General\Rules;

class JsonService
{
    /**
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
        $content = \json_decode($string, flags: $jsonDecodeFlags);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            if ($allowInvalidJson) {
                $content = null;
            } else {
                $truncated = strlen($string) > 200;
                $logString = $truncated ? substr($string, 0, 200) : $string;
                throw $exceptionFactory->create($stringName.' must be valid json, invalid content: \''.$logString.'\''.($truncated ? ' (truncated)' : ''));
            }
        }

        return new Rules($exceptionFactory, $stringName.' json', new Validated($content));
    }

    /**
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
        return new class ($stream, $streamName, $exceptionFactory, $allowInvalidJson, $jsonDecodeFlags) implements \Iterator {
            private int $lineNumber = 0;
            private string $buffer = '';
            private ?Rules $current = null;

            public function __construct(
                private readonly StreamInterface $stream,
                private readonly string $streamName,
                private readonly Exception $exceptionFactory,
                private readonly bool $allowInvalidJson,
                private readonly int $jsonDecodeFlags,
            ) {
                $this->fetch();
            }

            public function key(): ?int
            {
                if ($this->valid()) {
                    return $this->lineNumber ?? null;
                }

                return null;
            }

            public function current(): Rules
            {
                if ($this->valid()) {
                    return $this->current ?? throw $this->exceptionFactory->create($this->streamName . ' value ' . $this->lineNumber . ' is not valid stream');
                }

                throw $this->exceptionFactory->create($this->streamName . ' value ' . $this->lineNumber . ' is not valid stream');
            }

            public function next(): void
            {
                $this->fetch();
            }

            public function valid(): bool
            {
                return $this->current !== null || ! $this->stream->eof() || $this->buffer !== '';
            }

            public function rewind(): void
            {
                if ($this->lineNumber <= 1) {
                    return;
                }

                $this->stream->rewind();
                $this->lineNumber = 0;
                $this->buffer = '';
                $this->fetch();
            }

            private function fetch(): void
            {
                if (!$this->valid()) {
                    return;
                }

                $this->lineNumber++;

                while (!$this->stream->eof()) {
                    $this->buffer .= $this->stream->read(1024);
                    $endLine = strpos($this->buffer, "\n");
                    if ($endLine !== false) {
                        break;
                    }
                }

                if ($this->buffer === '') {
                    $this->current = null;
                    return;
                }

                $endLine = strpos($this->buffer, "\n");
                if ($endLine === false) {
                    $line = $this->buffer;
                    $this->buffer = '';
                } else {
                    $line = substr($this->buffer, 0, $endLine);
                    $this->buffer = substr($this->buffer, $endLine + 1);
                }

                $this->current = JsonService::jsonDecode(
                    $line,
                    $this->streamName . ' value ' . $this->lineNumber,
                    $this->exceptionFactory,
                    $this->allowInvalidJson,
                    $this->jsonDecodeFlags,
                );
            }
        };
    }
}
