<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Webhook;

final class Webhook
{
    /** @var non-empty-string */
    private string $id;
    /** @var non-empty-string */
    private string $type;
    private Params $params;

    /**
     * @param non-empty-string $id
     * @param non-empty-string $type
     */
    public function __construct(
        string $id,
        string $type,
        Params $params
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->params = $params;
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
