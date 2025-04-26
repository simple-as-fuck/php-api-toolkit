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

    /**
     * @deprecated use $this->listeningUrl
     * @return non-empty-string
     */
    public function listeningUrl(): string
    {
        return $this->listeningUrl;
    }

    /**
     * @deprecated use $this->priority
     * @return Priority::*
     */
    public function priority(): int
    {
        return $this->priority;
    }

    /**
     * @deprecated use $this->attributes
     * @return array<non-empty-string, non-empty-string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
