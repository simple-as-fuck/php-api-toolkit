<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Common;

use SimpleAsFuck\ApiToolkit\Data\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

/**
 * @implements UserClassRule<ProblemDetail>
 * @implements Transformer<ProblemDetail|\SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail>
 */
class ProblemDetailTransformer implements UserClassRule, Transformer
{
    public function validate(ObjectRule $rule): ProblemDetail
    {
        return new ProblemDetail(
            $rule->property('type')->string()->notEmpty()->nullable(true),
            $rule->property('status')->int()->nullable(true),
            $rule->property('title')->string()->notEmpty()->nullable(true),
            $rule->property('detail')->string()->notEmpty()->nullable(true),
            $rule->property('instance')->string()->notEmpty()->nullable(true),
        );
    }

    /**
     * @param ProblemDetail|\SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail $transformed
     * @phpstan-ignore-next-line
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = [];
        /** @phpstan-ignore-next-line */
        if ($transformed->type !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['type'] = $transformed->type;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->status !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['status'] = $transformed->status;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->title !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['title'] = $transformed->title;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->detail !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['detail'] = $transformed->detail;
        }
        /** @phpstan-ignore-next-line */
        if ($transformed->instance !== null) {
            /** @phpstan-ignore-next-line */
            $responseData['instance'] = $transformed->instance;
        }

        return (object) $responseData;
    }
}
