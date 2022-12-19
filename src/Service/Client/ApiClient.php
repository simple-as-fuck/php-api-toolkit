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
use SimpleAsFuck\ApiToolkit\Model\Client\NotFoundApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponseApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponsePromise;
use SimpleAsFuck\ApiToolkit\Model\Client\UnauthorizedApiException;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

class ApiClient
{
    public function __construct(
        private Repository $configRepository,
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
     * @param TBody|null $body
     * @param Transformer<TBody>|null $bodyTransformer
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @throws ApiException
     */
    public function request(string $apiName, string $method, string $urlWithQuery, $body = null, ?Transformer $bodyTransformer = null, array $headers = [], array $options = []): ObjectRule
    {
        $request = new Request($method, $urlWithQuery, [], null, $headers);
        if ($body !== null) {
            $request = $request->withJson($body, $bodyTransformer);
        }

        return $this->waitObject($this->requestAsync($apiName, $request, $options), true);
    }

    /**
     * @param non-empty-string $apiName
     * @param non-empty-string $method
     * @param non-empty-string $urlWithQuery
     * @param array<string, string|array<string>> $headers
     * @param array<RequestOptions::*, mixed> $options
     * @throws ApiException
     */
    public function requestObject(string $apiName, string $method, string $urlWithQuery, array $headers = [], array $options = []): ObjectRule
    {
        return $this->waitObject($this->requestAsync($apiName, new Request($method, $urlWithQuery, [], null, $headers), $options));
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
        $config = $this->configRepository->getClientConfig($apiName);
        if (! $request->hasBaseUrl()) {
            $request = $request->withBaseUrl($config->baseUrl());
        }

        $request = $request->createPsr($this->requestFactory);

        $token = $config->bearerToken();
        if (! $request->hasHeader('Authorization') && $token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer '.$token);
        }

        $defaultOptions = [
            RequestOptions::VERIFY => $config->verifyCerts(),
        ];

        foreach ($defaultOptions as $key => $defaultOption) {
            if (! array_key_exists($key, $options)) {
                $options[$key] = $defaultOption;
            }
        }

        return new ResponsePromise($apiName, $request, $this->client->sendAsync($request, $options));
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
                $response = new Response($response);
                if ($response->getStatusCode() === HttpCodes::HTTP_BAD_REQUEST) {
                    throw new BadRequestApiException($response, $message, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_UNAUTHORIZED) {
                    throw new UnauthorizedApiException($response, $message, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_FORBIDDEN) {
                    throw new ForbiddenApiException($response, $message, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_NOT_FOUND) {
                    throw new NotFoundApiException($response, $message, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_CONFLICT) {
                    throw new ConflictApiException($response, $message, $exception);
                }
                if ($response->getStatusCode() === HttpCodes::HTTP_GONE) {
                    throw new GoneApiException($response, $message, $exception);
                }

                throw new ResponseApiException($response, $message, $exception);
            }

            throw new ApiException($message, null, $exception);
        } catch (TransferException $exception) {
            throw new ApiException($exception->getMessage(), null, $exception);
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
}
