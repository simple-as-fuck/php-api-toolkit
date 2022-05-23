<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

/**
 * @implements UserClassRule<Webhook>
 * @implements Transformer<Webhook>
 */
final class WebhookTransformer implements UserClassRule, Transformer
{
    public function validate(ObjectRule $rule): Webhook
    {
        return new Webhook(
            $rule->property('webhookId')->string()->notEmpty()->notNull(),
            $rule->property('type')->string()->notEmpty()->notNull(),
            $rule->property('params')->object()->class(new ParamsTransformer())->notNull()
        );
    }

    /**
     * @param Webhook $transformed
     */
    public function toApi($transformed): \stdClass
    {
        return (object) [
            'webhookId' => $transformed->id(),
            'type' => $transformed->type(),
            'params' => (new ParamsTransformer())->toApi($transformed->params())
        ];
    }
}
