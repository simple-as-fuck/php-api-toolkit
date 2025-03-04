<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

class ResponseApiException extends ApiException
{
    /**
     * @param string $message for logging or debugging purposes MUST contain only English message
     * @param int $code https://datatracker.ietf.org/doc/html/rfc9457#name-status or HTTP status
     * @param non-empty-string|null $instance https://datatracker.ietf.org/doc/html/rfc9457#name-instance
     * @param non-empty-string|null $type https://datatracker.ietf.org/doc/html/rfc9457#name-type, if not available look at message
     * @param non-empty-string|null $title https://datatracker.ietf.org/doc/html/rfc9457#name-title message for end user
     * @param non-empty-string|null $detail https://datatracker.ietf.org/doc/html/rfc9457#name-detail detail for end user
     */
    final public function __construct(
        string $message,
        int $code,
        ?string $instance,
        ?string $type,
        ?string $title,
        ?string $detail,
        Request $request,
        private readonly Response $response,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $instance, $type, $title, $detail, $request, $response, $previous);
    }

    final public function response(): Response
    {
        return $this->response;
    }
}
