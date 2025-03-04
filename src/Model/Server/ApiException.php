<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use Kayex\HttpCodes;

class ApiException extends \RuntimeException
{
    /**
     * @param non-empty-string|null $message https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members available to server and client for logging or debugging purposes MUST contain only English message
     * @param int<100,505> $code HTTP status
     * @param non-empty-string|null $type https://datatracker.ietf.org/doc/html/rfc9457#name-type
     * @param non-empty-string|null $title https://datatracker.ietf.org/doc/html/rfc9457#name-title message for end user
     * @param non-empty-string|null $detail https://datatracker.ietf.org/doc/html/rfc9457#name-detail detail for end user
     * @param non-empty-string|null $instance https://datatracker.ietf.org/doc/html/rfc9457#name-instance
     * @param array<literal-string, mixed> $extensions https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members all values MUST be json serializable
     * @param non-empty-string|null $internalMessage available only to server for logging or debugging purposes MUST NOT leave server environment
     */
    public function __construct(
        ?string $message = null,
        int $code = HttpCodes::HTTP_INTERNAL_SERVER_ERROR,
        private readonly ?string $type = null,
        private readonly ?string $title = null,
        private readonly ?string $detail = null,
        private readonly ?string $instance = null,
        private readonly array $extensions = [],
        private readonly ?string $internalMessage = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message ?? '', $code, $previous);
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

    /**
     * @return non-empty-string|null
     */
    public function getInstance(): ?string
    {
        return $this->instance;
    }

    /**
     * @return array<literal-string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @return non-empty-string|null
     */
    public function getInternalMessage(): ?string
    {
        return $this->internalMessage;
    }

    /**
     * @deprecated use $this->getCode()
     * @return int<100,505>
     */
    public function getStatusCode(): int
    {
        /** @var int<100,505> */
        return $this->getCode();
    }
}
