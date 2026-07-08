<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Nullable;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

final readonly class ResponseJson
{
    /**
     * @param int $jsonEncodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     */
    public function __construct(
        private ResponseInterface $response,
        private int $jsonEncodeFlags,
    ) {
    }

    /**
     * @template TBody
     * @param TBody|null $body will be encoded as application/json
     * @param Transformer<TBody>|null $transformer
     */
    public function nullable(mixed $body, ?Transformer $transformer = null): ResponseInterface
    {
        return $this->response->withBody(
            Utils::streamFor(
                \json_encode(
                    Nullable::toApi($body, $transformer),
                    $this->jsonEncodeFlags | \JSON_THROW_ON_ERROR,
                )
            )
        );
    }

    /**
     * @template TBody
     * @param TBody $body will be encoded as application/json object
     * @param Transformer<TBody> $transformer
     */
    public function object(mixed $body, Transformer $transformer): ResponseInterface
    {
        return $this->nullable($body, $transformer);
    }

    public function array(): ResponseArray
    {
        return new ResponseArray($this->response, $this->jsonEncodeFlags);
    }

    public function arrayAssoc(): ResponseArray
    {
        return new ResponseArrayAssoc($this->response, $this->jsonEncodeFlags);
    }
}
