<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider as DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Data\Common\ProblemDetail;
use SimpleAsFuck\ApiToolkit\Service\Common\ProblemDetailTransformer;
use SimpleAsFuck\Validator\Rule\Object\ObjectRule;

#[CoversClass(ProblemDetailTransformer::class)]
final class ProblemDetailTransformerTest extends TestCase
{
    #[DataProvider('data')]
    public function test(ProblemDetail $problemDetail): void
    {
        $transformer = new ProblemDetailTransformer();

        $transformed = $transformer->validate(ObjectRule::make($transformer->toApi($problemDetail)));
        self::assertNotSame($problemDetail, $transformed);
        self::assertEquals($problemDetail, $transformed);
    }

    /**
     * @return non-empty-array<non-empty-array<mixed>>
     */
    public static function data(): array
    {
        return [
            [new ProblemDetail(null, null, null, null, null)],
            [new ProblemDetail('https://test/internal-error', 500, 'Internal systet errro', 'Please retry action', '/some/instance')],
        ];
    }
}
