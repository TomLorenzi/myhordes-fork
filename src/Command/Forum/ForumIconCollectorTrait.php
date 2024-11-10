<?php

namespace App\Command\Forum;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @property KernelInterface $kernel
 */
trait ForumIconCollectorTrait
{
    /**
     * @return string[]
     */
    private function listAllIcons(string $default): array {
        $basePath = "{$this->kernel->getProjectDir()}/assets/img/forum/banner/";
        $files = array_map(
            fn(string $s) => substr($s, strlen($basePath)),
            array_filter([
                             ...glob("{$basePath}*.*"),
                             ...glob("{$basePath}**/*.*"),
                         ], fn(string $s) => is_file($s) && !str_ends_with('.', $s) )
        );

        return [
            $default,
            ...$files
        ];
    }

}