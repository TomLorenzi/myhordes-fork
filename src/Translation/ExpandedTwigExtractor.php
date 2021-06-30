<?php


namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use Iterator;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Translation\Extractor\PhpStringTokenParser;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;

class ExpandedTwigExtractor extends TwigExtractor
{
    private TranslationConfigGlobal $config;

    public function __construct(Environment $twig, TranslationConfigGlobal $config)
    {
        parent::__construct($twig);
        $this->config = $config;
    }

    /**
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function canBeExtracted(string $file)
    {
        if (!$this->config->useTwig() || !parent::canBeExtracted($file)) return false;
        return !$this->config->useFileNameMatching() || in_array(basename($file),$this->config->matchingFileNames());
    }

}