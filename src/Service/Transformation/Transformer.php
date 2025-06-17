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
     * @return object MUST contain public json serializable properties
     */
    public function toApi($transformed): object;
}
