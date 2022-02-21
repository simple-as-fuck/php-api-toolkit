<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Server\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    public function testMakeJsonStream(): void
    {
        $response = ResponseFactory::makeJsonStream(new \ArrayIterator([548846, 'sadasjkfghjsg']));

        self::assertSame('[548846,"sadasjkfghjsg"]', $response->getBody()->getContents());
    }
}
