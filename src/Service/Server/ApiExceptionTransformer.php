<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Data\Server\ApiException;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

/**
 * @implements Transformer<ApiException|\SimpleAsFuck\ApiToolkit\Model\Server\ApiException>
 */
class ApiExceptionTransformer implements Transformer
{
    /**
     * @param ApiException|\SimpleAsFuck\ApiToolkit\Model\Server\ApiException $transformed
     * @phpstan-ignore-next-line
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = [];
        if ($transformed->getMessage() !== '') {
            $responseData['message'] = $transformed->getMessage();
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->getProblemDetail()?->type !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['type'] = $transformed->getProblemDetail()->type;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->getProblemDetail()?->title !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['title'] = $transformed->getProblemDetail()->title;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->getProblemDetail()?->status !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['status'] = $transformed->getProblemDetail()->status;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->getProblemDetail()?->detail !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['detail'] = $transformed->getProblemDetail()->detail;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->getProblemDetail()?->instance !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['instance'] = $transformed->getProblemDetail()->instance;
        }

        /** @phpstan-ignore-next-line */
        $responseData = [...$responseData, ...((array) $transformed->getProblemDetailExtensions())];

        return (object) $responseData;
    }
}
