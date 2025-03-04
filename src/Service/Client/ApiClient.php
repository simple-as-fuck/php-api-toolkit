<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\RequestOptions;
use Kayex\HttpCodes;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\DataObject\Client\ServiceUnavailableApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\BadRequestApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ConflictApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ForbiddenApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\GoneApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\InternalServerErrorApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\NotFoundApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponseApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponsePromise;
use SimpleAsFuck\ApiToolkit\Model\Client\UnauthorizedApiException;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
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
        private readonly Client $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ?DeprecationsLogger $deprecationsLogger = null
    ) {
    }

    /**
     * @template TBody
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $urlWithQuery
     * @param TBody|null $body will be encoded as json
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
        int $requestJsonFlags = 0
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
     * @param TBody|null $body will be encoded as json
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
        int $responseJsonFlags = 0
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
        return $this->waitArray($this->requestAsync($apiName, new Request($method, $url, $query, null, $headers), $options), responseJsonFlags: $responseJsonFlags);
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

        $defaultOptions = [
            RequestOptions::VERIFY => $this->config->getVerifyCerts($apiName),
        ];
        foreach ($defaultOptions as $key => $defaultOption) {
            if (! array_key_exists($key, $options)) {
                $options[$key] = $defaultOption;
            }
        }

        return new ResponsePromise($apiName, $request, $this->client->sendAsync($psrRequest, $options));
    }

    /**
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
            $message = $exception->getMessage();
            $response = $exception->getResponse();
            if ($response !== null) {
                $this->deprecationsLogger?->logDeprecation($promise->apiName, $promise->request, $response);

                $responseContent = $response->getBody()->getContents();
                $errorObject = Validator::make(\json_decode($responseContent))->object();
                $messageParts = [];

                // https://datatracker.ietf.org/doc/html/rfc9457#name-type
                $errorType = $errorObject->property('type')->string()->notEmpty()->nullable(true);
                if ($errorType !== null) {
                    $messageParts[] = 'Error type: "'.$errorType.'"';
                }

                $errorMessage = $errorObject->property('message')->string()->notEmpty()->nullable(true);
                if ($errorMessage !== null) {
                    $messageParts[] = $errorMessage;
                }

                // https://datatracker.ietf.org/doc/html/rfc9457#name-status
                $errorStatus = $errorObject->property('status')->int()->nullable(true) ?? $response->getStatusCode();
                // https://datatracker.ietf.org/doc/html/rfc9457#name-instance
                $errorInstance = $errorObject->property('instance')->string()->notEmpty()->nullable(true);
                // https://datatracker.ietf.org/doc/html/rfc9457#name-title
                $errorTitle = $errorObject->property('title')->string()->notEmpty()->nullable(true);
                // https://datatracker.ietf.org/doc/html/rfc9457#name-detail
                $errorDetail = $errorObject->property('detail')->string()->notEmpty()->nullable(true);

                if (count($messageParts) === 0) {
                    if ($errorTitle !== null) {
                        $messageParts[] = 'Error title: "'.$errorTitle.'"';
                    }
                    if ($errorDetail !== null) {
                        $messageParts[] = 'Error detail: "'.$errorDetail.'"';
                    }
                }

                if (count($messageParts) !== 0) {
                    $messageParts[] = 'status (' . $errorStatus . ')';
                    if ($errorInstance !== null) {
                        $messageParts[] = 'error instance: "' . $errorInstance . '"';
                    }

                    $message = implode(' ', $messageParts);
                }

                $response = $response->withBody((new HttpFactory())->createStream($responseContent));
                $response = new Response($promise->request, $response);

                match ($response->getStatusCode()) {
                    HttpCodes::HTTP_BAD_REQUEST => throw new BadRequestApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_UNAUTHORIZED => throw new UnauthorizedApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_FORBIDDEN => throw new ForbiddenApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_NOT_FOUND => throw new NotFoundApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_CONFLICT => throw new ConflictApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_GONE => throw new GoneApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_INTERNAL_SERVER_ERROR => throw new InternalServerErrorApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    HttpCodes::HTTP_SERVICE_UNAVAILABLE => throw new ServiceUnavailableApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                    default => throw new ResponseApiException($message, $errorStatus, $errorInstance, $errorType, $errorTitle, $errorDetail, $promise->request, $response, $exception),
                };
            }

            throw new ApiException($message, 0, $promise->request->url(), null, null, null, $promise->request, $response, $exception);
        } catch (TransferException $exception) {
            throw new ApiException($exception->getMessage(), 0, $promise->request->url(), null, null, null, $promise->request, null, $exception);
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
        return $this->waitRaw($promise)->getJson($allowInvalidJson, $responseJsonFlags)->object();
    }

    /**
     * @param int $responseJsonFlags bitmask https://www.php.net/manual/en/function.json-decode.php
     * @throws ApiException
     */
    public function waitArray(ResponsePromise $promise, bool $allowInvalidJson = false, int $responseJsonFlags = 0): ArrayRule
    {
        return $this->waitRaw($promise)->getJson($allowInvalidJson, $responseJsonFlags)->array();
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
}
