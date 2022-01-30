<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Transformation;

/**
 * @template Transformed
 */
interface Transformer
{
    /**
     * @param Transformed $transformed
     * @return \stdClass|\JsonSerializable
     */
    public function toApi($transformed);
}
