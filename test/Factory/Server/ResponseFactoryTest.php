<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @dataProvider dataProviderMakeArray
     *
     * @param array<mixed> $streamedData
     */
    public function testMakeArray(string $expectedBody, array $streamedData): void
    {
        $response = ResponseFactory::makeArray(new \ArrayIterator($streamedData));

        self::assertSame($expectedBody, $response->getBody()->getContents());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderMakeArray(): array
    {
        return [
            ['[548846,"sadasjkfghjsg"]', [548846, 'sadasjkfghjsg']],
            ['[]', []],
        ];
    }
}
