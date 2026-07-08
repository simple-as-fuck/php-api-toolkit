<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResponseFactory
{
    /**
     * @todo 0.9 $body and $transformer change to not null, you can use \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::make()->withJson()->nullable()
     * @template TBody
     * @param TBody|null $body will be encoded as application/json object
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeObject(mixed $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): Response
    {
        $factory = new HttpFoundationFactory();
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeObject($body, $transformer, $code, $headers));
    }

    /**
     * @template TBody
     * @param iterable<TBody> $body will be encoded as application/json array
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeArray(iterable $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): StreamedResponse
    {
        if (is_array($body)) {
            $body = new \ArrayIterator($body);
        } elseif (! $body instanceof \Iterator) {
            $body = new \IteratorIterator($body);
        }

        $factory = new HttpFoundationFactory();
        /** @var StreamedResponse */
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeArray($body, $transformer, $code, $headers), streamed: true);
    }

    /**
     * @deprecated you can use \SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::make()->withJson()->arrayAssoc()->of()
     * @template TBody
     * @param iterable<array-key, TBody> $body will be encoded as application/json object to preserve keys as properties in response
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeArrayAssoc(
        iterable $body,
        ?Transformer $transformer = null,
        int $code = HttpCodes::HTTP_OK,
        array $headers = [],
    ): StreamedResponse {
        if (is_array($body)) {
            $body = new \ArrayIterator($body);
        } elseif (! $body instanceof \Iterator) {
            $body = new \IteratorIterator($body);
        }

        $factory = new HttpFoundationFactory();
        /** @var StreamedResponse */
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeArrayAssoc($body, $transformer, $code, $headers), streamed: true);
    }

    /**
     * @template TBody
     * @param iterable<TBody> $body will be encoded as application/jsonl https://jsonlines.org/
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeStream(iterable $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): StreamedResponse
    {
        if (is_array($body)) {
            $body = new \ArrayIterator($body);
        } elseif (! $body instanceof \Iterator) {
            $body = new \IteratorIterator($body);
        }

        $factory = new HttpFoundationFactory();
        /** @var StreamedResponse */
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeStream($body, $transformer, $code, $headers), streamed: true);
    }

    /**
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeWebhookResult(bool $stopDispatching = false, array $headers = []): Response
    {
        $factory = new HttpFoundationFactory();
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeWebhookResult(
            $stopDispatching,
            $headers,
        ));
    }
}
