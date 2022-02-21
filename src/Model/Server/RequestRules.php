<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Server;

use Psr\Http\Message\ServerRequestInterface;
use SimpleAsFuck\ApiToolkit\Service\Http\MessageService;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\General\Rules;

final class RequestRules
{
    private Exception $exceptionFactory;
    private ServerRequestInterface $request;

    public function __construct(Exception $exceptionFactory, ServerRequestInterface $request)
    {
        $this->exceptionFactory = $exceptionFactory;
        $this->request = $request;
    }

    public function query(): QueryRule
    {
        /** @phpstan-ignore-next-line */
        return new QueryRule($this->exceptionFactory, new Validated($this->request->getQueryParams()));
    }

    public function json(bool $allowInvalidJson = false): Rules
    {
        return MessageService::parseJsonFromBody($this->exceptionFactory, $this->request, 'Request body', $allowInvalidJson);
    }

    /**
     * this is experimental url query parser,
     * url after question mark is decoded and parsed as json,
     * parsing json directly from query string may not be supported by all http libraries or tools
     */
    public function queryJson(bool $allowInvalidJson = false): Rules
    {
        $query = $this->request->getUri()->getQuery();
        $query = \urldecode($query);

        $query = \json_decode($query);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            if ($allowInvalidJson) {
                $query = null;
            } else {
                throw $this->exceptionFactory->create('Request query must be valid json which is url encoded before concatenation after question mark');
            }
        }

        return new Rules($this->exceptionFactory, 'Request query json', new Validated($query));
    }
}
