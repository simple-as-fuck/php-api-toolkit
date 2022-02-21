<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

use SimpleAsFuck\ApiToolkit\Service\Config\Repository;
use SimpleAsFuck\ApiToolkit\Service\Transformation\Transformer;

/**
 * @implements Transformer<\Throwable>
 */
class ExceptionTransformer implements Transformer
{
    private Repository $configRepository;

    public function __construct(Repository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @param \Throwable $transformed
     */
    public function toApi($transformed): \stdClass
    {
        $responseData = new \stdClass();
        $responseData->message = 'Internal server error';
        if ($this->configRepository->getServerConfig()->debug()) {
            $responseData->message = 'Exception ('.\get_class($transformed).') message: "'.$transformed->getMessage().'" from: "'.$transformed->getFile().'":'.$transformed->getLine();
        }

        return $responseData;
    }
}
