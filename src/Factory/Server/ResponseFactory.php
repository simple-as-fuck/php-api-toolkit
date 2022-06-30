<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Server;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Utils;
use Kayex\HttpCodes;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Service\Server\SpeedLimitService;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

final class ResponseFactory
{
    /**
     * @template TBody
     * @param TBody|null $body
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeJson($body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): ResponseInterface
    {
        $factory = new HttpFactory();
        $response = self::makeResponse($factory, $code, $headers);

        if ($body !== null) {
            if ($transformer !== null) {
                $body = $transformer->toApi($body);
            }
        }
        return $response->withBody($factory->createStream(Utils::jsonEncode($body)));
    }

    /**
     * @template TBody
     * @param \Iterator<TBody> $body
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     * @param float $speedLimit speed limit in KB/s for sending response slow down, zero means no slow down (this is not precise but is something)
     */
    public static function makeJsonStream(\Iterator $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = [], float $speedLimit = 0): ResponseInterface
    {
        $factory = new HttpFactory();
        $response = self::makeResponse($factory, $code, $headers);

        $start = true;
        $previousItemTime = \microtime(true);
        return $response->withBody(new PumpStream(function (int $size) use ($body, $transformer, $speedLimit, &$start, &$previousItemTime): ?string {
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

            $responseData = $body->current();
            if ($transformer !== null) {
                $responseData = $transformer->toApi($responseData);
            }

            $item .= Utils::jsonEncode($responseData);
            $body->next();
            if ($body->valid()) {
                $item .= ',';
                SpeedLimitService::slowdownDataSending($speedLimit, strlen($item), $previousItemTime);
            } else {
                $item .= ']';
            }

            $previousItemTime = \microtime(true);
            return $item;
        }));
    }

    /**
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    private static function makeResponse(ResponseFactoryInterface $factory, int $code, array $headers): ResponseInterface
    {
        $response = $factory->createResponse($code);

        foreach ($headers as $name => $header) {
            $response = $response->withHeader($name, $header);
        }

        return $response;
    }
}
