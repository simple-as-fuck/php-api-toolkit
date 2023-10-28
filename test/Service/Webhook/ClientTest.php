<?php

declare(strict_types=1);


use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions as RequestOptions;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority as Priority;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Client;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Config;

/**
 * @covers \SimpleAsFuck\ApiToolkit\Service\Webhook\Client
 */
final class ClientTest extends TestCase
{
    /**
     * @dataProvider dataCallWebhooks
     *
     * @param array<Webhook> $expectedRetryWebhooks
     * @param array<Webhook> $webhooks
     */
    public function testCallWebhooks(array $expectedRetryWebhooks, int $expectedCalls, array $webhooks, int $tries): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getDelayBetweenTries')->willReturn(5);
        $config->method('getMaxTries')->willReturn(6);

        $httpCalls = 0;
        $httpClient = $this->createMock(\GuzzleHttp\Client::class);
        $httpClient->method('request')->willReturnCallback(static function (string $method, string $uri, array $options) use (&$httpCalls): Response {
            $httpCalls++;
            if (($options[RequestOptions::JSON]->params->attributes[0]->value ?? null) === 'fail') {
                throw new \RuntimeException();
            }

            if (($options[RequestOptions::JSON]->params->attributes[0]->value ?? null) === 'stop') {
                return new Response(200, body: '{"stopDispatching":true}');
            }

            return new Response(200, body: '{}');
        });

        $client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['dispatchWebhooks'])
            ->setConstructorArgs([$config, $httpClient, null])
            ->getMock()
        ;

        if (count($expectedRetryWebhooks) === 0) {
            $client->expects(self::never())->method('dispatchWebhooks');
        } else {
            $client->expects(self::once())->method('dispatchWebhooks')->with($expectedRetryWebhooks, 5, $tries + 1, [], []);
        }

        $client->callWebhooks($webhooks, $tries, [], []);

        self::assertSame($expectedCalls, $httpCalls);
    }

    /**
     * @return non-empty-list<non-empty-list<mixed>>
     */
    public static function dataCallWebhooks(): array
    {
        return [
            [
                [],
                3,
                [
                    new Webhook('5', 'test', new Params('http://test1', Priority::HIGH, [])),
                    new Webhook('4', 'test', new Params('http://test2', Priority::NORMAL, [])),
                    new Webhook('6', 'test', new Params('http://test3', Priority::LOW, [])),
                ],
                0,
            ],
            [
                [
                    new Webhook('4', 'test', new Params('http://test2', Priority::NORMAL, ['call' => 'fail'])),
                    new Webhook('6', 'test', new Params('http://test3', Priority::LOW, [])),
                ],
                2,
                [
                    new Webhook('5', 'test', new Params('http://test1', Priority::HIGH, [])),
                    new Webhook('4', 'test', new Params('http://test2', Priority::NORMAL, ['call' => 'fail'])),
                    new Webhook('6', 'test', new Params('http://test3', Priority::LOW, [])),
                ],
                0,
            ],
            [
                [],
                2,
                [
                    new Webhook('5', 'test', new Params('http://test1', Priority::HIGH, [])),
                    new Webhook('4', 'test', new Params('http://test2', Priority::NORMAL, ['call' => 'fail'])),
                    new Webhook('6', 'test', new Params('http://test3', Priority::LOW, [])),
                ],
                5,
            ],
            [
                [],
                2,
                [
                    new Webhook('5', 'test', new Params('http://test1', Priority::HIGH, [])),
                    new Webhook('4', 'test', new Params('http://test2', Priority::NORMAL, ['call' => 'stop'])),
                    new Webhook('6', 'test', new Params('http://test3', Priority::LOW, [])),
                ],
                0,
            ],
        ];
    }
}
