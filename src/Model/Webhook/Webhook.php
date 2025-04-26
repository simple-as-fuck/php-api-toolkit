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

    /**
     * @deprecated use $this->id
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @deprecated use $this->type
     * @return non-empty-string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @deprecated use $this->params
     */
    public function params(): Params
    {
        return $this->params;
    }
}
