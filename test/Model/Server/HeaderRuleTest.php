<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Server\HeaderRule;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;
use SimpleAsFuck\Validator\Model\Validated;
use SimpleAsFuck\Validator\Rule\String\StringRule;

/**
 * @covers \SimpleAsFuck\ApiToolkit\Model\Server\HeaderRule
 */
final class HeaderRuleTest extends TestCase
{
    private HeaderRule $rule;

    protected function setUp(): void
    {
        $testData = [
            'header' => ['test'],
            'Header1' => ['test1'],
        ];
        /** @var array<array<string>> $testData */
        $this->rule = new HeaderRule(new UnexpectedValueException(), new Validated($testData));
    }

    /**
     * @dataProvider dataProviderKeyOf
     *
     * @param non-empty-string $key
     * @param array<string>|null $expectedValue
     */
    public function testKeyOf(?array $expectedValue, string $key, bool $caseSensitive): void
    {
        $headerValue = $this->rule->keyOf($key, fn (StringRule $rule): ?string => $rule->nullable(), $caseSensitive)->nullable();

        self::assertSame($expectedValue, $headerValue);
    }

    /**
     * @return non-empty-array<non-empty-array<mixed>>
     */
    public function dataProviderKeyOf(): array
    {
        return [
            [null, 'notFound', false],
            [null, 'notFound', true],
            [['test'], 'header', false],
            [['test'], 'Header', false],
            [['test1'], 'header1', false],
            [null, 'Header', true],
            [['test1'], 'Header1', true],
        ];
    }

    /**
     * @dataProvider dataProviderKey
     *
     * @param non-empty-string $key
     */
    public function testKey(?string $expectedValue, string $key, bool $caseSensitive): void
    {
        $headerValue = $this->rule->key($key, $caseSensitive)->nullable();

        self::assertSame($expectedValue, $headerValue);
    }

    /**
     * @return non-empty-array<non-empty-array<mixed>>
     */
    public function dataProviderKey(): array
    {
        return [
            [null, 'notFound', false],
            [null, 'notFound', true],
            ['test', 'header', false],
            ['test', 'Header', false],
            ['test1', 'header1', false],
            [null, 'Header', true],
            ['test1', 'Header1', true],
        ];
    }
}
