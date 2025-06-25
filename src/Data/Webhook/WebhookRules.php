<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Webhook;

use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\Validator\Factory\Exception;

final class WebhookRules
{
    public function __construct(
        private readonly Exception $exception,
        private readonly Webhook $webhook,
    ) {
    }

    public function notNull(): Webhook
    {
        return $this->webhook;
    }

    public function attributes(): AttributesRule
    {
        return new AttributesRule($this->exception, $this->webhook->params->attributes);
    }
}
