<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\DataObject\Client;

use SimpleAsFuck\ApiToolkit\DataObject\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponseApiException;

/** @phpstan-ignore-next-line class.extendsDeprecatedClass */
final class ServiceUnavailableApiException extends ResponseApiException
{
    public function getRequest(): Request
    {
        /** @phpstan-ignore-next-line */
        return $this->request();
    }

    public function getResponse(): Response
    {
        /** @phpstan-ignore-next-line */
        return $this->response();
    }

    /** @phpstan-ignore-next-line */
    public function getProblemDetail(): ?ProblemDetail
    {
        return new ProblemDetail(
            /** @phpstan-ignore-next-line */
            $this->getType(),
            $this->getCode(),
            /** @phpstan-ignore-next-line */
            $this->getTitle(),
            /** @phpstan-ignore-next-line */
            $this->getDetail(),
            /** @phpstan-ignore-next-line */
            $this->getInstance(),
        );
    }
}
