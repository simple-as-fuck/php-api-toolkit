<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Factory\Server\Response;

final class ResponseTest extends TestCase
{
    public function testMakeJsonStream(): void
    {
        $response = Response::makeJsonStream(new \ArrayIterator([548846, 'sadasjkfghjsg']));

        self::assertSame('[548846,"sadasjkfghjsg"]', $response->getBody()->getContents());
    }
}
