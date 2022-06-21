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
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseExtractor implements ExtractorInterface
{
    private TranslationConfigGlobal $config;
    private ConfMaster $confMaster;

    protected $prefix;
    protected $em;

    protected static $has_been_run = false;

    public function __construct(EntityManagerInterface $em, TranslationConfigGlobal $config, ConfMaster $confMaster)
    {
        $this->em = $em;
        $this->config = $config;
        $this->confMaster = $confMaster;
    }

    private function insert(MessageCatalogue &$c, string $message, string $domain, string $class) {
        if (!empty($message)) {
            $c->set($message, $this->prefix . $message, $domain);
            $this->config->add_source_for($message,$domain,'db',str_replace('App\\Entity\\', '', $class));
        }

    }

    /**
     * @inheritDoc
     */
    public function extract($resource, MessageCatalogue $c)
    {
        if (self::$has_been_run) return;
        self::$has_been_run = true;

        //<editor-fold desc="Item Domain">
        // Get item labels
        foreach ($this->em->getRepository(ItemPrototype::class)->findAll() as $item) {
            /** @var ItemPrototype $item */
            $this->insert($c, $item->getLabel(), 'items', ItemPrototype::class);
            $this->insert($c, $item->getDescription(), 'items', ItemPrototype::class);
            if ($item->getDecoText()) $this->insert($c, $item->getDecoText(), 'items', ItemPrototype::class);
        }

        // Get Action labels and messages as well as requirement messages
        foreach ($this->em->getRepository(ItemAction::class)->findAll() as $action) {
            /** @var ItemAction $action */
            $this->insert($c, $action->getLabel(), 'items', ItemAction::class);
            if (!empty($action->getTooltip()))
                $this->insert($c, $action->getTooltip(), 'items', ItemAction::class);
            if (!empty($action->getConfirmMsg()))
                $this->insert($c, $action->getConfirmMsg(), 'items', ItemAction::class);
            if (!empty($action->getMessage()))
                $this->insert($c, $action->getMessage(), 'items', ItemAction::class);
            if (!empty($action->getEscortMessage()))
                $this->insert($c, $action->getEscortMessage(), 'items', ItemAction::class);
            foreach ($action->getRequirements() as $requirement) {
                if ($requirement->getFailureText())
                    $this->insert($c, $requirement->getFailureText(), 'items', Requirement::class);
            }
        }

        // Get the escort action labels and tooltips
        foreach ($this->em->getRepository(EscortActionGroup::class)->findAll() as $escort_action) {
            $this->insert($c, $escort_action->getLabel(), 'items', EscortActionGroup::class);
            $this->insert($c, $escort_action->getTooltip(), 'items', EscortActionGroup::class);
        }

        foreach ($this->em->getRepository(Recipe::class)->findAll() as $recipe)
            /** @var Recipe $recipe */
            if ($recipe->getAction())
                $this->insert( $c, $recipe->getAction(), 'items', Recipe::class );

        foreach ($this->em->getRepository(ItemCategory::class)->findRootCategories() as $itemCategory)
            /** @var ItemCategory $itemCategory */
            if ($itemCategory->getLabel())
                $this->insert( $c, $itemCategory->getLabel(), 'items', ItemCategory::class );

        foreach ($this->em->getRepository(AffectMessage::class)->findAll() as $affectMessage)
            /** @var AffectMessage $affectMessage */
            if ($affectMessage->getText())
                $this->insert( $c, $affectMessage->getText(), 'items', AffectMessage::class );

        foreach ($this->em->getRepository(FeatureUnlockPrototype::class)->findAll() as $feature) {
            /** @var FeatureUnlockPrototype $feature */
            $this->insert( $c, $feature->getLabel(), 'items', FeatureUnlockPrototype::class );
            $this->insert( $c, $feature->getDescription(), 'items', FeatureUnlockPrototype::class );
        }

        //</editor-fold>

        //<editor-fold desc="Building Domain">
        // Get building labels and upgrade descriptions
        foreach ($this->em->getRepository(BuildingPrototype::class)->findAll() as $building) {
            /** @var BuildingPrototype $building */
            $this->insert( $c, $building->getLabel(), 'buildings', BuildingPrototype::class );
            if($building->getDescription())
                $this->insert( $c, $building->getDescription(), 'buildings', BuildingPrototype::class );
            if ($building->getUpgradeTexts())
                foreach ($building->getUpgradeTexts() as $text)
                    $this->insert( $c, $text, 'buildings', BuildingPrototype::class );
            if ($building->getZeroLevelText())
                $this->insert( $c, $building->getZeroLevelText(), 'buildings', BuildingPrototype::class );
        }

        // Get home upgrade labels
        foreach ($this->em->getRepository(CitizenHomePrototype::class)->findAll() as $upgrade)
            /** @var CitizenHomePrototype $upgrade */
            $this->insert( $c, $upgrade->getLabel(), 'buildings', CitizenHomePrototype::class );

        // Get home extension labels
        foreach ($this->em->getRepository(CitizenHomeUpgradePrototype::class)->findAll() as $extension) {
            /** @var CitizenHomeUpgradePrototype $extension */
            $this->insert($c, $extension->getLabel(), 'buildings', CitizenHomeUpgradePrototype::class);
            $this->insert($c, $extension->getDescription(), 'buildings', CitizenHomeUpgradePrototype::class);
        }
        //</editor-fold>

        //<editor-fold desc="Game Domain">
        foreach ($this->em->getRepository(CitizenStatus::class)->findAll() as $status) {
            /** @var CitizenStatus $status */
            if (!$status->getHidden() && $status->getLabel())
                $this->insert( $c, $status->getLabel(), 'game', CitizenStatus::class );

            if (!$status->getHidden() && $status->getDescription())
                $this->insert( $c, $status->getDescription(), 'game', CitizenStatus::class );
        }

        foreach ($this->em->getRepository(CauseOfDeath::class)->findAll() as $causeOfDeath){
            /** @var CitizenStatus $status */
            if ($causeOfDeath->getLabel())
                $this->insert( $c, $causeOfDeath->getLabel(), 'game', CauseOfDeath::class );

            if ($causeOfDeath->getDescription())
                $this->insert( $c, $causeOfDeath->getDescription(), 'game', CauseOfDeath::class );
        }

        foreach ($this->em->getRepository(CitizenProfession::class)->findAll() as $profession) {
            /** @var CitizenProfession $profession */
            if ($profession->getLabel())
                $this->insert( $c, $profession->getLabel(), 'game', CitizenProfession::class );
            if ($profession->getDescription())
                $this->insert( $c, $profession->getDescription(), 'game', CitizenProfession::class );
        }

        foreach ($this->em->getRepository(CitizenRole::class)->findAll() as $role) {
            /** @var CitizenRole $role */
            if ($role->getLabel())
                $this->insert( $c, $role->getLabel(), 'game', CitizenRole::class );
            if ($role->getMessage())
                $this->insert( $c, $role->getMessage(), 'game', CitizenRole::class );

        }

        foreach ($this->em->getRepository(ZonePrototype::class)->findAll() as $zone) {
            /** @var ZonePrototype $zone */
            if ($zone->getLabel())
                $this->insert( $c, $zone->getLabel(), 'game', ZonePrototype::class );

            if ($zone->getDescription())
                $this->insert( $c, $zone->getDescription(), 'game', ZonePrototype::class );

            if ($zone->getExplorableDescription())
                $this->insert( $c, $zone->getExplorableDescription(), 'game', ZonePrototype::class );
        }

        foreach ($this->em->getRepository(ZoneTag::class)->findAll() as $zone) {
            /** @var ZonePrototype $zone */
            if ($zone->getLabel())
                $this->insert( $c, $zone->getLabel(), 'game', ZoneTag::class );
        }

        foreach ($this->em->getRepository(TownClass::class)->findAll() as $town) {
            /** @var TownClass $town */
            if ($town->getLabel())
                $this->insert( $c, $town->getLabel(), 'game', TownClass::class );
            if ($town->getHelp())
                $this->insert( $c, $town->getHelp(), 'game', TownClass::class );
        }


        foreach ($this->em->getRepository(PictoPrototype::class)->findAll() as $pictoPrototype) {
            /** @var PictoPrototype $pictoPrototype */
            if ($pictoPrototype->getLabel())
                $this->insert( $c, $pictoPrototype->getLabel(), 'game', PictoPrototype::class );

            if ($pictoPrototype->getDescription())
                $this->insert( $c, $pictoPrototype->getDescription(), 'game', PictoPrototype::class );
        }

        foreach ($this->em->getRepository(AwardPrototype::class)->findAll() as $awardPrototype) {
            /** @var AwardPrototype $awardPrototype */
            if ($awardPrototype->getTitle())
                $this->insert( $c, $awardPrototype->getTitle(), 'game', AwardPrototype::class );
        }

        foreach ($this->em->getRepository(LogEntryTemplate::class)->findAll() as $logtemplate)
            /** @var LogEntryTemplate $logtemplate */
            if ($logtemplate->getText())
                $this->insert( $c, $logtemplate->getText(), 'game', LogEntryTemplate::class );

        foreach ($this->em->getRepository(GazetteEntryTemplate::class)->findAll() as $gazetteTemplate)
            /** @var GazetteEntryTemplate $gazetteTemplate */
            if ($gazetteTemplate->getText())
                $this->insert( $c, $gazetteTemplate->getText(), 'gazette', GazetteEntryTemplate::class );

        foreach ($this->em->getRepository(HeroSkillPrototype::class)->findAll() as $heroSkill) {
            /** @var HeroSkillPrototype $heroSkill */
            if ($heroSkill->getTitle())
                $this->insert( $c, $heroSkill->getTitle(), 'game', HeroSkillPrototype::class );

            if ($heroSkill->getDescription())
                $this->insert( $c, $heroSkill->getDescription(), 'game', HeroSkillPrototype::class );
        }

        foreach ($this->em->getRepository(ComplaintReason::class)->findAll() as $reason) {
            /** @var ComplaintReason $reason */
            if ($reason->getText())
                $this->insert( $c, $reason->getText(), 'game', ComplaintReason::class );
        }

        foreach ($this->em->getRepository(ThreadTag::class)->findAll() as $tag) {
            /** @var ThreadTag $tag */
            $this->insert( $c, $tag->getLabel(), 'global', ThreadTag::class );
        }

        foreach ($this->em->getRepository(TownClass::class)->findAll() as $town)
            /** @var TownClass $town */
            if ($town->getLabel())
                $this->insert( $c, $town->getLabel(), 'soul', TownClass::class );

        foreach ($this->em->getRepository(Season::class)->findAll() as $season) {
            /** @var Season $season */
            $this->insert( $c, "Saison {$season->getNumber()}.{$season->getSubNumber()}", 'season', Season::class );
        }

        foreach ($this->em->getRepository(CouncilEntryTemplate::class)->findAll() as $councilTemplate)
            /** @var CouncilEntryTemplate $councilTemplate */
            if ($councilTemplate->getText())
                $this->insert( $c, $councilTemplate->getText(), 'council', CouncilEntryTemplate::class );
        //</editor-fold>

        //<editor-fold desc="Global Domain">
        $langs = $this->confMaster->getGlobalConf()->get(MyHordesConf::CONF_LANGS);
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