<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Data\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\BadRequestApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\ForbiddenApiException;
use SimpleAsFuck\ApiToolkit\Data\Client\UnauthorizedApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponsePromise;
use SimpleAsFuck\ApiToolkit\Service\Client\ApiClient;
use SimpleAsFuck\ApiToolkit\Service\Client\Config;

final class ApiClientTest extends TestCase
{
    private ApiClient $apiClient;

    protected function setUp(): void
    {
        $config = $this->createMock(Config::class);
        $client = $this->createMock(Client::class);
        $httpFactory = new HttpFactory();

        $this->apiClient = new ApiClient($config, $client, $httpFactory);
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('dataProviderWaitRawFail')]
    public function testWaitRawFail(string $expectedClass, int $expectedStatusCode, string $expectedMessage, ?string $expectedContent, \Throwable $exception): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('wait')->willThrowException($exception);
        $promise = new ResponsePromise('test', new Request('GET', '/'), $promise);

        try {
            $this->apiClient->waitRaw($promise);
        } catch (ApiException $apiException) {
            self::assertInstanceOf($expectedClass, $apiException);
            self::assertSame($expectedStatusCode, $apiException->getCode());
            self::assertSame($expectedMessage, $apiException->getMessage());
            self::assertSame($expectedContent, $apiException->getResponse()?->getBody()->getContents());
        }
    }

    /**
     * @return non-empty-array<non-empty-array<mixed>>
     */
    public static function dataProviderWaitRawFail(): array
    {
        $httpFactory = new HttpFactory();
        $request = $httpFactory->createRequest('GET', '/');
        $response = new Response(new Request('GET', '/'), $httpFactory->createResponse(400));

        return [
            [ApiException::class, 0, 'Exception message', null, new TransferException('Exception message')],
            [BadRequestApiException::class, 400, 'Exception message', '', new RequestException('Exception message', $request, $response)],
            [
                BadRequestApiException::class,
                400,
                'Json message',
                '{"message":"Json message"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withBody($httpFactory->createStream('{"message":"Json message"}'))
                ),
            ],
            [
                BadRequestApiException::class,
                400,
                'Error title: "Json title"',
                '{"title":"Json title"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withBody($httpFactory->createStream('{"title":"Json title"}'))
                ),
            ],
            [
                BadRequestApiException::class,
                400,
                'Json message',
                '{"title":"Json title","message":"Json message"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withBody($httpFactory->createStream('{"title":"Json title","message":"Json message"}'))
                ),
            ],
            [
                UnauthorizedApiException::class,
                401,
                'Error type: "/test/error" error instance: "/test/url"',
                '{"title":"Json title","type":"/test/error","status":401,"instance":"/test/url"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withStatus(401)->withBody($httpFactory->createStream('{"title":"Json title","type":"/test/error","status":401,"instance":"/test/url"}'))
                ),
            ],
            [
                ForbiddenApiException::class,
                403,
                'Error type: "/test/error" Json message',
                '{"type":"/test/error","message":"Json message"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withStatus(403)->withBody($httpFactory->createStream('{"type":"/test/error","message":"Json message"}'))
                ),
            ],
        ];
    }
}
