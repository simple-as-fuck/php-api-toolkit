<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\DataObject\Common;

/**
 * https://datatracker.ietf.org/doc/html/rfc9457#name-the-problem-details-json-ob
 */
final class ProblemDetail
{
    /**
     * @param non-empty-string|null $type https://datatracker.ietf.org/doc/html/rfc9457#name-type
     * @param int|null $status https://datatracker.ietf.org/doc/html/rfc9457#name-status
     * @param non-empty-string|null $title https://datatracker.ietf.org/doc/html/rfc9457#name-title message for end user
     * @param non-empty-string|null $detail https://datatracker.ietf.org/doc/html/rfc9457#name-detail detail for end user
     * @param non-empty-string|null $instance https://datatracker.ietf.org/doc/html/rfc9457#name-instance
     */
    public function __construct(
        public readonly ?string $type,
        public readonly ?int $status,
        public readonly ?string $title,
        public readonly ?string $detail = null,
        public readonly ?string $instance = null
    ) {
    }
}
