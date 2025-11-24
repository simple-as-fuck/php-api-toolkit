<?php

declare(strict_types=1);

namespace Test\Factory\Symfony;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @param iterable<mixed> $streamedData
     */
    #[DataProvider('dataProviderMakeArray')]
    public function testMakeArray(string $expectedBody, iterable $streamedData): void
    {
        $response = ResponseFactory::makeArray($streamedData);

        ob_start();
        $response->sendContent();
        $responseContent = ob_get_clean();

        self::assertSame($expectedBody, $responseContent);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderMakeArray(): array
    {
        return [
            ['[548846,"sadasjkfghjsg"]', [548846, 'sadasjkfghjsg']],
            ['[]', new \ArrayIterator([])],
        ];
    }

    /**
     * @param array<mixed> $streamedData
     */
    #[DataProvider('dataMakeSteam')]
    public function testMakeSteam(string $expectedBody, iterable $streamedData): void
    {
        $response = ResponseFactory::makeStream($streamedData);

        ob_start();
        $response->sendContent();
        $responseContent = ob_get_clean();

        self::assertSame($expectedBody, $responseContent);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataMakeSteam(): array
    {
        return [
            ["548846\n\"sadasjkfghjsg\"\n", [548846, 'sadasjkfghjsg']],
            ['', []],
        ];
    }
}
