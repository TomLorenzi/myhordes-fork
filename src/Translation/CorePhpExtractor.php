<?php


namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use Iterator;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\Extractor\PhpStringTokenParser;
use Symfony\Component\Translation\MessageCatalogue;

class CorePhpExtractor extends PhpExtractor
{

    private TranslationConfigGlobal $config;

    public function __construct(TranslationConfigGlobal $config)
    {
        echo "php created.\n";
        $this->config = $config;
    }

    /**
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function canBeExtracted(string $file)
    {
        if (!$this->config->usePHP() || !parent::canBeExtracted($file)) return false;
        return !$this->config->useFileNameMatching() || in_array(basename($file),$this->config->matchingFileNames());
    }

}