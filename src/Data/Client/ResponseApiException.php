<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Client;

use SimpleAsFuck\ApiToolkit\Data\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

class ResponseApiException extends ApiException
{
    /**
     * @param string $message for logging or debugging purposes MUST contain only English message
     * @param int $code https://datatracker.ietf.org/doc/html/rfc9457#name-status or HTTP status
     * @param ObjectRule|null $problemDetailExtensions https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members
     */
    public function __construct(
        string $message,
        int $code,
        Request $request,
        Response $response,
        /** @phpstan-ignore-next-line */
        ProblemDetail|\SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail|null $problemDetail,
        ?ObjectRule $problemDetailExtensions,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $request,
            $response,
            $problemDetail,
            $problemDetailExtensions,
            $previous
        );
    }

    public function getResponse(): Response
    {
        return parent::getResponse() ?? throw new \LogicException();
    }
}
