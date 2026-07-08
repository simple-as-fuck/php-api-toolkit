<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use GuzzleHttp\Psr7\PumpStream;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Service\Transformation\NotNull;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

readonly class ResponseArray
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
     * @param \Iterator<TBody> $body will be encoded as application/json array
     * @param Transformer<TBody>|null $transformer
     */
    public function of(\Iterator $body, ?Transformer $transformer = null): ResponseInterface
    {
        $start = true;
        return $this->response->withBody(
            new PumpStream(function () use ($body, $transformer, &$start): ?string {
                $item = '';
                if ($start) {
                    $start = false;
                    if (! $body->valid()) {
                        return '[]';
                    }
                    $item = '[';
                }

                if (! $body->valid()) {
                    return null;
                }

                $item .= \json_encode(NotNull::toApi($body->current(), $transformer), $this->jsonEncodeFlags | \JSON_THROW_ON_ERROR);
                $body->next();
                if ($body->valid()) {
                    $item .= ',';
                } else {
                    $item .= ']';
                }

                return $item;
            }),
        );
    }

    /**
     * @template TBody
     * @param \Iterator<TBody> $body
     * @param Transformer<TBody> $transformer
     */
    public function ofObject(\Iterator $body, Transformer $transformer): ResponseInterface
    {
        return $this->of($body, $transformer);
    }
}
