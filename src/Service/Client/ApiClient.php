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
        private Config $config,
        private Client $client,
        private RequestFactoryInterface $requestFactory,
        private ?DeprecationsLogger $deprecationsLogger = null
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
     * @throws ApiException
     */
    public function request(string $apiName, string $method, string $urlWithQuery, mixed $body = null, ?Transformer $bodyTransformer = null, array $headers = [], array $options = []): Response
    {
        $request = new Request($method, $urlWithQuery, [], null, $headers);
        if ($body !== null) {
            $request = $request->withJson($body, $bodyTransformer);
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
     * @throws ApiException
     */
    public function requestObject(string $apiName, string $method, string $urlWithQuery, mixed $body = null, ?Transformer $bodyTransformer = null, array $headers = [], array $options = []): ObjectRule
    {
        return $this->request($apiName, $method, $urlWithQuery, $body, $bodyTransformer, $headers, $options)->getJson()->object();
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $url
     * @param array<mixed> $query
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @throws ApiException
     */
    public function requestArray(string $apiName, string $method, string $url, array $query = [], array $headers = [], array $options = []): ArrayRule
    {
        return $this->waitArray($this->requestAsync($apiName, new Request($method, $url, $query, null, $headers), $options));
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

        $psrRequest = $request->createPsr($this->requestFactory);

        $token = $this->config->getBearerToken($apiName);
        if (! $psrRequest->hasHeader('Authorization') && $token !== null) {
            $psrRequest = $psrRequest->withHeader('Authorization', 'Bearer '.$token);
        }

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
            $response = $promise->wait();
        } catch (RequestException $exception) {
            $message = $exception->getMessage();
            $response = $exception->getResponse();
            if ($response !== null) {
                $this->deprecationsLogger?->logDeprecation($promise->apiName(), $promise->request(), $response);

                $responseContent = $response->getBody()->getContents();
                $errorObject = Validator::make(\json_decode($responseContent))->object();
                $errorParts = [];

                // https://datatracker.ietf.org/doc/html/rfc7807
                $errorType = $errorObject->property('type')->string()->notEmpty()->nullable(true);
                if ($errorType !== null) {
                    $errorParts[] = 'Error type: "'.$errorType.'"';
                }

                $errorMessage =
                    $errorObject
                        ->property('title')
                        ->string()
                        ->notEmpty()
                        ->nullable(true)
                    ??
                    $errorObject
                        ->property('message')
                        ->string()
                        ->notEmpty()
                        ->nullable(true)
                ;
                if ($errorMessage !== null) {
                    $errorParts[] = $errorMessage;
                }

                if (count($errorParts) !== 0) {
                    $errorStatus = $errorObject->property('status')->int()->nullable(true);
                    if ($errorStatus !== null) {
                        $errorParts[] = 'status ('.$errorStatus.')';
                    }
                    $errorInstance = $errorObject->property('instance')->string()->notEmpty()->nullable(true);
                    if ($errorInstance !== null) {
                        $errorParts[] = 'instance: "'.$errorInstance.'"';
                    }

                    $message = implode(' ', $errorParts);
                }

                $response = $response->withBody((new HttpFactory())->createStream($responseContent));
                $response = new Response($promise->request(), $response);
                if ($response->getStatusCode() === HttpCodes::HTTP_BAD_REQUEST) {
                    throw new BadRequestApiException($message, $promise->request(), $response, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_UNAUTHORIZED) {
                    throw new UnauthorizedApiException($message, $promise->request(), $response, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_FORBIDDEN) {
                    throw new ForbiddenApiException($message, $promise->request(), $response, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_NOT_FOUND) {
                    throw new NotFoundApiException($message, $promise->request(), $response, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_CONFLICT) {
                    throw new ConflictApiException($message, $promise->request(), $response, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_GONE) {
                    throw new GoneApiException($message, $promise->request(), $response, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_INTERNAL_SERVER_ERROR) {
                    throw new InternalServerErrorApiException($message, $promise->request(), $response, $exception);
                }

                throw new ResponseApiException($message, $promise->request(), $response, $exception);
            }

            throw new ApiException($message, $promise->request(), null, $exception);
        } catch (TransferException $exception) {
            throw new ApiException($exception->getMessage(), $promise->request(), null, $exception);
        }

        $this->deprecationsLogger?->logDeprecation($promise->apiName(), $promise->request(), $response);
        return $response;
    }

    /**
     * @throws ApiException
     */
    public function waitObject(ResponsePromise $promise, bool $allowInvalidJson = false): ObjectRule
    {
        return $this->waitRaw($promise)->getJson($allowInvalidJson)->object();
    }

    /**
     * @throws ApiException
     */
    public function waitArray(ResponsePromise $promise, bool $allowInvalidJson = false): ArrayRule
    {
        return $this->waitRaw($promise)->getJson($allowInvalidJson)->array();
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $type
     * @param non-empty-string $listeningUrl example: https://example.com/listening/...
     * @param Priority::* $priority
     * @param array<non-empty-string, string> $attributes required attributes without them can not be webhook dispatched
     * @param array<string, string|array<string>> $requestHeaders
     * @throws ApiException
     */
    public function addWebhookListener(string $apiName, string $type, string $listeningUrl, int $priority = Priority::NORMAL, array $attributes = [], array $requestHeaders = []): Webhook
    {
        return $this->requestObject(
            $apiName,
            'POST',
            '/webhook?type='.$type,
            new Params(
                StringRule::make($listeningUrl, 'Parameter $listeningUrl')->httpUrl([PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PATH])->notNull(),
                $priority,
                $attributes
            ),
            new ParamsTransformer(),
            $requestHeaders
        )
            ->class(new WebhookTransformer())->notNull()
        ;
    }

    /**
     * @param non-empty-string $apiName
     * @param array<string, string|array<string>> $requestHeaders
     * @throws ApiException
     */
    public function removeWebhookListener(string $apiName, string $webhookId, array $requestHeaders = []): void
    {
        $this->request($apiName, 'DELETE', '/webhook?webhookId='.$webhookId, null, null, $requestHeaders);
    }
}
