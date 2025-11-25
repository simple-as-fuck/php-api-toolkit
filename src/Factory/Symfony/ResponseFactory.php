<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use GuzzleHttp\Utils;
use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResponseFactory
{
    /**
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
        $headers['Content-Type'] = 'application/json';
        return new StreamedResponse(
            static function () use ($body, $transformer) {
                $dataSeparator = '';

                echo '[';

                foreach ($body as $responseData) {
                    echo $dataSeparator;

                    if ($transformer !== null) {
                        $responseData = $transformer->toApi($responseData);
                    }

                    /** @var non-empty-string $responseData */
                    $responseData = Utils::jsonEncode($responseData);
                    echo $responseData;

                    $dataSeparator = ',';
                }

                echo ']';
            },
            $code,
            $headers,
        );
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
        $headers['Content-Type'] = 'application/jsonl';
        return new StreamedResponse(
            static function () use ($body, $transformer): void {
                foreach ($body as $responseData) {
                    if ($transformer !== null) {
                        $responseData = $transformer->toApi($responseData);
                    }

                    echo Utils::jsonEncode($responseData) . "\n";
                }
            },
            $code,
            $headers,
        );
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
