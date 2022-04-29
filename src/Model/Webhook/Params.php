<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final class Params
{
    /** @var non-empty-string */
    private string $listeningUrl;
    /** @var Priority::* */
    private int $priority;
    /** @var array<string, string> */
    private array $attributes;

    /**
     * @param non-empty-string $listeningUrl
     * @param Priority::* $priority
     * @param array<string, string> $attributes
     */
    public function __construct(
        string $listeningUrl,
        int $priority,
        array $attributes
    ) {
        $this->listeningUrl = $listeningUrl;
        $this->priority = $priority;
        $this->attributes = $attributes;
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
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
