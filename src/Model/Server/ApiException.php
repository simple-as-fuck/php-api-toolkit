<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use Kayex\HttpCodes;

class ApiException extends \RuntimeException
{
    /** @var int<100,505> */
    private int $httpCode;

    /**
     * @param int<100,505> $httpCode
     */
    public function __construct(string $message, int $httpCode = HttpCodes::HTTP_INTERNAL_SERVER_ERROR, \Throwable $previous = null)
    {
        parent::__construct($message, $httpCode, $previous);
        $this->httpCode = $httpCode;
    }

    /**
     * @return int<100,505>
     */
    public function getStatusCode(): int
    {
        return $this->httpCode;
    }
}
