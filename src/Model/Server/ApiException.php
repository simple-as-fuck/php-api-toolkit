<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail;

class ApiException extends \RuntimeException
{
    /**
     * @param non-empty-string|null $message https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members available to server and client for logging or debugging purposes MUST contain only English message
     * @param ProblemDetail|int<100, 505> $problemDetail ProblemDetail | HTTP status
     * @param array<literal-string, mixed> $problemDetailExtensions https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members all values MUST be json serializable
     * @param non-empty-string|null $internalMessage available only to server for logging or debugging purposes MUST NOT leave server environment
     */
    public function __construct(
        ?string $message = null,
        private readonly ProblemDetail|int $problemDetail = HttpCodes::HTTP_INTERNAL_SERVER_ERROR,
        private readonly array $problemDetailExtensions = [],
        private readonly ?string $internalMessage = null,
        ?\Throwable $previous = null
    ) {
        if (is_int($problemDetail)) {
            $code = $problemDetail;
        } else {
            $code = $problemDetail->status ?? HttpCodes::HTTP_INTERNAL_SERVER_ERROR;
        }
        parent::__construct($message ?? '', $code, $previous);
    }

    public function getProblemDetail(): ?ProblemDetail
    {
        if (is_int($this->problemDetail)) {
            return null;
        }

        return $this->problemDetail;
    }

    /**
     * @return array<literal-string, mixed>
     */
    public function getProblemDetailExtensions(): array
    {
        return $this->problemDetailExtensions;
    }

    /**
     * @return non-empty-string|null
     */
    public function getInternalMessage(): ?string
    {
        return $this->internalMessage;
    }
}
