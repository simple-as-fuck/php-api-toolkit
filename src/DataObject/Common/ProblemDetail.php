<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\DataObject\Common;

/**
 * https://datatracker.ietf.org/doc/html/rfc9457#name-the-problem-details-json-ob
 */
final readonly class ProblemDetail
{
    /**
     * @param non-empty-string|null $type https://datatracker.ietf.org/doc/html/rfc9457#name-type
     * @param int|null $status https://datatracker.ietf.org/doc/html/rfc9457#name-status
     * @param non-empty-string|null $title https://datatracker.ietf.org/doc/html/rfc9457#name-title message for end user
     * @param non-empty-string|null $detail https://datatracker.ietf.org/doc/html/rfc9457#name-detail detail for end user
     * @param non-empty-string|null $instance https://datatracker.ietf.org/doc/html/rfc9457#name-instance
     */
    public function __construct(
        public ?string $type,
        public ?int $status,
        public ?string $title,
        public ?string $detail = null,
        public ?string $instance = null
    ) {
    }
}
