<?php

declare(strict_types=1);


use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Server\QueryRule;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException as UnexpectedValueException;
use SimpleAsFuck\Validator\Model\Validated as Validated;

/**
 * @covers \SimpleAsFuck\ApiToolkit\Model\Server\QueryRule
 */
final class QueryRuleTest extends TestCase
{
    private QueryRule $rule;

    public function setUp(): void
    {
        /** @var array<mixed> $queryData */
        $queryData = ['test' => 'someValue'];

        $this->rule = new QueryRule(new UnexpectedValueException(), new Validated($queryData));
    }

    public function testKey(): void
    {
        $value = $this->rule->key('test')->string()->notNull();
        self::assertSame('someValue', $value);

        $this->expectExceptionMessage('Request query parameter: testNotExist must be not null');
        $this->rule->key('testNotExist')->string()->notNull();
    }
}
