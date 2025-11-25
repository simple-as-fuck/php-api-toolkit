<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Data\Server;

use Psr\Http\Message\ServerRequestInterface;
use SimpleAsFuck\ApiToolkit\Data\Webhook\WebhookRules;
use SimpleAsFuck\ApiToolkit\Service\Common\JsonService;
use SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookTransformer;
use SimpleAsFuck\Validator\Factory\Exception;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\General\Rules;

final readonly class RequestRules
{
    public function __construct(
        private Exception $exceptionFactory,
        private ServerRequestInterface $request
    ) {
    }

    public function header(): HeaderRule
    {
        return new HeaderRule($this->exceptionFactory, new Validated($this->request->getHeaders()));
    }

    public function query(): QueryRule
    {
        return new QueryRule($this->exceptionFactory, new Validated($this->request->getQueryParams()));
    }

    /**
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     */
    public function json(bool $allowInvalidJson = false, int $jsonDecodeFlags = 0): Rules
    {
        return JsonService::jsonDecode($this->request->getBody()->getContents(), 'Request body', $this->exceptionFactory, $allowInvalidJson, $jsonDecodeFlags);
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

        return new Rules($this->exceptionFactory, 'Request query: json', new Validated($query));
    }

    public function webhook(): WebhookRules
    {
        return new WebhookRules(
            $this->exceptionFactory,
            $this->json()->object()->class(new WebhookTransformer())->notNull(),
        );
    }
}
