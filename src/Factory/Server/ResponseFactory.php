<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use Kayex\HttpCodes;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Data\Server\Response;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Result;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\ResultTransformer;

final class ResponseFactory
{
    /**
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function make(int $code = HttpCodes::HTTP_OK, array $headers = []): Response
    {
        return new Response(new \GuzzleHttp\Psr7\Response($code, $headers));
    }

    /**
     * @todo 0.9 $body and $transformer change to not null, you can use self::make()->withJson()->nullable()
     * @template TBody
     * @param TBody|null $body will be encoded as application/json object
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeObject(mixed $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        return self::make($code, $headers)->withJson()->nullable($body, $transformer);
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
        return self::make($code, $headers)->withJson()->array()->of($body, $transformer);
    }

    /**
     * @deprecated you can use self::make()->withJson()->arrayAssoc()->of()
     * @template TBody
     * @param \Iterator<array-key, TBody> $body will be encoded as application/json object to preserve keys as properties in response
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeArrayAssoc(\Iterator $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        return self::make($code, $headers)->withJson()->arrayAssoc()->of($body, $transformer);
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
        return self::make($code, $headers)->withJsonl()->of($body, $transformer);
    }

    /**
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeWebhookResult(bool $stopDispatching = false, array $headers = []): ResponseInterface
    {
        return self::makeObject(new Result($stopDispatching), new ResultTransformer(), HttpCodes::HTTP_OK, $headers);
    }
}
