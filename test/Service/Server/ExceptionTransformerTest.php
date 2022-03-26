<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Service\Server\ExceptionTransformer;

/**
 * @covers \SimpleAsFuck\ApiToolkit\Service\Server\ExceptionTransformer
 */
final class ExceptionTransformerTest extends TestCase
{
    /**
     * @dataProvider dataProviderToApi
     */
    public function testToApi(string $expectedMessage, bool $debug): void
    {
        $config = $this->createMock(SimpleAsFuck\ApiToolkit\Service\Config\Repository::class);
        $config->method('getServerConfig')->willReturn(new \SimpleAsFuck\ApiToolkit\Model\Server\Config($debug));

        $transformer = new ExceptionTransformer($config);

        $exception = new \Exception('Test');

        $transformedData = $transformer->toApi($exception);

        self::assertSame($expectedMessage, $transformedData->message);
        if ($debug) {
            self::assertIsArray($transformedData->trace);
            foreach ($transformedData->trace as $item) {
                self::assertIsString($item);
                self::assertNotSame('', $item);
            }
        } else {
            self::assertObjectNotHasAttribute('trace', $transformedData);
        }
    }

    /**
     * @return array<array<mixed>>
     */
    public function dataProviderToApi(): array
    {
        return [
            ['Internal server error', false],
            ['Exception (Exception) message: \'Test\' from: '.__DIR__.'/ExceptionTransformerTest.php:23', true],
        ];
    }
}
