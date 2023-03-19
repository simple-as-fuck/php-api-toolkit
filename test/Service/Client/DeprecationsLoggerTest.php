<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Service\Client\Config;
use SimpleAsFuck\ApiToolkit\Service\Client\DeprecationsLogger;

/**
 * @covers \SimpleAsFuck\ApiToolkit\Service\Client\DeprecationsLogger
 */
final class DeprecationsLoggerTest extends TestCase
{
    /**
     * @dataProvider dataProviderLogDeprecation
     *
     * @param non-empty-string|null $expectedLogMessage
     * @param array<non-empty-string, string> $expectedContext
     */
    public function testLogDeprecation(?string $expectedLogMessage, array $expectedContext, ResponseInterface $response): void
    {
        $psrLogger = $this->createMock(LoggerInterface::class);
        if ($expectedLogMessage === null) {
            $psrLogger->expects(self::never())->method(self::anything());
        } else {
            $psrLogger->expects(self::once())->method('warning')->with($expectedLogMessage, $expectedContext);
        }

        $config = $this->createMock(Config::class);
        $config->method('getDeprecatedHeader')->willReturn('Deprecated');

        $httpFactory = new HttpFactory();

        $logger = new DeprecationsLogger($config, $psrLogger, $httpFactory);
        $logger->logDeprecation('test', (new Request('GET', '/'))->withBaseUrl('https://test'), $response);
    }

    /**
     * @return non-empty-array<array{non-empty-string|null, array<non-empty-string, string>, ResponseInterface}>
     */
    public function dataProviderLogDeprecation(): array
    {
        $httpFactory = new HttpFactory();

        return [
            [null, [], $httpFactory->createResponse()],
            [
                'Api: test method: GET uri: "https://test/" call is deprecated',
                [
                    'Sunset' => '2022-12-15T21:46:37+00:00',
                    'Link' => 'https://example.net/sunset',
                    'Deprecated' => 'Some deprecated description',
                ],
                $httpFactory->createResponse()
                    ->withHeader('Sunset', 'Thu, 15 Dec 2022 21:46:37 GMT')
                    ->withHeader('Link', '<https://example.net/sunset>;rel="sunset";type="text/html"')
                    ->withHeader('Deprecated', 'Some deprecated description')
                ,
            ],
            [
                'Api: test method: GET uri: "https://test/" call is deprecated',
                [
                    'Sunset' => 'Some sunset header with incorrect format',
                    'Link' => 'Some sunset link with incorrect format',
                ],
                $httpFactory->createResponse()
                    ->withHeader('Sunset', 'Some sunset header with incorrect format')
                    ->withHeader('Link', 'Some another link')
                    ->withHeader('Link', 'Some sunset link with incorrect format')
                ,
            ],
        ];
    }
}
