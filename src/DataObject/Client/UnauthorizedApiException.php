<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\DataObject\Client;

use SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;

/** @phpstan-ignore-next-line class.extendsDeprecatedClass */
final class UnauthorizedApiException extends \SimpleAsFuck\ApiToolkit\Model\Client\UnauthorizedApiException
{
    /**
     * @param string $message for logging or debugging purposes MUST contain only English message
     * @param int $code https://datatracker.ietf.org/doc/html/rfc9457#name-status or HTTP status
     */
    public function __construct(
        string $message,
        int $code,
        private readonly Request $request,
        private readonly Response $response,
        private readonly ?ProblemDetail $problemDetail,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $problemDetail?->instance,
            $problemDetail?->type,
            $problemDetail?->title,
            $problemDetail?->detail,
            $request,
            $response,
            $previous
        );
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getProblemDetail(): ?ProblemDetail
    {
        return $this->problemDetail;
    }
}
