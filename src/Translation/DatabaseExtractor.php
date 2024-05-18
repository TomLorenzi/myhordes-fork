<?php


namespace App\Translation;

use App\Entity\Season;
use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Structures\EventConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseExtractor implements ExtractorInterface
{
    private TranslationConfigGlobal $config;

    protected $prefix;
    protected EntityManagerInterface $em;
    protected ConfMaster $conf;

    protected static $has_been_run = false;

    public function __construct(EntityManagerInterface $em, TranslationConfigGlobal $config, ConfMaster $conf)
    {
        $this->em = $em;
        $this->config = $config;
        $this->conf = $conf;
    }

    private function insert(MessageCatalogue &$c, string $message, string $domain, string $class): void
    {
        if (!empty($message)) {
            $c->set($message, $this->prefix . $message, $domain);
            $this->config->add_source_for($message,$domain,'db',str_replace('App\\Entity\\', '', $class));
        }

    }

    /**
     * @inheritDoc
     */
    public function extract($resource, MessageCatalogue $c): void
    {
        if (!$this->config->useDatabase()) return;

        if (self::$has_been_run) return;
        self::$has_been_run = true;

        foreach ($this->em->getRepository(Season::class)->findAll() as $season) {
            /** @var Season $season */
            $this->insert( $c, "Saison {$season->getNumber()}.{$season->getSubNumber()}", 'season', Season::class );
        }

        foreach ($this->conf->getAllEventNames() as $event)
            if ($this->conf->eventIsPublic($event)) {
                $this->insert($c, "event_{$event}_title", 'events', EventConf::class);
                $this->insert($c, "event_{$event}_description", 'events', EventConf::class);
            }
    }

    /**
     * @inheritDoc
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}