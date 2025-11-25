<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Webhook;

use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\RuleChain;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\String\StringRule;

final readonly class AttributesRule
{
    /**
     * @param array<non-empty-string, non-empty-string> $attributes
     */
    public function __construct(
        private Exception $exception,
        private array $attributes,
    ) {
    }

    /**
     * @param non-empty-string $key
     */
    public function key(string $key): StringRule
    {
        return new StringRule(
            $this->exception,
            new RuleChain(),
            new Validated($this->attributes[$key] ?? null),
            'Webhook attribute value for key: ' . $key
        );
    }
}
