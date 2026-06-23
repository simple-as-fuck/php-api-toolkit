<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Transformation;

final class NotNull
{
    /**
     * @template Transformed
     * @param Transformed $transformed
     * @param Transformer<Transformed>|null $transformer
     * @return ($transformer is Transformer<Transformed> ? object : mixed) MUST contain json serializable values
     */
    public static function toApi(mixed $transformed, ?Transformer $transformer): mixed
    {
        if ($transformer === null) {
            return $transformed;
        }

        return $transformer->toApi($transformed);
    }
}
