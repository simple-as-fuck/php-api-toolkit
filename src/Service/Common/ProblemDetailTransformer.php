<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Common;

use SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

/**
 * @implements UserClassRule<ProblemDetail>
 * @implements Transformer<ProblemDetail>
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
     * @param ProblemDetail $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = [];
        if ($transformed->type !== null) {
            $responseData['type'] = $transformed->type;
        }
        if ($transformed->status !== null) {
            $responseData['status'] = $transformed->status;
        }
        if ($transformed->title !== null) {
            $responseData['title'] = $transformed->title;
        }
        if ($transformed->detail !== null) {
            $responseData['detail'] = $transformed->detail;
        }
        if ($transformed->instance !== null) {
            $responseData['instance'] = $transformed->instance;
        }

        return (object) $responseData;
    }
}
