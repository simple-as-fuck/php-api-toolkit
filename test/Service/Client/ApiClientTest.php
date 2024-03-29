<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Client\ApiException;
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
     * @dataProvider dataProviderWaitRawFail
     */
    public function testWaitRawFail(int $expectedStatusCode, string $expectedMessage, string $expectedContent, \Throwable $exception): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('wait')->willThrowException($exception);
        $promise = new ResponsePromise('test', new Request('GET', '/'), $promise);

        try {
            $this->apiClient->waitRaw($promise);
        } catch (ApiException $apiException) {
            self::assertSame($expectedStatusCode, $apiException->getCode());
            self::assertSame($expectedMessage, $apiException->getMessage());
            $response = $apiException->response();
            if ($response !== null) {
                self::assertSame($expectedContent, $response->getBody()->getContents());
            }
        }
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderWaitRawFail(): array
    {
        $httpFactory = new HttpFactory();
        $request = $httpFactory->createRequest('GET', '/');
        $response = new Response(new Request('GET', '/'), $httpFactory->createResponse(400));

        return [
            [0, 'Exception message', '', new TransferException('Exception message')],
            [400, 'Exception message', '', new RequestException('Exception message', $request, $response)],
            [
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
                400,
                'Json title',
                '{"title":"Json title"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withBody($httpFactory->createStream('{"title":"Json title"}'))
                ),
            ],
            [
                400,
                'Error type: "/test/error" Json title status (401) instance: "/test/url"',
                '{"title":"Json title","type":"/test/error","status":401,"instance":"/test/url"}',
                new RequestException(
                    'Exception message',
                    $request,
                    $response->withBody($httpFactory->createStream('{"title":"Json title","type":"/test/error","status":401,"instance":"/test/url"}'))
                ),
            ],
        ];
    }
}
