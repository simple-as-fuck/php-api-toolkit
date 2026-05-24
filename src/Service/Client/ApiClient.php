<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Kayex\HttpCodes;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Data\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\ResponseApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\BadRequestApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\ConflictApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\ForbiddenApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\GoneApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\InternalServerErrorApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\NotFoundApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\ServiceUnavailableApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\StreamRules;
use SimpleAsFuck\ApiToolkit\Data\Client\UnauthorizedApiException;
use SimpleAsFuck\ApiToolkit\Data\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Factory\Client\ParseResponseException;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponsePromise;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\ApiToolkit\Service\Common\ProblemDetailTransformer;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\ParamsTransformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookTransformer;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;
use SimpleAsFuck\Validator\Rule\String\StringRule;

class ApiClient
{
    public function __construct(
        private readonly Config $config,
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ?DeprecationsLogger $deprecationsLogger = null,
    ) {
    }

    /**
     * @template TBody
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $urlWithQuery
     * @param TBody|null $body will be encoded as application/json
     * @param Transformer<TBody>|null $bodyTransformer
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @param int $requestJsonFlags bitmask https://www.php.net/manual/en/function.json-encode.php
     * @throws ApiException
     */
    public function request(
        string $apiName,
        string $method,
        string $urlWithQuery,
        mixed $body = null,
        ?Transformer $bodyTransformer = null,
        array $headers = [],
        array $options = [],
        int $requestJsonFlags = 0,
    ): Response {
        $request = new Request($method, $urlWithQuery, [], null, $headers);
        if ($body !== null) {
            $request = $request->withJson($body, $bodyTransformer, $requestJsonFlags);
        }

        return $this->waitRaw($this->requestAsync($apiName, $request, $options));
    }

    /**
     * @template TBody
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $urlWithQuery
     * @param TBody|null $body will be encoded as application/json
     * @param Transformer<TBody>|null $bodyTransformer
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @param int $requestJsonFlags bitmask https://www.php.net/manual/en/function.json-encode.php
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function requestObject(
        string $apiName,
        string $method,
        string $urlWithQuery,
        mixed $body = null,
        ?Transformer $bodyTransformer = null,
        array $headers = [],
        array $options = [],
        int $requestJsonFlags = 0,
        int $responseJsonFlags = 0,
    ): ObjectRule {
        return $this
            ->request($apiName, $method, $urlWithQuery, $body, $bodyTransformer, $headers, $options, $requestJsonFlags)
            ->getJson(jsonDecodeFlags: $responseJsonFlags)
            ->object()
        ;
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $url
     * @param array<mixed> $query
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function requestArray(
        string $apiName,
        string $method,
        string $url,
        array $query = [],
        array $headers = [],
        array $options = [],
        int $responseJsonFlags = 0
    ): ArrayRule {
        return $this->waitArray(
            $this->requestAsync(
                $apiName,
                new Request($method, $url, $query, null, $headers),
                $options,
            ),
            responseJsonFlags: $responseJsonFlags,
        );
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $url
     * @param array<mixed> $query
     * @param array<non-empty-string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function requestStream(
        string $apiName,
        string $method,
        string $url,
        array $query = [],
        array $headers = [],
        array $options = [],
        int $responseJsonFlags = 0,
    ): StreamRules {
        $options[RequestOptions::STREAM] = true;
        return $this->waitStream(
            $this->requestAsync(
                $apiName,
                new Request($method, $url, $query, null, $headers),
                $options,
            ),
            responseJsonFlags: $responseJsonFlags,
        );
    }

    /**
     * @param non-empty-string $apiName
     * @param array<RequestOptions::*, mixed> $options
     * @throws ApiException
     */
    public function requestRaw(string $apiName, Request $request, array $options = []): Response
    {
        return $this->waitRaw($this->requestAsync($apiName, $request, $options));
    }

    /**
     * @param non-empty-string $apiName
     * @param array<RequestOptions::*, mixed> $options
     */
    public function requestAsync(string $apiName, Request $request, array $options = []): ResponsePromise
    {
        if (! $request->hasBaseUrl()) {
            $request = $request->withBaseUrl($this->config->getBaseUrl($apiName));
        }

        $defaultHeaders = $this->config->getDefaultHeaders($apiName);
        foreach ($defaultHeaders as $defaultHeader => $value) {
            $defaultHeader = (string) $defaultHeader;
            if (! $request->hasHeader($defaultHeader)) {
                $request = $request->withHeader($defaultHeader, $value);
            }
        }

        $psrRequest = $request->createPsr($this->requestFactory);

        $defaultOptions = $this->config->getDefaultOptions($apiName);
        $defaultOptions->key(RequestOptions::TIMEOUT)->float()->min(0)->notNull(if: ($options[RequestOptions::TIMEOUT] ?? null) === null);
        foreach ($defaultOptions->nullable() ?? [] as $key => $defaultOption) {
            if (! array_key_exists($key, $options)) {
                $options[$key] = $defaultOption;
            }
        }

        return new ResponsePromise($apiName, $request, $this->client->sendAsync($psrRequest, $options));
    }

    /**
     * @todo use ParseJson::make instead of Validator::json
     * @throws ApiException
     */
    public function waitRaw(ResponsePromise $promise): Response
    {
        try {
            /**
             * @var ResponseInterface $response
             * @throws RequestException|TransferException
             */
            $response = $promise->promise->wait();
            $response = new Response($promise->request, $response);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            if ($response !== null) {
                $this->deprecationsLogger?->logDeprecation($promise->apiName, $promise->request, $response);

                $responseContent = $response->getBody()->getContents();
                $response = $response->withBody(Utils::streamFor($responseContent));
                $response = new Response($promise->request, $response);

                $errorObject = Validator::json(
                    $responseContent,
                    'Response problem detail: json',
                    new ParseResponseException($promise->request, $response),
                    allowInvalidJson: true,
                )
                    ->object()
                ;
                $problemDetail = $errorObject->class(new ProblemDetailTransformer())->nullable(true);

                $statusCode = $problemDetail->status ?? $response->getStatusCode();

                match ($response->getStatusCode()) {
                    HttpCodes::HTTP_BAD_REQUEST => throw new BadRequestApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_UNAUTHORIZED => throw new UnauthorizedApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_FORBIDDEN => throw new ForbiddenApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_NOT_FOUND => throw new NotFoundApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_CONFLICT => throw new ConflictApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_GONE => throw new GoneApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_INTERNAL_SERVER_ERROR => throw new InternalServerErrorApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    HttpCodes::HTTP_SERVICE_UNAVAILABLE => throw new ServiceUnavailableApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                    default => throw new ResponseApiException($this->buildExceptionMessage($promise, $problemDetail, $errorObject, $exception, $response->getStatusCode()), $statusCode, $promise->request, $response, $problemDetail, $errorObject, $exception),
                };
            }

            throw new ApiException($exception->getMessage(), 0, $promise->request, null, null, null, $exception);
        } catch (TransferException $exception) {
            throw new ApiException($exception->getMessage(), 0, $promise->request, null, null, null, $exception);
        }

        $this->deprecationsLogger?->logDeprecation($promise->apiName, $promise->request, $response);
        return $response;
    }

    /**
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function waitObject(ResponsePromise $promise, bool $allowInvalidJson = false, int $responseJsonFlags = 0): ObjectRule
    {
        return $this->waitRaw($promise)->getJson(allowInvalidJson: $allowInvalidJson, jsonDecodeFlags: $responseJsonFlags)->object();
    }

    /**
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function waitArray(ResponsePromise $promise, bool $allowInvalidJson = false, int $responseJsonFlags = 0): ArrayRule
    {
        return $this->waitRaw($promise)->getJson(allowInvalidJson: $allowInvalidJson, jsonDecodeFlags: $responseJsonFlags)->array();
    }

    /**
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function waitStream(ResponsePromise $promise, bool $allowInvalidJson = false, int $responseJsonFlags = 0): StreamRules
    {
        return $this->waitRaw($promise)->getJsonl(allowInvalidJson: $allowInvalidJson, jsonDecodeFlags: $responseJsonFlags);
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $type
     * @param non-empty-string $listeningUrl example: https://example.com/listening/...
     * @param Priority::* $priority
     * @param array<non-empty-string, non-empty-string> $requiredAttributes without them can not be webhook dispatched
     * @param array<string, string|array<string>> $requestHeaders
     * @param array<RequestOptions::*, mixed> $requestOptions
     * @param int $requestJsonFlags bitmask https://www.php.net/manual/en/function.json-encode.php
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function addWebhookListener(
        string $apiName,
        string $type,
        string $listeningUrl,
        int $priority = Priority::NORMAL,
        array $requiredAttributes = [],
        array $requestHeaders = [],
        array $requestOptions = [],
        int $requestJsonFlags = 0,
        int $responseJsonFlags = 0
    ): Webhook {
        return $this->requestObject(
            $apiName,
            'POST',
            '/webhook?type='.$type,
            new Params(
                StringRule::make($listeningUrl, 'Parameter $listeningUrl')->httpUrl([PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PATH])->notNull(),
                $priority,
                $requiredAttributes
            ),
            new ParamsTransformer(),
            $requestHeaders,
            $requestOptions,
            $requestJsonFlags,
            $responseJsonFlags,
        )
            ->class(new WebhookTransformer())->notNull()
        ;
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $webhookId
     * @param array<string, string|array<string>> $requestHeaders
     * @param array<RequestOptions::*, mixed> $requestOptions
     * @throws ApiException
     */
    public function removeWebhookListener(string $apiName, string $webhookId, array $requestHeaders = [], array $requestOptions = []): void
    {
        $this->request($apiName, 'DELETE', '/webhook?webhookId='.$webhookId, headers: $requestHeaders, options: $requestOptions);
    }

    private function buildExceptionMessage(
        ResponsePromise $promise,
        ?ProblemDetail $problemDetail,
        ObjectRule $problemDetailExtensions,
        \Throwable $previous,
        ?int $statusCode = null,
    ): string {
        $messageParts = [];

        if ($problemDetail?->type !== null) {
            $messageParts[] = 'Error type: "'.$problemDetail->type.'"';
        }
        // https://datatracker.ietf.org/doc/html/rfc9457#name-extension-members
        $extensionMessage = $problemDetailExtensions->property('message')->string()->notEmpty()->nullable(true);
        if ($extensionMessage !== null) {
            $messageParts[] = $extensionMessage;
        }

        if (count($messageParts) === 0) {
            if ($problemDetail?->title !== null) {
                $messageParts[] = 'Error title: "'.$problemDetail->title.'"';
            }
            if ($problemDetail?->detail !== null) {
                $messageParts[] = 'Error detail: "'.$problemDetail->detail.'"';
            }
        }

        if (count($messageParts) === 0) {
            return $previous->getMessage();
        }

        if ($statusCode !== null) {
            $messageParts[] = 'status (' . $statusCode . ')';
        }
        if ($problemDetail?->instance !== null) {
            $messageParts[] = 'error instance: "' . $problemDetail->instance . '"';
        }

        return 'API ' . $promise->apiName . ' ' . $promise->request->method() . ' ' . $promise->request->url() . ' returned error: ' . implode(' ', $messageParts);
    }
}
