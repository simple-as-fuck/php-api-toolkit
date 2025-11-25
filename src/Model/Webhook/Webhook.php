<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final readonly class Webhook
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $type
     */
    public function __construct(
        public string $id,
        public string $type,
        public Params $params
    ) {
    }
}
