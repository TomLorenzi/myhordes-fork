<?php


namespace App\Translation;

use App\Entity\AffectMessage;
use App\Entity\AwardPrototype;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ComplaintReason;
use App\Entity\CouncilEntryTemplate;
use App\Entity\EscortActionGroup;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\GazetteEntryTemplate;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\ItemCategory;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\Requirement;
use App\Entity\Season;
use App\Entity\ThreadTag;
use App\Entity\TownClass;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
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