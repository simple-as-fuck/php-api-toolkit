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
     * @param object{message?: non-empty-string|null, type?: non-empty-string|null, title?: non-empty-string|null, status?: int|null, detail?: non-empty-string|null, instance?: non-empty-string|null}|null $problemDetailExtensions https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members all properties MUST be json serializable
     * @param non-empty-string|null $internalMessage available only to server for logging or debugging purposes MUST NOT leave server environment
     */
    public function __construct(
        ?string $message = null,
        private readonly ProblemDetail|int $problemDetail = HttpCodes::HTTP_INTERNAL_SERVER_ERROR,
        private readonly ?object $problemDetailExtensions = null,
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

    public function getProblemDetailExtensions(): ?object
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
