<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Symfony;

use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @implements Transformer<HttpExceptionInterface>
 */
class HttpExceptionTransformer implements Transformer
{
    /**
     * @param HttpExceptionInterface $transformed
     */
    public function toApi($transformed): \stdClass
    {
        return (object) [
            'message' => $transformed->getMessage(),
            'status' => $transformed->getStatusCode(),
        ];
    }
}
