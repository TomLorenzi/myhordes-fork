<?php

namespace App\Translation;

use App\Enum\Configuration\MyHordesSetting;
use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Service\Translation\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class ConfigExtractor implements ExtractorInterface
{
    protected string $prefix;
    protected EntityManagerInterface $em;
    protected ConfMaster $confMaster;
    protected TranslationConfigGlobal $config;

    private ?MessageCatalogue $catalogue = null;
    protected static bool $has_been_run = false;

    public function __construct(EntityManagerInterface $em, ConfMaster $confMaster, TranslationConfigGlobal $config, TranslationService $trans)
    {
        $this->em = $em;
        $this->confMaster = $confMaster;
        $this->config = $config;
        $this->catalogue = $config->skipExistingMessages() ? $trans->getMessageSubCatalogue(bundle: false, locale: 'de') : null;
    }

    private function insert(MessageCatalogue &$c, string $message, string $domain, string $file): void
    {
        if (!empty($message) && !$this->catalogue?->has( $message, $domain )) {
            $c->set($message, $this->prefix . $message, $domain);
            $this->config->add_source_for($message,$domain,'config',$file);
        }
    }

    /**
     * @inheritDoc
     */
    public function extract($resource, MessageCatalogue $c): void
    {
        if (!$this->config->useConfig()) return;

        if (self::$has_been_run) return;
        self::$has_been_run = true;

        //<editor-fold desc="Global Domain">
        $langs = $this->confMaster->getGlobalConf()->get(MyHordesSetting::Languages);
        foreach ($langs as $lang) {
            $this->insert($c, $lang['label'], 'global', 'app/myhordes.yml');
            if (!empty($lang['tooltip']))
                $this->insert($c, $lang['tooltip'], 'global', 'app/myhordes.yml');
        }
        //</editor-fold>
    }

    /**
     * @inheritDoc
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}