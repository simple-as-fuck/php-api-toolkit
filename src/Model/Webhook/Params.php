<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final class Params
{
    /**
     * @param non-empty-string $listeningUrl
     * @param Priority::* $priority
     * @param array<non-empty-string, non-empty-string> $attributes
     */
    public function __construct(
        public readonly string $listeningUrl,
        public readonly int $priority,
        public readonly array $attributes
    ) {
    }
}
