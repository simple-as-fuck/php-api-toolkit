<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Service\Server\ExceptionTransformer;

#[CoversClass(ExceptionTransformer::class)]
final class ExceptionTransformerTest extends TestCase
{
    #[DataProvider('dataProviderToApi')]
    public function testToApi(string $expectedMessage, bool $debug): void
    {
        $config = $this->createMock(SimpleAsFuck\ApiToolkit\Service\Config\Repository::class);
        $config->method('isDebug')->willReturn($debug);

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
            self::assertNull($transformedData->trace ?? null);
        }
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderToApi(): array
    {
        return [
            ['Internal server error', false],
            ['Exception (Exception) message: \'Test\' from: '.__DIR__.'/ExceptionTransformerTest.php:23', true],
        ];
    }
}
