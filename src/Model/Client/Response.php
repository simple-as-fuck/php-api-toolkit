<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Model\Client;

use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Data\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\StreamRules;
use SimpleAsFuck\ApiToolkit\Factory\Client\ParseResponseException;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\String\ParseJson;

final readonly class Response extends \SimpleAsFuck\ApiToolkit\Data\Common\Response
{
    public function __construct(
        private Request $request,
        ResponseInterface $response
    ) {
        parent::__construct($response);
    }

    /**
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function getJson(
        bool $allowInvalidJson = false,
        bool $emptyStringAsNull = false,
        int $jsonDecodeFlags = 0,
    ): ParseJson {
        return ParseJson::make(
            $this->getBody()->getContents(),
            'Response body',
            new ParseResponseException($this->request, $this),
            allowInvalidJson: $allowInvalidJson,
            emptyStringAsNull: $emptyStringAsNull,
            jsonDecodeFlags: $jsonDecodeFlags,
        )
            ->cache()
        ;
    }

    /**
     * @param int $jsonDecodeFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @return StreamRules<ParseJson>
     * @throws ApiException
     */
    public function getJsonl(
        bool $allowInvalidJson = false,
        bool $emptyStringAsNull = false,
        int $jsonDecodeFlags = 0,
    ): StreamRules {
        return new StreamRules(
            Validator::jsonl(
                $this->getBody(),
                'Response body',
                new ParseResponseException($this->request, $this),
                allowInvalidJson: $allowInvalidJson,
                emptyStringAsNull: $emptyStringAsNull,
                jsonDecodeFlags: $jsonDecodeFlags,
            ),
        );
    }

    protected function clone(ResponseInterface $response): static
    {
        return new self($this->request, $response);
    }
}
