<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Service\Common\JsonService;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;

#[CoversClass(JsonService::class)]
final class JsonServiceTest extends TestCase
{
    private HttpFactory $factory;

    public function setUp(): void
    {
        $this->factory = new HttpFactory();
    }

    #[DataProvider('dataJsonDecodeError')]
    public function testJsonDecodeError(string $expectedErrorMessage, string $invalidContent): void
    {
        $this->expectExceptionMessage($expectedErrorMessage);

        JsonService::jsonDecode($invalidContent, 'Test body', new UnexpectedValueException());
    }

    /**
     * @return non-empty-array<non-empty-array<string>>
     */
    public static function dataJsonDecodeError(): array
    {
        return [
            ['Test body must be valid json (Syntax error), invalid content: \'\'', ''],
            ['Test body must be valid json (Syntax error), invalid content: \'kjdfhgroigiosdiugaeiufsabdv\'', 'kjdfhgroigiosdiugaeiufsabdv'],
            ['Test body must be valid json (Syntax error), invalid content: \'kjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigi\' (truncated)', 'kjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdvkjdfhgroigiosdiugaeiufsabdv'],
        ];
    }

    /**
     * @param array<mixed> $expectedData
     */
    #[DataProvider('dataJsonlDecode')]
    public function testJsonlDecode(
        array   $expectedData,
        string  $jsonl,
        bool    $allowInvalidJson = false,
        ?string $expectedErrorMessage = null,
    ): void {
        if ($expectedErrorMessage !== null) {
            $this->expectExceptionMessage($expectedErrorMessage);
        }

        $data = [];
        $stream = JsonService::jsonlDecode($this->factory->createStream($jsonl), allowInvalidJson: $allowInvalidJson);
        while ($stream->valid()) {
            $stream->current();
            $stream->next();
        }
        foreach ($stream as $item) {
        }
        $stream->rewind();
        while ($stream->valid()) {
            $stream->current();
            $stream->next();
        }
        foreach ($stream as $lineNumber => $value) {
            $data[$lineNumber] = $value->nullable();
        }

        self::assertEquals($expectedData, $data);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataJsonlDecode(): array
    {
        return [
            [[], ''],
            [[], '', true],

            [[], ' ', false, 'Stream content value 1 must be valid json (Syntax error), invalid content: \' \''],
            [[1 => null], ' ', true],
            [[], "\n", false, 'Stream content value 1 must be valid json (Syntax error), invalid content: \'\''],
            [[1 => null], "\n", true],
            [[], "1\n{\"test\":5}\n fuck \n[8.9]", false, 'Stream content value 3 must be valid json (Syntax error), invalid content: \' fuck \''],
            [[1 => 1, (object)['test' => 5], null, [8.9]], "1\n{\"test\":5}\n fuck \n[8.9 ]", true],
            [[], "5\n\n9", false, 'Stream content value 2 must be valid json (Syntax error), invalid content: \'\''],
            [[1 => 5, 2 => null, 3 => 9], "5\n\n9", true],

            [[1 => 1, 2 => 9], "1\n9"],
            [[1 => 3, 2 => '5', 3 => 9], "3\n\"5\"\n9\n"],
            [[1 => [], 2 => false, 3 => 9], "[]\nfalse\n9"],
            [[1 => null, 2 => false, 3 => (object)[]], "null\nfalse\n{}\n"],
            [[1 => 5.9, 2 => 'ahoj', [5, 8]], " 5.9\n \"ahoj\" \r\n[5, 8]"],
            [[1 => 8973, 'ahoj hi', 'jkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdi'], "8973\n\"ahoj hi\"\n\"jkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdijkfgshdkjfhdsghhfdigfsdi\"\n"],
        ];
    }

    #[DataProvider('dataJsonlDecodeError')]
    public function testJsonlDecodeError(string $expectedErrorMessage, string $invalidContent): void
    {
        $this->expectExceptionMessage($expectedErrorMessage);

        $stream = JsonService::jsonlDecode($this->factory->createStream($invalidContent));
        foreach ($stream as $item) {
            $item->int()->positive()->max(500)->notNull();
        }
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataJsonlDecodeError(): array
    {
        return [
            ['Stream content value 1 json must be not null', "null\n"],
            ['Stream content value 1 json must be integer, object given', "{\"test\":5}\n"],
            ['Stream content value 2 json must have minimum value: 1', "8\n0\n"],
            ['Stream content value 3 json must have maximum value: 500', "8\n65\n6455\n"],
        ];
    }
}
