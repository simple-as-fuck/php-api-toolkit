<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Model\Server\ApiException;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @implements Transformer<ApiException|HttpException>
 */
class ApiExceptionTransformer implements Transformer
{
    /**
     * @param ApiException|HttpException $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = new \stdClass();
        $responseData->message = $transformed->getMessage();

        return $responseData;
    }
}
