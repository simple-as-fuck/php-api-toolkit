<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Factory\Symfony;

use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

final class Response
{
    /**
     * @template TBody
     * @param TBody|null $body
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     */
    public static function makeJson($body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = []): \Symfony\Component\HttpFoundation\Response
    {
        $factory = new HttpFoundationFactory();
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\Response::makeJson($body, $transformer, $code, $headers));
    }

    /**
     * @template TBody
     * @param \Iterator<TBody> $body
     * @param Transformer<TBody>|null $transformer
     * @param int<100,505> $code
     * @param array<non-empty-string, string|array<string>> $headers
     * @param float $speedLimit speed limit in KB/s for sending response slow down, zero means no slow down (this is not precise but is something)
     */
    public static function makeJsonStream(\Iterator $body, ?Transformer $transformer = null, int $code = HttpCodes::HTTP_OK, array $headers = [], float $speedLimit = 0): \Symfony\Component\HttpFoundation\Response
    {
        $factory = new HttpFoundationFactory();
        return $factory->createResponse(\SimpleAsFuck\ApiToolkit\Factory\Server\Response::makeJsonStream($body, $transformer, $code, $headers, $speedLimit), true);
    }
}
