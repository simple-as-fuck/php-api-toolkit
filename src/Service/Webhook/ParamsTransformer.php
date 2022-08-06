<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Rule\ArrayRule\TypedKey;
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
        $attributeType = static fn (TypedKey $key): string => $key->string()->notNull();
        $attributes = [];
        foreach ($rule->property('attributes')->array()->of($attributeType)->notNull() as $key => $attribute) {
            $attributes[(string) $key] = $attribute;
        }
        return new Params(
            $rule->property('listeningUrl')->string()->parseHttpUrl([PHP_URL_PATH])->notNull(),
            $rule->property('priority')->int()->in([Priority::HIGH, Priority::NORMAL, Priority::LOW])->notNull(),
            $attributes
        );
    }

    /**
     * @param Params $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $apiData = new \stdClass();
        $apiData->listeningUrl = $transformed->listeningUrl();
        $apiData->priority = $transformed->priority();
        $apiData->attributes = $transformed->attributes();

        return $apiData;
    }
}
