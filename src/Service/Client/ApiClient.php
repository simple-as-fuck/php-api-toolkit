<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\RequestOptions;
use Kayex\HttpCodes;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\ApiToolkit\Model\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\BadRequestApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ConflictApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\ForbiddenApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\GoneApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\NotFoundApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponseApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\UnauthorizedApiException;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\ParamsTransformer;
use SimpleAsFuck\ApiToolkit\Service\Webhook\WebhookTransformer;
use SimpleAsFuck\Validator\Factory\Validator;
use SimpleAsFuck\Validator\Rule\ArrayRule\ArrayRule;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;
use SimpleAsFuck\Validator\Rule\String\StringRule;

class ApiClient
{
    private Repository $configRepository;
    private Client $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(Repository $configRepository, Client $client, RequestFactoryInterface $requestFactory)
    {
        $this->configRepository = $configRepository;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
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
    public function requestAsync(string $apiName, Request $request, array $options = []): PromiseInterface
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

        return $this->client->sendAsync($request, $options);
    }

    /**
     * @throws ApiException
     */
    public function waitRaw(PromiseInterface $promise): Response
    {
        try {
            /** @var ResponseInterface $response */
            $response = $promise->wait();
            $response = new Response($response);
        } catch (RequestException $exception) {
            $message = $exception->getMessage();
            $response = $exception->getResponse();
            if ($response !== null) {
                $responseContent = $response->getBody()->getContents();
                $jsonMessage = Validator::make(\json_decode($responseContent))
                    ->object()
                    ->property('message')
                    ->string()
                    ->notEmpty()
                    ->nullable(true)
                ;
                if ($jsonMessage !== null) {
                    $message = $jsonMessage;
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

        return $response;
    }

    /**
     * @throws ApiException
     */
    public function waitObject(PromiseInterface $promise, bool $allowInvalidJson = false): ObjectRule
    {
        return $this->waitRaw($promise)->getJson($allowInvalidJson)->object();
    }

    /**
     * @throws ApiException
     */
    public function waitArray(PromiseInterface $promise, bool $allowInvalidJson = false): ArrayRule
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
        return $this->request(
            $apiName,
            'POST',
            '/webhook?type='.$type,
            new Params(
                StringRule::make($listeningUrl, 'Parameter $listeningUrl')->parseHttpUrl([PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PATH])->notNull(),
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
