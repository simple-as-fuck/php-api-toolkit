<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Model\Webhook\Result;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

/**
 * @implements UserClassRule<Result>
 * @implements Transformer<Result>
 */
final class ResultTransformer implements UserClassRule, Transformer
{
    public function validate(ObjectRule $rule): Result
    {
        return new Result($rule->property('stopDispatching')->bool()->nullable() ?? false);
    }

    /**
     * @param Result $transformed
     */
    public function toApi($transformed): \stdClass
    {
        return (object) ['stopDispatching' => $transformed->shopDispatching()];
    }
}
