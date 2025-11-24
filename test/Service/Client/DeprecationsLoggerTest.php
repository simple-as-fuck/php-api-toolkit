<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;
use SimpleAsFuck\ApiToolkit\Service\Client\Config;
use SimpleAsFuck\ApiToolkit\Service\Client\DeprecationsLogger;

#[CoversClass(DeprecationsLogger::class)]
final class DeprecationsLoggerTest extends TestCase
{
    /**
     * @param non-empty-string|null $expectedLogMessage
     * @param array<non-empty-string, string> $expectedContext
     */
    #[DataProvider('dataProviderLogDeprecation')]
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

        $logger = new DeprecationsLogger($config, $psrLogger);
        $logger->logDeprecation('test', (new Request('GET', '/')), $response);
    }

    /**
     * @return non-empty-array<array{non-empty-string|null, array<non-empty-string, string>, ResponseInterface}>
     */
    public static function dataProviderLogDeprecation(): array
    {
        return [
            [null, [], new Response()],
            [
                'Api: test method: GET url: "/" call is deprecated',
                [
                    'Sunset' => '2022-12-15T21:46:37+00:00',
                    'Link' => 'https://example.net/sunset',
                    'Deprecated' => 'Some deprecated description',
                ],
                new Response(
                    headers: [
                        'Sunset' => 'Thu, 15 Dec 2022 21:46:37 GMT',
                        'Link' => '<https://example.net/sunset>;rel="sunset";type="text/html"',
                        'Deprecated' => 'Some deprecated description',
                    ],
                ),
            ],
            [
                'Api: test method: GET url: "/" call is deprecated',
                [
                    'Sunset' => 'Some sunset header with incorrect format',
                    'Link' => 'Some sunset link with incorrect format',
                ],
                new Response(
                    headers: [
                        'Sunset' => 'Some sunset header with incorrect format',
                        'Link' => 'Some sunset link with incorrect format',
                    ],
                ),
            ],
        ];
    }
}
