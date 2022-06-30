<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @dataProvider dataProviderMakeJsonStream
     *
     * @param array<mixed> $streamedData
     */
    public function testMakeJsonStream(string $expectedBody, array $streamedData): void
    {
        $response = ResponseFactory::makeJsonStream(new \ArrayIterator($streamedData));

        self::assertSame($expectedBody, $response->getBody()->getContents());
    }

    /**
     * @return array<array<mixed>>
     */
    public function dataProviderMakeJsonStream(): array
    {
        return [
            ['[548846,"sadasjkfghjsg"]', [548846, 'sadasjkfghjsg']],
            ['[]', []],
        ];
    }
}
