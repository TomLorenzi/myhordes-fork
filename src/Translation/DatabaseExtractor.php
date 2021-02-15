<?php


namespace App\Translation;

use App\Entity\AffectMessage;
use App\Entity\AwardPrototype;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ComplaintReason;
use App\Entity\EscortActionGroup;
use App\Entity\GazetteEntryTemplate;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\ItemCategory;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\Season;
use App\Entity\TownClass;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseExtractor implements ExtractorInterface
{
    protected $prefix;
    protected $em;

    protected static $has_been_run = false;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function insert(MessageCatalogue &$c, string $message, string $domain) {
        if (!empty($message))
            $c->set( $message, $this->prefix . $message, $domain );
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
            $this->insert($c, $item->getLabel(), 'items');
            $this->insert($c, $item->getDescription(), 'items');
        }

        // Get Action labels and messages as well as requirement messages
        foreach ($this->em->getRepository(ItemAction::class)->findAll() as $action) {
            /** @var ItemAction $action */
            $this->insert($c, $action->getLabel(), 'items');
            if (!empty($action->getTooltip()))
                $this->insert($c, $action->getTooltip(), 'items');
            if (!empty($action->getMessage()))
                $this->insert($c, $action->getMessage(), 'items');
            foreach ($action->getRequirements() as $requirement) {
                if ($requirement->getFailureText())
                    $this->insert($c, $requirement->getFailureText(), 'items');
            }
        }

        // Get the escort action labels
        foreach ($this->em->getRepository(EscortActionGroup::class)->findAll() as $escort_action)
            $this->insert($c, $escort_action->getLabel(), 'items');

        foreach ($this->em->getRepository(Recipe::class)->findAll() as $recipe)
            /** @var Recipe $recipe */
            if ($recipe->getAction())
                $this->insert( $c, $recipe->getAction(), 'items' );

        foreach ($this->em->getRepository(ItemCategory::class)->findRootCategories() as $itemCategory)
            /** @var ItemCategory $itemCategory */
            if ($itemCategory->getLabel())
                $this->insert( $c, $itemCategory->getLabel(), 'items' );

        foreach ($this->em->getRepository(AffectMessage::class)->findAll() as $affectMessage)
            /** @var AffectMessage $affectMessage */
            if ($affectMessage->getText())
                $this->insert( $c, $affectMessage->getText(), 'items' );

        //</editor-fold>

        //<editor-fold desc="Building Domain">
        // Get building labels and upgrade descriptions
        foreach ($this->em->getRepository(BuildingPrototype::class)->findAll() as $building) {
            /** @var BuildingPrototype $building */
            $this->insert( $c, $building->getLabel(), 'buildings' );
            if($building->getDescription())
                $this->insert( $c, $building->getDescription(), 'buildings' );
            if ($building->getUpgradeTexts())
                foreach ($building->getUpgradeTexts() as $text)
                    $this->insert( $c, $text, 'buildings' );
        }

        // Get home upgrade labels
        foreach ($this->em->getRepository(CitizenHomePrototype::class)->findAll() as $upgrade)
            /** @var CitizenHomePrototype $upgrade */
            $this->insert( $c, $upgrade->getLabel(), 'buildings' );

        // Get home extension labels
        foreach ($this->em->getRepository(CitizenHomeUpgradePrototype::class)->findAll() as $extension) {
            /** @var CitizenHomeUpgradePrototype $extension */
            $this->insert($c, $extension->getLabel(), 'buildings');
            $this->insert($c, $extension->getDescription(), 'buildings');
        }
        //</editor-fold>

        //<editor-fold desc="Game Domain">
        foreach ($this->em->getRepository(CitizenStatus::class)->findAll() as $status) {
            /** @var CitizenStatus $status */
            if (!$status->getHidden() && $status->getLabel())
                $this->insert( $c, $status->getLabel(), 'game' );

            if (!$status->getHidden() && $status->getDescription())
                $this->insert( $c, $status->getDescription(), 'game' );
        }

        foreach ($this->em->getRepository(CauseOfDeath::class)->findAll() as $causeOfDeath){
            /** @var CitizenStatus $status */
            if ($causeOfDeath->getLabel())
                $this->insert( $c, $causeOfDeath->getLabel(), 'game' );

            if ($causeOfDeath->getDescription())
                $this->insert( $c, $causeOfDeath->getDescription(), 'game' );
        }

        foreach ($this->em->getRepository(CitizenProfession::class)->findAll() as $profession) {
            /** @var CitizenProfession $profession */
            if ($profession->getLabel())
                $this->insert( $c, $profession->getLabel(), 'game' );
            if ($profession->getDescription())
                $this->insert( $c, $profession->getDescription(), 'game' );
        }

        foreach ($this->em->getRepository(CitizenRole::class)->findAll() as $role) {
            /** @var CitizenRole $role */
            if ($role->getLabel())
                $this->insert( $c, $role->getLabel(), 'game' );
            if ($role->getMessage())
                $this->insert( $c, $role->getMessage(), 'game' );

        }

        foreach ($this->em->getRepository(ZonePrototype::class)->findAll() as $zone) {
            /** @var ZonePrototype $zone */
            if ($zone->getLabel())
                $this->insert( $c, $zone->getLabel(), 'game' );

            if ($zone->getDescription())
                $this->insert( $c, $zone->getDescription(), 'game' );

            if ($zone->getExplorableDescription())
                $this->insert( $c, $zone->getExplorableDescription(), 'game' );
        }

        foreach ($this->em->getRepository(ZoneTag::class)->findAll() as $zone) {
            /** @var ZonePrototype $zone */
            if ($zone->getLabel())
                $this->insert( $c, $zone->getLabel(), 'game' );
        }

        foreach ($this->em->getRepository(TownClass::class)->findAll() as $town)
            /** @var TownClass $town */
            if ($town->getLabel())
                $this->insert( $c, $town->getLabel(), 'game' );

        foreach ($this->em->getRepository(PictoPrototype::class)->findAll() as $pictoPrototype) {
            /** @var PictoPrototype $pictoPrototype */
            if ($pictoPrototype->getLabel())
                $this->insert( $c, $pictoPrototype->getLabel(), 'game' );

            if ($pictoPrototype->getDescription())
                $this->insert( $c, $pictoPrototype->getDescription(), 'game' );
        }

        foreach ($this->em->getRepository(AwardPrototype::class)->findAll() as $awardPrototype) {
            /** @var AwardPrototype $awardPrototype */
            if ($awardPrototype->getTitle())
                $this->insert( $c, $awardPrototype->getTitle(), 'game' );
        }

        foreach ($this->em->getRepository(LogEntryTemplate::class)->findAll() as $logtemplate)
            /** @var LogEntryTemplate $logtemplate */
            if ($logtemplate->getText())
                $this->insert( $c, $logtemplate->getText(), 'game' );

        foreach ($this->em->getRepository(GazetteEntryTemplate::class)->findAll() as $gazetteTemplate)
            /** @var GazetteEntryTemplate $gazetteTemplate */
            if ($gazetteTemplate->getText())
                $this->insert( $c, $logtemplate->getText(), 'game' );

        foreach ($this->em->getRepository(HeroSkillPrototype::class)->findAll() as $heroSkill) {
            /** @var HeroSkillPrototype $heroSkill */
            if ($heroSkill->getTitle())
                $this->insert( $c, $heroSkill->getTitle(), 'game' );

            if ($heroSkill->getDescription())
                $this->insert( $c, $heroSkill->getDescription(), 'game' );
        }

        foreach ($this->em->getRepository(ComplaintReason::class)->findAll() as $reason) {
            /** @var ComplaintReason $reason */
            if ($reason->getText())
                $this->insert( $c, $reason->getText(), 'game' );
        }

        foreach ($this->em->getRepository(Season::class)->findAll() as $season) {
            /** @var Season $season */
            $this->insert( $c, "Saison {$season->getNumber()}.{$season->getSubNumber()}", 'season' );
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