<?php

namespace App\Service\Actions\Cache;

use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class InvalidateTagsInAllPoolsAction
{
    public function __construct(
        private TagAwareCacheInterface $gameCachePool,
        private TagAwareCacheInterface $twigCache,
    ) { }

    public function __invoke(string|array $tag): void
    {
        $tags = is_array($tag) ? $tag : [$tag];
        try {
            $this->gameCachePool->invalidateTags($tags);
            $this->twigCache->invalidateTags($tags);
        } catch (\Throwable) {}
    }
}