<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Model\Server\ApiException;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

/**
 * @implements Transformer<ApiException>
 */
class ApiExceptionTransformer implements Transformer
{
    /**
     * @param ApiException $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = [];
        if ($transformed->getMessage() !== '') {
            $responseData['message'] = $transformed->getMessage();
        }
        if ($transformed->getType() !== null) {
            $responseData['type'] = $transformed->getType();
        }
        if ($transformed->getTitle() !== null) {
            $responseData['title'] = $transformed->getTitle();
        }
        $responseData['status'] = $transformed->getCode();
        if ($transformed->getDetail() !== null) {
            $responseData['detail'] = $transformed->getDetail();
        }
        if ($transformed->getInstance() !== null) {
            $responseData['instance'] = $transformed->getInstance();
        }

        $responseData = [...$responseData, ...$transformed->getExtensions()];

        return (object) $responseData;
    }
}
