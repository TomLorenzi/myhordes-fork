<?php


namespace App\Translation;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenStatus;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\TownClass;
use App\Entity\ZonePrototype;
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
        foreach ($this->em->getRepository(ItemPrototype::class)->findAll() as $item)
            /** @var ItemPrototype $item */
            $this->insert( $c, $item->getLabel(), 'items' );

        // Get Action labels and messages as well as requirement messages
        foreach ($this->em->getRepository(ItemAction::class)->findAll() as $action) {
            /** @var ItemAction $action */
            $this->insert($c, $action->getLabel(), 'items');
            if ($action->getMessage())
                $this->insert($c, $action->getMessage(), 'items');
            foreach ($action->getRequirements() as $requirement) {
                if ($requirement->getFailureText())
                    $this->insert($c, $requirement->getFailureText(), 'items');
            }
        }
        //</editor-fold>

        //<editor-fold desc="Building Domain">
        // Get building labels and upgrade descriptions
        foreach ($this->em->getRepository(BuildingPrototype::class)->findAll() as $building) {
            /** @var BuildingPrototype $building */
            $this->insert( $c, $building->getLabel(), 'buildings' );
            if ($building->getUpgradeTexts())
                foreach ($building->getUpgradeTexts() as $text)
                    $this->insert( $c, $text, 'buildings' );
        }

        // Get home upgrade labels
        foreach ($this->em->getRepository(CitizenHomePrototype::class)->findAll() as $upgrade)
            /** @var $upgrade CitizenHomePrototype */
            $this->insert( $c, $upgrade->getLabel(), 'buildings' );

        // Get home extension labels
        foreach ($this->em->getRepository(CitizenHomeUpgradePrototype::class)->findAll() as $extension) {
            /** @var $extension CitizenHomeUpgradePrototype */
            $this->insert($c, $extension->getLabel(), 'buildings');
            $this->insert($c, $extension->getDescription(), 'buildings');
        }
        //</editor-fold>

        //<editor-fold desc="Game Domain">
        foreach ($this->em->getRepository(CitizenStatus::class)->findAll() as $status)
            /** @var $status CitizenStatus */
            if ($status->getLabel())
                $this->insert( $c, $status->getLabel(), 'game' );

        foreach ($this->em->getRepository(CitizenProfession::class)->findAll() as $profession)
            /** @var $profession CitizenProfession */
            if ($profession->getLabel())
                $this->insert( $c, $status->getLabel(), 'game' );

        foreach ($this->em->getRepository(ZonePrototype::class)->findAll() as $zone)
            /** @var $zone ZonePrototype */
            if ($zone->getLabel())
                $this->insert( $c, $zone->getLabel(), 'game' );

            foreach ($this->em->getRepository(TownClass::class)->findAll() as $town)
            /** @var $town TownClass */
            if ($town->getLabel())
                $this->insert( $c, $town->getLabel(), 'game' );
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