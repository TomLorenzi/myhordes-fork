<?php

namespace App\Translation;

use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Structures\Conf;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class ConfigExtractor implements ExtractorInterface
{
    protected string $prefix;
    protected EntityManagerInterface $em;
    protected ConfMaster $confMaster;
    protected TranslationConfigGlobal $config;

    protected static $has_been_run = false;

    public function __construct(EntityManagerInterface $em, ConfMaster $confMaster, TranslationConfigGlobal $config)
    {
        $this->em = $em;
        $this->confMaster = $confMaster;
        $this->config = $config;
    }

    private function insert(MessageCatalogue &$c, string $message, string $domain, string $file) {
        if (!empty($message)) {
            $c->set($message, $this->prefix . $message, $domain);
            $this->config->add_source_for($message,$domain,'config',$file);
        }
    }

    /**
     * @inheritDoc
     */
    public function extract($resource, MessageCatalogue $c)
    {
        if (self::$has_been_run) return;
        self::$has_been_run = true;

        //<editor-fold desc="Global Domain">
        $langs = $this->confMaster->getGlobalConf()->get(MyHordesConf::CONF_LANGS);
        foreach ($langs as $lang) {
            $this->insert($c, $lang['label'], 'global', 'app/mhordes.yml');
            if (!empty($lang['tooltip']))
                $this->insert($c, $lang['tooltip'], 'global', 'app/mhordes.yml');
        }
        //</editor-fold>
    }

    /**
     * @inheritDoc
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }
}