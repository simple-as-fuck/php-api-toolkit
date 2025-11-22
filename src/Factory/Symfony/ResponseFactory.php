<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use GuzzleHttp\Utils;
use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\Service\Server\SpeedLimitService;
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
     * @deprecated use ResponseFactory::makeObject
     * @template TBody
     * @param TBody|null $body will be encoded as json
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeJson(mixed $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): Response
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
        return self::makeJsonStream($body, $transformer, $code, $headers);
    }

    /**
     * @deprecated use ResponseFactory::makeArray
     * @template TBody
     * @param iterable<TBody> $body will be encoded as json
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     * @param float $speedLimit speed limit in KB/s for sending response slow down, zero means no slow down (this is not precise but is something)
     */
    public static function makeJsonStream(iterable $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = [], float $speedLimit = 0): StreamedResponse
    {
        $headers['Content-Type'] = 'application/json';
        return new StreamedResponse(
            static function () use ($body, $transformer, $speedLimit) {
                $dataSeparator = '';

                echo '[';

                foreach ($body as $responseData) {
                    $batchSendingStartAt = \microtime(true);
                    echo $dataSeparator;

                    if ($transformer !== null) {
                        $responseData = $transformer->toApi($responseData);
                    }

                    /** @var non-empty-string $responseData */
                    $responseData = Utils::jsonEncode($responseData);
                    echo $responseData;

                    SpeedLimitService::slowdownDataSending($speedLimit, strlen($dataSeparator) + strlen($responseData), $batchSendingStartAt);
                    $dataSeparator = ',';
                }

                echo ']';
            },
            $code,
            $headers
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
     * @deprecated will be static
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public function makeWebhookResult(bool $stopDispatching = false, array $headers = []): Response
    {
        $factory = new HttpFoundationFactory();
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory::makeWebhookResult(
            $stopDispatching,
            $headers,
        ));
    }
}
