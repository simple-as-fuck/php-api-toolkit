<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final readonly class Params
{
    /**
     * @param non-empty-string $listeningUrl
     * @param Priority::* $priority
     * @param array<non-empty-string, non-empty-string> $attributes
     */
    public function __construct(
        public string $listeningUrl,
        public int $priority,
        public array $attributes
    ) {
    }
}
