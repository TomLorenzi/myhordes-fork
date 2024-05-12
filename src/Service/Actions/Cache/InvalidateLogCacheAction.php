<?php

namespace App\Service\Actions\Cache;

use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;

readonly class InvalidateLogCacheAction
{
    public function __construct(
        private InvalidateTagsInAllPoolsAction $invalidate,
        private CalculateBlockTimeAction $block,
    ) { }

    public function __invoke(TownLogEntry|\DateTimeInterface|Zone $source, int|Town|null $town = null): void
    {
        if (is_a($source, Zone::class)) ($this->invalidate)("logs__z{$source->getId()}");
        else {
            $block = ($this->block)(is_a($source, TownLogEntry::class) ? $source->getTimestamp() : $source);
            $tid = is_a($source, TownLogEntry::class) ? $source->getTown()?->getId() : (
            is_a($town, Town::class) ? $$town->getId() : null
            );
            ($this->invalidate)($tid ? "logs__{$tid}__{$block->getTimestamp()}" : "logs__{$block->getTimestamp()}");
        }

    }
}