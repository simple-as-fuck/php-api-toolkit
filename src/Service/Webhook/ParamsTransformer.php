<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\Custom\UserClassRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

/**
 * @implements Transformer<Params>
 * @implements UserClassRule<Params>
 */
final class ParamsTransformer implements Transformer, UserClassRule
{
    public function validate(ObjectRule $rule): Params
    {
        $attributes = [];
        foreach ($rule->property('attributes')->array()->ofObject()->nullable() ?? [] as $object) {
            $attributes[$object->property('key')->string()->notEmpty()->notNull()] = $object->property('value')->string()->notEmpty()->notNull();
        }
        return new Params(
            $rule->property('listeningUrl')->string()->httpUrl([PHP_URL_PATH])->notNull(),
            $rule->property('priority')->int()->in([Priority::HIGH, Priority::NORMAL, Priority::LOW])->notNull(),
            $attributes
        );
    }

    /**
     * @param Params $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $attributes = [];
        foreach ($transformed->attributes() as $key => $value) {
            $attributes[] = (object) [
                'key' => $key,
                'value' => $value,
            ];
        }

        return (object) [
            'listeningUrl' => $transformed->listeningUrl(),
            'priority' => $transformed->priority(),
            'attributes' => $attributes,
        ];
    }
}
