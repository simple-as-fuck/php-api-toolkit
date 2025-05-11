<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use Kayex\HttpCodes;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

/**
 * @implements Transformer<\Throwable>
 */
class ExceptionTransformer implements Transformer
{
    public function __construct(
        private readonly Repository $configRepository,
    ) {
    }

    /**
     * @param \Throwable $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = new \stdClass();
        $responseData->message = 'Internal server error';
        $responseData->status = HttpCodes::HTTP_INTERNAL_SERVER_ERROR;
        if ($this->configRepository->isDebug()) {
            $responseData->message = 'Exception ('.\get_class($transformed).') message: \''.$transformed->getMessage().'\' from: '.$transformed->getFile().':'.$transformed->getLine();
            $responseData->trace = array_map(fn (array $item): string => ($item['file'] ?? '-').':'.($item['line'] ?? '-'), $transformed->getTrace());
        }

        return $responseData;
    }
}
