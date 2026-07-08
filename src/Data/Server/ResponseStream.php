<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use GuzzleHttp\Psr7\PumpStream;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Service\Transformation\NotNull;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

final readonly class ResponseStream extends ResponseArray
{
    public function __construct(
        private ResponseInterface $response,
        private int $jsonEncodeFlags
    ) {
        parent::__construct($response, $jsonEncodeFlags);
    }

    /**
     * @template TBody
     * @param \Iterator<TBody> $body will be encoded as application/jsonl https://jsonlines.org/
     * @param Transformer<TBody>|null $transformer
     */
    public function of(\Iterator $body, ?Transformer $transformer = null): ResponseInterface
    {
        return $this->response->withBody(
            new PumpStream(function () use ($body, $transformer): ?string {
                if (! $body->valid()) {
                    return null;
                }

                $responseData = NotNull::toApi($body->current(), $transformer);
                $body->next();
                return \json_encode($responseData, $this->jsonEncodeFlags | \JSON_THROW_ON_ERROR) . "\n";
            })
        );
    }
}
