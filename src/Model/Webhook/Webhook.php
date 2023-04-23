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
        private string $id,
        private string $type,
        private Params $params
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function type(): string
    {
        return $this->type;
    }

    public function params(): Params
    {
        return $this->params;
    }
}
