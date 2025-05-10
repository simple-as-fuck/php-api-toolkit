<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final class Webhook
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $type
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly Params $params
    ) {
    }
}
