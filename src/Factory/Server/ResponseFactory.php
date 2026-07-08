<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Utils;
use Kayex\HttpCodes;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Result;
use SimpleAsFuck\ApiToolkit\Service\Transformation\NotNull;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Nullable;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\ResultTransformer;

final class ResponseFactory
{
    /**
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function make(int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        return new \GuzzleHttp\Psr7\Response($code, $headers);
    }

    /**
     * @todo 0.9 $body and $transformer change to not null
     * @template TBody
     * @param TBody|null $body will be encoded as application/json object
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeObject(mixed $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json';
        $response = self::make($code, $headers);

        return $response->withBody(Utils::streamFor(\json_encode(Nullable::toApi($body, $transformer), \JSON_THROW_ON_ERROR)));
    }

    /**
     * @template TBody
     * @param \Iterator<TBody> $body will be encoded as application/json array
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeArray(\Iterator $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json';
        $response = self::make($code, $headers);

        $start = true;
        return $response->withBody(new PumpStream(function () use ($body, $transformer, &$start): ?string {
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

            $item .= \json_encode(NotNull::toApi($body->current(), $transformer), \JSON_THROW_ON_ERROR);
            $body->next();
            if ($body->valid()) {
                $item .= ',';
            } else {
                $item .= ']';
            }

            return $item;
        }));
    }

    /**
     * @template TBody
     * @param \Iterator<array-key, TBody> $body will be encoded as application/json object to preserve keys as properties in response
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeArrayAssoc(\Iterator $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json';
        $response = self::make($code, $headers);

        $start = true;
        return $response->withBody(new PumpStream(function () use ($body, $transformer, &$start): ?string {
            $item = '';
            if ($start) {
                $start = false;
                if (! $body->valid()) {
                    return '{}';
                }
                $item = '{';
            }

            if (! $body->valid()) {
                return null;
            }

            $item .= \json_encode((string) $body->key(), \JSON_THROW_ON_ERROR) . ':' . \json_encode(NotNull::toApi($body->current(), $transformer), \JSON_THROW_ON_ERROR);
            $body->next();
            if ($body->valid()) {
                $item .= ',';
            } else {
                $item .= '}';
            }

            return $item;
        }));
    }

    /**
     * @template TBody
     * @param \Iterator<TBody> $body will be encoded as application/jsonl https://jsonlines.org/
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeStream(\Iterator $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/jsonl';
        $response = self::make($code, $headers);

        return $response->withBody(new PumpStream(static function () use ($body, $transformer): ?string {
            if (! $body->valid()) {
                return null;
            }

            $responseData = NotNull::toApi($body->current(), $transformer);
            $body->next();
            return \json_encode($responseData, \JSON_THROW_ON_ERROR) . "\n";
        }));
    }

    /**
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeWebhookResult(bool $stopDispatching = false, array $headers = []): ResponseInterface
    {
        return self::makeObject(new Result($stopDispatching), new ResultTransformer(), HttpCodes::HTTP_OK, $headers);
    }
}
