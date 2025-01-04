<?php

namespace MyHordes\Fixtures\Service;

use Adbar\Dot;
use ArrayHelpers\Arr;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;
use Symfony\Component\Yaml\Yaml;

class YamlDataService implements FixtureProcessorInterface {

    private readonly ?string $base_path;

    public function __construct(array $data)
    {
        $path = Arr::get($data, 'MyHordesFixturesBundle.path');
        $this->base_path = (!$path || !is_dir($path) || !is_dir("$path/content"))
            ? null
            : "$path/content";
    }

    public function process(array &$data, ?string $tag = null): void
    {
        if ($this->base_path === null || $tag === null) return;
        $target_path = $this->base_path . "/" . str_replace('.', '/', $tag) . ".yaml";

        if (!file_exists($target_path) || !is_readable($target_path)) return;

        $content = Yaml::parseFile($target_path);
        if (!$content || !is_array($content)) return;

        $data = (new Dot([
            ...(new Dot($data))->flatten(),
            ...(new Dot($content))->flatten(),
        ], true))->all();
    }
}