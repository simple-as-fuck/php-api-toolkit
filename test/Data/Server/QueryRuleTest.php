<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Data\Server\QueryRule;
use SimpleAsFuck\Validator\Factory\UnexpectedValueException;
use SimpleAsFuck\Validator\Model\Validated;

#[CoversClass(QueryRule::class)]
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
