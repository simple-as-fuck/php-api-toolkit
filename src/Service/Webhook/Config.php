<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Webhook;

abstract class Config
{
    /**
     * @deprecated in 0.6 will be removed use $this->getDefaultHeaders
     * @return non-empty-string|null
     */
    abstract public function getBearerToken(): ?string;

    /**
     * @return array<string>
     */
    public function getDefaultHeaders(): array
    {
        /** @phpstan-ignore-next-line */
        $token = $this->getBearerToken();
        if ($token !== null) {
            return ['Authorization' => 'Bearer '.$token];
        }

        return [];
    }

    /**
     * @return positive-int
     */
    abstract public function getMaxTries(): int;

    /**
     * @return positive-int delay in seconds
     */
    abstract public function getDelayBetweenTries(): int;
}
