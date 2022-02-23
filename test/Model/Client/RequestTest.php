<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Client\Request;

final class RequestTest extends TestCase
{
    public function testWithJson(): void
    {
        $request = new Request('POST', '/test');
        $request = $request->withBaseUrl('http://host');
        $requestWithJson = $request->withJson(['test' => 'test'], null);

        $factory = new HttpFactory();

        self::assertSame('', $request->createPsr($factory)->getBody()->getContents());
        self::assertSame('{"test":"test"}', $requestWithJson->createPsr($factory)->getBody()->getContents());
    }

    public function testWithBaseUrl(): void
    {
        $request = new Request('GET', '/test', ['test' => 'test'], null, ['test' => 'test']);
        $requestWithBaseUrl = $request->withBaseUrl('http://host');
        $psrRequest = $requestWithBaseUrl->createPsr(new HttpFactory());

        self::assertSame(false, $request->hasBaseUrl());
        self::assertSame(true, $requestWithBaseUrl->hasBaseUrl());
        self::assertSame('GET', $psrRequest->getMethod());
        self::assertSame('http://host/test?test=test', (string) $psrRequest->getUri());
        self::assertSame('', $psrRequest->getBody()->getContents());
        self::assertSame(['test'], $psrRequest->getHeader('test'));
    }
}
