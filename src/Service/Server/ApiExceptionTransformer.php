<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Data\Server\ApiException;
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
        if ($transformed->getProblemDetail()?->type !== null) {
            $responseData['type'] = $transformed->getProblemDetail()->type;
        }
        if ($transformed->getProblemDetail()?->title !== null) {
            $responseData['title'] = $transformed->getProblemDetail()->title;
        }
        if ($transformed->getProblemDetail()?->status !== null) {
            $responseData['status'] = $transformed->getProblemDetail()->status;
        }
        if ($transformed->getProblemDetail()?->detail !== null) {
            $responseData['detail'] = $transformed->getProblemDetail()->detail;
        }
        if ($transformed->getProblemDetail()?->instance !== null) {
            $responseData['instance'] = $transformed->getProblemDetail()->instance;
        }

        $responseData = [...$responseData, ...((array) $transformed->getProblemDetailExtensions())];

        return (object) $responseData;
    }
}
