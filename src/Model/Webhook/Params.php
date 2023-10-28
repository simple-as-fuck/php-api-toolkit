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
        private string $listeningUrl,
        private int $priority,
        private array $attributes
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function listeningUrl(): string
    {
        return $this->listeningUrl;
    }

    /**
     * @return Priority::*
     */
    public function priority(): int
    {
        return $this->priority;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
