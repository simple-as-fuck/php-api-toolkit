<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Client\ApiException;
use SimpleAsFuck\ApiToolkit\Model\Client\Response;
use SimpleAsFuck\ApiToolkit\Model\Client\ResponsePromise;
use SimpleAsFuck\ApiToolkit\Service\Client\ApiClient;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;

final class ApiClientTest extends TestCase
{
    private ApiClient $apiClient;
    private HttpFactory $httpFactory;

    protected function setUp(): void
    {
        $configRepository = $this->createMock(Repository::class);
        $client = $this->createMock(Client::class);
        $this->httpFactory = new HttpFactory();

        $this->apiClient = new ApiClient($configRepository, $client, $this->httpFactory);
    }

    /**
     * @dataProvider dataProviderWaitRawFail
     */
    public function testWaitRawFail(int $expectedStatusCode, string $expectedMessage, string $expectedContent, \Throwable $exception): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('wait')->willThrowException($exception);
        $promise = new ResponsePromise('test', $this->httpFactory->createRequest('GET', 'http://test'), $promise);

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
    public function dataProviderWaitRawFail(): array
    {
        $httpFactory = new HttpFactory();
        $request = $httpFactory->createRequest('GET', '');
        $response = new Response($httpFactory->createResponse(400));
        $jsonBody = $httpFactory->createStream('{"message":"Json message"}');

        return [
            [0, 'Exception message', '', new TransferException('Exception message')],
            [400, 'Exception message', '', new RequestException('Exception message', $request, $response)],
            [400, 'Json message', '{"message":"Json message"}', new RequestException('Exception message', $request, $response->withBody($jsonBody))],
        ];
    }
}
