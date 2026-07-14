<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @param \Iterator<mixed> $streamedData
     */
    #[DataProvider('dataProviderMakeArray')]
    public function testMakeArray(string $expectedBody, \Iterator $streamedData): void
    {
        $response = ResponseFactory::makeArray($streamedData);

        self::assertSame($expectedBody, $response->getBody()->getContents());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderMakeArray(): array
    {
        return [
            ['[548846,"sadasjkfghjsg"]', new \ArrayIterator([548846, 'test' => 'sadasjkfghjsg'])],
            ['[548846,"sadasjkfghjsg"]', (static function (): \Generator {
                yield 548846;
                yield 'test' => 'sadasjkfghjsg';
            })()],
            ['[]', new \ArrayIterator()],
        ];
    }

    /**
     * @param \Iterator<array-key, mixed> $streamedData
     */
    #[DataProvider('dataProviderMakeArrayAssoc')]
    public function testMakeArrayAssoc(string $expectedBody, \Iterator $streamedData): void
    {
        $response = ResponseFactory::make()->withJson()->arrayAssoc()->of($streamedData);

        self::assertSame($expectedBody, $response->getBody()->getContents());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderMakeArrayAssoc(): array
    {
        return [
            ['{"0":548846,"test":"sadasjkfghjsg"}', new \ArrayIterator([548846, 'test' => 'sadasjkfghjsg'])],
            ['{"0":548846,"test":"sadasjkfghjsg"}', (static function (): \Generator {
                yield 548846;
                yield 'test' => 'sadasjkfghjsg';
            })()],
            ['{}', new \ArrayIterator()],
        ];
    }

    /**
     * @param \Iterator<mixed> $streamedData
     */
    #[DataProvider('dataMakeSteam')]
    public function testMakeSteam(string $expectedBody, \Iterator $streamedData): void
    {
        $response = ResponseFactory::makeStream($streamedData);

        self::assertSame($expectedBody, $response->getBody()->getContents());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataMakeSteam(): array
    {
        return [
            ["548846\n\"sadasjkfghjsg\"\n", new \ArrayIterator([548846, 'sadasjkfghjsg'])],
            ["548846\n\"sadasjkfghjsg\"\n", (static function (): \Generator {
                yield 548846;
                yield 'test' => 'sadasjkfghjsg';
            })()],
            ['', new \ArrayIterator()],
        ];
    }
}
