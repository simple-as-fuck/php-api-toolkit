<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use Psr\EventDispatcher\StoppableEventInterface;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Result;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

/**
 * @implements UserClassRule<Result&StoppableEventInterface>
 * @implements Transformer<Result&StoppableEventInterface>
 */
final class ResultTransformer implements UserClassRule, Transformer
{
    public function validate(ObjectRule $rule): Result&StoppableEventInterface
    {
        return new Result($rule->property('stopDispatching')->bool()->nullable() ?? false);
    }

    /**
     * @param Result&StoppableEventInterface $transformed
     */
    public function toApi($transformed): \stdClass
    {
        return (object) ['stopDispatching' => $transformed->isPropagationStopped()];
    }
}
