<?php

declare(strict_types=1);

namespace Test\Factory\Symfony;

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Symfony\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @dataProvider dataProviderMakeJsonStream
     *
     * @param iterable<mixed> $streamedData
     */
    public function testMakeJsonStream(string $expectedBody, iterable $streamedData): void
    {
        $response = ResponseFactory::makeJsonStream($streamedData);

        ob_start();
        $response->sendContent();
        $responseContent = ob_get_clean();

        self::assertSame($expectedBody, $responseContent);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProviderMakeJsonStream(): array
    {
        return [
            ['[548846,"sadasjkfghjsg"]', [548846, 'sadasjkfghjsg']],
            ['[]', new \ArrayIterator([])],
        ];
    }
}
