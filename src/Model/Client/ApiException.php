<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

class ApiException extends \RuntimeException
{
    /**
     * @param string $message for logging or debugging purposes MUST contain only English message
     * @param int $code https://datatracker.ietf.org/doc/html/rfc9457#name-status or HTTP status or 0 if nothing is available
     * @param non-empty-string|null $instance https://datatracker.ietf.org/doc/html/rfc9457#name-instance
     * @param non-empty-string|null $type https://datatracker.ietf.org/doc/html/rfc9457#name-type, if not available look at message
     * @param non-empty-string|null $title https://datatracker.ietf.org/doc/html/rfc9457#name-title message for end user
     * @param non-empty-string|null $detail https://datatracker.ietf.org/doc/html/rfc9457#name-detail detail for end user
     */
    public function __construct(
        string $message,
        int $code,
        private readonly ?string $instance,
        private readonly ?string $type,
        private readonly ?string $title,
        private readonly ?string $detail,
        private readonly Request $request,
        private readonly ?Response $response,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return non-empty-string|null
     */
    public function getInstance(): ?string
    {
        return $this->instance;
    }

    /**
     * @return non-empty-string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return non-empty-string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return non-empty-string|null
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function response(): ?Response
    {
        return $this->response;
    }
}
