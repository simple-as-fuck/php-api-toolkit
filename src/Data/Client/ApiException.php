<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Client;

use SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

class ApiException extends \RuntimeException
{
    /**
     * @param string $message for logging or debugging purposes MUST contain only English message
     * @param int $code https://datatracker.ietf.org/doc/html/rfc9457#name-status or HTTP status
     * @param ObjectRule|null $problemDetailExtensions https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members
     */
    public function __construct(
        string $message,
        int $code,
        private readonly Request $request,
        private readonly ?Response $response,
        private readonly ?ProblemDetail $problemDetail,
        private readonly ?ObjectRule $problemDetailExtensions,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function getProblemDetail(): ?ProblemDetail
    {
        return $this->problemDetail;
    }

    public function getProblemDetailExtensions(): ?ObjectRule
    {
        return $this->problemDetailExtensions;
    }
}
