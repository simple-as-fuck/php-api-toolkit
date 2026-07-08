<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use Psr\Http\Message\ResponseInterface;

final readonly class Response extends \SimpleAsFuck\ApiToolkit\Data\Common\Response
{
    /**
     * @param int $jsonEncodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     */
    public function withJson(
        int $jsonEncodeFlags = 0,
    ): ResponseJson {
        return new ResponseJson($this->withHeader('Content-Type', 'application/json'), $jsonEncodeFlags);
    }

    /**
     * @param int $jsonEncodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     */
    public function withJsonl(
        int $jsonEncodeFlags = 0,
    ): ResponseStream {
        return new ResponseStream($this->withHeader('Content-Type', 'application/jsonl'), $jsonEncodeFlags);
    }

    protected function clone(ResponseInterface $response): static
    {
        return new self($response);
    }
}
