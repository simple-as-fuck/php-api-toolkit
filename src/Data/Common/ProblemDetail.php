<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Common;

/**
 * https://datatracker.ietf.org/doc/html/rfc9457#name-the-problem-details-json-ob
 * @phpstan-ignore-next-line
 */
final readonly class ProblemDetail extends \SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail
{
    /**
     * @param non-empty-string|null $type https://datatracker.ietf.org/doc/html/rfc9457#name-type
     * @param int|null $status https://datatracker.ietf.org/doc/html/rfc9457#name-status
     * @param non-empty-string|null $title https://datatracker.ietf.org/doc/html/rfc9457#name-title message for end user
     * @param non-empty-string|null $detail https://datatracker.ietf.org/doc/html/rfc9457#name-detail detail for end user
     * @param non-empty-string|null $instance https://datatracker.ietf.org/doc/html/rfc9457#name-instance
     */
    public function __construct(
        ?string $type,
        ?int $status,
        ?string $title,
        ?string $detail = null,
        ?string $instance = null
    ) {
        /** @phpstan-ignore-next-line */
        parent::__construct(
            $type,
            $status,
            $title,
            $detail,
            $instance,
        );
    }
}
