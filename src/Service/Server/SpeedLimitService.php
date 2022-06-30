<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Service\Server;

final class SpeedLimitService
{
    /**
     * @param float $speedLimit speed limit in KB/s for sending response slow down, zero means no slow down (this is not precise but is something)
     * @param positive-int $batchSize size of sended batch in bytes
     * @param float $batchSendingStartAt microtime when sending the batch started
     */
    public static function slowdownDataSending(float $speedLimit, int $batchSize, float $batchSendingStartAt): void
    {
        if ($speedLimit > 0) {
            $timeLimit = ($batchSize / 1024) / $speedLimit;
            $timeOverHead = $timeLimit - (\microtime(true) - $batchSendingStartAt);
            if ($timeOverHead > 0) {
                \usleep((int) ($timeOverHead * 1000000));
            }
        }
    }
}
