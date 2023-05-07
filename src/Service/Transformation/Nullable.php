<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Transformation;

final class Nullable
{
    /**
     * @template Transformed
     * @param Transformed|null $transformed
     * @param Transformer<Transformed> $transformer
     * @return \stdClass|\JsonSerializable|null
     */
    public static function toApi(mixed $transformed, Transformer $transformer): \stdClass|\JsonSerializable|null
    {
        if($transformed === null) {
            return null;
        }

        return $transformer->toApi($transformed);
    }
}
