<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Transformation;

final class Collection
{
    /**
     * @template TValue
     * @param iterable<TValue> $transformed
     * @param Transformer<TValue> $transformer
     * @return array<int, \stdClass|\JsonSerializable>
     */
    public static function toApi(iterable $transformed, Transformer $transformer): array
    {
        $apiArray = [];
        foreach ($transformed as $value) {
            $apiArray[] = $transformer->toApi($value);
        }
        return $apiArray;
    }
}
