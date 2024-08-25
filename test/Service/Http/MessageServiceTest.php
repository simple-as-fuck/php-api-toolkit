<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Service\Http\MessageService;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;

#[CoversClass(MessageService::class)]
final class MessageServiceTest extends TestCase
{
    private HttpFactory $httpFactory;

    protected function setUp(): void
    {
        $this->httpFactory = new HttpFactory();
    }

    #[DataProvider('dataParseErrorMessage')]
    public function testParseErrorMessage(string $expectedErrorMessage, string $invalidContent): void
    {
        $this->expectExceptionMessage($expectedErrorMessage);

        $message = $this->httpFactory->createResponse()->withBody($this->httpFactory->createStream($invalidContent));
        MessageService::parseJsonFromBody(new UnexpectedValueException(), $message, 'Test body', false);
    }

    /**
     * @return non-empty-array<non-empty-array<string>>
     */
    public static function dataParseErrorMessage(): array
    {
        return [
            ['Test body must be valid json, invalid content: \'\'', ''],
            ['Test body must be valid json, invalid content: \'kjdfhgroigiosdiugaeiufsabdv\'', 'kjdfhgroigiosdiugaeiufsabdv'],
            ['Test body must be valid json, invalid content: \'kjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigi\' (truncated)', 'kjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdv'],
        ];
    }
}
