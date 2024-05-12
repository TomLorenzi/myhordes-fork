<?php

namespace App\Service\Actions\Cache;

use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class CalculateBlockTimeAction
{
    public function __construct(
    ) { }

    public function __invoke(\DateTimeInterface $time, int $interval = 600): \DateTime {
        $base = (clone $time)->modify('today')->getTimestamp();
        $offset = (float)(($time->getTimestamp()) - $base) / (float)$interval;
        return (new \DateTime())->setTimestamp($base + round(floor($offset) * $interval));
    }
}