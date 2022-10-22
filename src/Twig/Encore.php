<?php


namespace App\Twig;

use Psr\Container\ContainerInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Encore extends AbstractExtension
{
    private EntrypointLookupCollectionInterface $collection;

    public function __construct(EntrypointLookupCollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encore_entry_all_js_files', [$this, 'getWebpackJsFiles']),
            new TwigFunction('encore_entry_all_css_files', [$this, 'getWebpackCssFiles']),
        ];
    }

    public function getWebpackJsFiles(string $entryName, string $entrypointName = '_default'): array
    {
        $entry = $this->getEntrypointLookup($entrypointName);
        $entry->reset();
        return $entry->getJavaScriptFiles($entryName);
    }

    public function getWebpackCssFiles(string $entryName, string $entrypointName = '_default'): array
    {
        $entry = $this->getEntrypointLookup($entrypointName);
        $entry->reset();
        return $entry->getCssFiles($entryName);
    }

    private function getEntrypointLookup(string $entrypointName): EntrypointLookupInterface
    {
        return $this->collection->getEntrypointLookup($entrypointName);
    }
}