<?php


namespace App\Service;

use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\Complaint;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Translation\T;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class LogTemplateHandler
{
    private $trans;
    private $asset;
    private $entity_manager;

    public function __construct(TranslatorInterface $t, Packages $a, EntityManagerInterface $em )
    {
        $this->trans = $t;
        $this->asset = $a;
        $this->entity_manager = $em;
    }

    public function wrap(?string $obj, ?string $class = null): string {
        //if (!($obj || $obj != 0)) {var_dump($obj); die;}
        return ($obj === "0" || $obj) ? ("<span" . ($class ? " class='$class'" : '') . ">$obj</span>") : '';
    }

    /**
     * @param Item|ItemPrototype|ItemGroupEntry|Citizen|CitizenProfession|Building|BuildingPrototype|CauseOfDeath|CitizenHome|CitizenHomePrototype|array $obj
     * @param bool $small
     * @return string
     */
    public function iconize($obj, bool $small = false, bool $broken = false): string {
        if (is_array($obj) && count($obj) === 2) return $this->iconize( $obj['item'], $small) . ' x ' . $obj['count'];

        if ($obj instanceof Item) {
            $str = $this->iconize( $obj->getPrototype(), $small, $obj->getBroken() );
            return $str;
        }
        if ($obj instanceof Building)    return $this->iconize( $obj->getPrototype(), $small );
        if ($obj instanceof CitizenHome) return $this->iconize( $obj->getPrototype(), $small );

        if ($small) {
            if ($obj instanceof CitizenProfession) return "<img alt='' src='{$this->asset->getUrl( "build/images/professions/{$obj->getIcon()}.gif" )}' />";
        }

        if ($obj instanceof ItemPrototype) {
            $text = "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'items')}";
            if($broken)
                $text .= " (" . $this->trans->trans("Kaputt", [], 'items') . ")";
            return $text;
        }
        if ($obj instanceof ItemGroupEntry)       return "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getPrototype()->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getPrototype()->getLabel(), [], 'items')} <i>x {$obj->getChance()}</i>";
        if ($obj instanceof BuildingPrototype)    return "<img alt='' src='{$this->asset->getUrl( "build/images/building/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'buildings')}";
        if ($obj instanceof Citizen)              return $obj->getUser()->getName();
        if ($obj instanceof CitizenProfession)    return "<img alt='' src='{$this->asset->getUrl( "build/images/professions/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'game')}";
        if ($obj instanceof CitizenHomePrototype) return "<img alt='' src='{$this->asset->getUrl( "build/images/home/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'buildings')}";
        if ($obj instanceof CauseOfDeath)         return $this->trans->trans($obj->getLabel(), [], 'game');
        return "";
    }

    public function fetchVariableObject (string $type, ?int $key) {
        if ($key === null) return null;
        $object = null;
        switch ($type) {
            case 'citizen':
                $object = $this->entity_manager->getRepository(Citizen::class)->find($key);
                break;
            case 'item':
                $object = $this->entity_manager->getRepository(ItemPrototype::class)->find($key);
                break;
            case 'itemGroup':
                $object = $this->entity_manager->getRepository(ItemGroup::class)->find($key);
                break;
            case 'home':
                $object = $this->entity_manager->getRepository(CitizenHomePrototype::class)->find($key);
                break;
            case 'plan':
                $object = $this->entity_manager->getRepository(BuildingPrototype::class)->find($key);
                break;
            case 'profession':
                $object = $this->entity_manager->getRepository(CitizenProfession::class)->find($key);
                break;
            case 'cod':
                $object = $this->entity_manager->getRepository(CauseOfDeath::class)->find($key);
                break;
        }
        return $object;
    }

    public function generateDogName(int $numeric): string {
        $dog_names_prefix = [
            T::__('TDG_PRE_00_','names'), T::__('TDG_PRE_10_','names'),T::__('TDG_PRE_20_','names'), T::__('TDG_PRE_30_','names'),T::__('TDG_PRE_40_','names'), T::__('TDG_PRE_50_','names'),
            T::__('TDG_PRE_01_','names'), T::__('TDG_PRE_11_','names'),T::__('TDG_PRE_21_','names'), T::__('TDG_PRE_31_','names'),T::__('TDG_PRE_41_','names'), T::__('TDG_PRE_51_','names'),
            T::__('TDG_PRE_02_','names'), T::__('TDG_PRE_12_','names'),T::__('TDG_PRE_22_','names'), T::__('TDG_PRE_32_','names'),T::__('TDG_PRE_42_','names'), T::__('TDG_PRE_52_','names'),
            T::__('TDG_PRE_03_','names'), T::__('TDG_PRE_13_','names'),T::__('TDG_PRE_23_','names'), T::__('TDG_PRE_33_','names'),T::__('TDG_PRE_43_','names'), T::__('TDG_PRE_53_','names'),
            T::__('TDG_PRE_04_','names'), T::__('TDG_PRE_14_','names'),T::__('TDG_PRE_24_','names'), T::__('TDG_PRE_34_','names'),T::__('TDG_PRE_44_','names'), T::__('TDG_PRE_54_','names'),
            T::__('TDG_PRE_05_','names'), T::__('TDG_PRE_15_','names'),T::__('TDG_PRE_25_','names'), T::__('TDG_PRE_35_','names'),T::__('TDG_PRE_45_','names'), T::__('TDG_PRE_55_','names'),
            T::__('TDG_PRE_06_','names'), T::__('TDG_PRE_16_','names'),T::__('TDG_PRE_26_','names'), T::__('TDG_PRE_36_','names'),T::__('TDG_PRE_46_','names'), T::__('TDG_PRE_56_','names'),
            T::__('TDG_PRE_07_','names'), T::__('TDG_PRE_17_','names'),T::__('TDG_PRE_27_','names'), T::__('TDG_PRE_37_','names'),T::__('TDG_PRE_47_','names'), T::__('TDG_PRE_57_','names'),
            T::__('TDG_PRE_08_','names'), T::__('TDG_PRE_18_','names'),T::__('TDG_PRE_28_','names'), T::__('TDG_PRE_38_','names'),T::__('TDG_PRE_48_','names'), T::__('TDG_PRE_58_','names'),
            T::__('TDG_PRE_09_','names'), T::__('TDG_PRE_19_','names'),T::__('TDG_PRE_29_','names'), T::__('TDG_PRE_39_','names'),T::__('TDG_PRE_49_','names'), T::__('TDG_PRE_59_','names'),
        ];

        $dog_names_suffix = [
            T::__('TDG_SUF_00','names'), T::__('TDG_SUF_10','names'),T::__('TDG_SUF_20','names'), T::__('TDG_SUF_30','names'),T::__('TDG_SUF_40','names'), T::__('TDG_SUF_50','names'),
            T::__('TDG_SUF_01','names'), T::__('TDG_SUF_11','names'),T::__('TDG_SUF_21','names'), T::__('TDG_SUF_31','names'),T::__('TDG_SUF_41','names'), T::__('TDG_SUF_51','names'),
            T::__('TDG_SUF_02','names'), T::__('TDG_SUF_12','names'),T::__('TDG_SUF_22','names'), T::__('TDG_SUF_32','names'),T::__('TDG_SUF_42','names'), T::__('TDG_SUF_52','names'),
            T::__('TDG_SUF_03','names'), T::__('TDG_SUF_13','names'),T::__('TDG_SUF_23','names'), T::__('TDG_SUF_33','names'),T::__('TDG_SUF_43','names'), T::__('TDG_SUF_53','names'),
            T::__('TDG_SUF_04','names'), T::__('TDG_SUF_14','names'),T::__('TDG_SUF_24','names'), T::__('TDG_SUF_34','names'),T::__('TDG_SUF_44','names'), T::__('TDG_SUF_54','names'),
            T::__('TDG_SUF_05','names'), T::__('TDG_SUF_15','names'),T::__('TDG_SUF_25','names'), T::__('TDG_SUF_35','names'),T::__('TDG_SUF_45','names'), T::__('TDG_SUF_55','names'),
            T::__('TDG_SUF_06','names'), T::__('TDG_SUF_16','names'),T::__('TDG_SUF_26','names'), T::__('TDG_SUF_36','names'),T::__('TDG_SUF_46','names'), T::__('TDG_SUF_56','names'),
            T::__('TDG_SUF_07','names'), T::__('TDG_SUF_17','names'),T::__('TDG_SUF_27','names'), T::__('TDG_SUF_37','names'),T::__('TDG_SUF_47','names'), T::__('TDG_SUF_57','names'),
            T::__('TDG_SUF_08','names'), T::__('TDG_SUF_18','names'),T::__('TDG_SUF_28','names'), T::__('TDG_SUF_38','names'),T::__('TDG_SUF_48','names'), T::__('TDG_SUF_58','names'),
            T::__('TDG_SUF_09','names'), T::__('TDG_SUF_19','names'),T::__('TDG_SUF_29','names'), T::__('TDG_SUF_39','names'),T::__('TDG_SUF_49','names'), T::__('TDG_SUF_59','names'),
        ];

        list(,$preID,$sufID) = unpack('l2', md5("dog-for-{$numeric}", true));
        return "{$this->trans->trans($dog_names_prefix[abs($preID % count($dog_names_prefix))],[],'names')}{$this->trans->trans($dog_names_suffix[abs($sufID % count($dog_names_suffix))],[],'names')}";
    }
    
    public function parseTransParams (array $variableTypes, array $variables): ?array {
        $transParams = [];
        foreach ($variableTypes as $typeEntry) {
            try {
                if ($typeEntry['type'] === 'itemGroup') {                
                    $itemGroupEntries  = $this->fetchVariableObject($typeEntry['type'], $variables[$typeEntry['name']])->getEntries()->getValues();
                    $transParams['%'.$typeEntry['name'].'%'] = implode( ', ', array_map( function(ItemGroupEntry $e) { return $this->wrap( $this->iconize( $e ), 'tool' ); }, $itemGroupEntries ));
                }
                elseif ($typeEntry['type'] === 'list') {
                    $listType = $typeEntry['listType'];
                    $listArray = array_map( function($e) use ($listType) { if(array_key_exists('count', $e)) {return array('item' => $this->fetchVariableObject($listType, $e['id']),'count' => $e['count']);}
                        else { return $this->fetchVariableObject($listType, $e['id']); } }, $variables[$typeEntry['name']] );
                    if (isset($listArray)) {
                        $transParams['%'.$typeEntry['name'].'%'] = implode( ', ', array_map( function($e) { return $this->wrap( $this->iconize( $e ), 'tool' ); }, $listArray ) );
                    }
                    else
                        $transParams['%'.$typeEntry['name'].'%'] = "null";
                }
                elseif ($typeEntry['type'] === 'num' || $typeEntry['type'] === 'string') {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap($variables[$typeEntry['name']]);
                }
                elseif ($typeEntry['type'] === 'transString') {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap( $this->trans->trans($variables[$typeEntry['name']], [], 'game') );
                }
                elseif ($typeEntry['type'] === 'dogname') {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap( $this->generateDogName((int)$variables[$typeEntry['name']]) );
                }
                elseif ($typeEntry['type'] === 'ap') {
                    $ap = $variables[$typeEntry['name']];
                    $transParams['%'.$typeEntry['name'].'%'] = "<div class='ap'>{$ap}</div>";
                }   
                elseif ($typeEntry['type'] === 'chat') {
                    $transParams['%'.$typeEntry['name'].'%'] = $variables[$typeEntry['name']];
                }
                elseif ($typeEntry['type'] === 'item') {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap( $this->iconize( $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] ), false, $variables['broken'] ?? false ), 'tool' );
                }
                else {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap( $this->iconize( $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] ), false, $variables['broken'] ?? false ) );
                }
            }
            catch (Exception $e) {
                $transParams['%'.$typeEntry['name'].'%'] = "_error_";
            }
        }

        return $transParams;
    }

    public function bankItemLog( Citizen $citizen, ItemPrototype $item, bool $toBank, bool $broken = false ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken);
        if ($toBank)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankGive']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankTake']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function bankItemTamerLog( Citizen $citizen, ItemPrototype $item, bool $broken = false ): TownLogEntry {

        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken, 'dogname' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankGiveTamer']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondTamerSendLog( Citizen $citizen, int $items ): TownLogEntry {

        $variables = array('citizen' => $citizen->getId(), 'count' => $items);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondTamerSend']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondItemLog( Citizen $citizen, ItemPrototype $item, bool $toFloor, bool $broken = false ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken);
        if ($toFloor)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'itemFloorDrop']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'itemFloorTake']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellLog( Citizen $citizen, bool $tooMuch ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        if ($tooMuch)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellTakeMuch']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellTake']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellAdd( Citizen $citizen, ?ItemPrototype $item, int $count ): TownLogEntry {
        if (isset($item)) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'num' => $count);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellAddItem']);
        }   
        else {
            $variables = array('citizen' => $citizen->getId(), 'num' => $count);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellAdd']);
        }
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellAddShaman( Citizen $citizen, int $count ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'num' => $count);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellAddShaman']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsInvestAP( Citizen $citizen, BuildingPrototype $proto, int $ap ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'ap' => $ap);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsInvestAP']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsInvestRepairAP( Citizen $citizen, BuildingPrototype $proto, int $ap ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'ap' => $ap);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsInvestRepairAP']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsDamage( Town $town, BuildingPrototype $proto, int $damage ): TownLogEntry {
        $variables = array('plan' => $proto->getId(), 'damage' => $damage);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsDamage']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( null );
    }

    public function constructionsDestroy( Town $town, BuildingPrototype $proto, int $damage ): TownLogEntry {
        $variables = array('plan' => $proto->getId(), 'damage' => $damage);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsDestroy']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( null );
    }

    public function fireworkExplosion( Town $town, BuildingPrototype $proto ): TownLogEntry {
        $variables = array('plan' => $proto->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'fireworkExplosion']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( null );
    }

    public function constructionsNewSite( Citizen $citizen, BuildingPrototype $proto ): TownLogEntry {
        if ($proto->getParent()){
            $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'parent' => $proto->getParent()->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsNewSiteDepend']);
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsNewSite']);
        }   
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsBuildingComplete( Citizen $citizen, BuildingPrototype $proto ): TownLogEntry {
        $list = $proto->getResources() ? $proto->getResources()->getEntries()->getValues() : [];
        if (!empty($list)){
            $varlist = array_map( function(ItemGroupEntry $e) { return array('id' => $e->getPrototype()->getId(), 'count' => $e->getChance()); }, $list );

            $variables = array('plan' => $proto->getId(), 'list' => $varlist);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingComplete']);
        }
        else {
            $variables = array('plan' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteNoResources']);
        }
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function constructionsBuildingCompleteSpawnItems( Building $building, $items ): TownLogEntry {
        $proto = $building->getPrototype();
        $variables = array('building' => $proto->getId(), 
            'list' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteSpawnItems']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function constructionsBuildingCompleteWell( Building $building, int $water ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $water);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteWell']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function doorControl( Citizen $citizen, bool $open ): TownLogEntry {
        if ($open)
            $action = "geöffnet";
        else 
            $action = "geschlossen";
        $variables = array('citizen' => $citizen->getId(), 'action' => $action);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'doorControl']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function doorControlAuto( Town $town, bool $open, ?DateTimeInterface $time ): TownLogEntry {
        if ($open)
            $action = "geöffnet";
        else 
            $action = "geschlossen";
        $variables = array('action' => $action);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'doorControlAuto']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function doorPass( Citizen $citizen, bool $in ): TownLogEntry {
        if ($in)
            $action = "betreten";
        else 
            $action = "verlassen";
        $variables = array('citizen' => $citizen->getId(), 'action' => $action);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'doorPass']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenJoin( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenJoin']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenProfession( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'profession' => $citizen->getProfession()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenProfession']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenZombieAttackRepelled( Citizen $citizen, int $def, int $zombies ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'num' => $zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenZombieAttackRepelled']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenDeath( Citizen $citizen, int $zombies = 0, ?Zone $zone = null, ?int $day = null ): TownLogEntry {
        switch ($citizen->getCauseOfDeath()->getRef()) {
            case CauseOfDeath::NightlyAttack:
                $variables = array('citizen' => $citizen->getId(), 'num' => $zombies);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathNightlyAttack']);
                break;
            case CauseOfDeath::Vanished:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathVanished']);
                break;
            case CauseOfDeath::Cyanide:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathCyanide']);
                break;
            case CauseOfDeath::Poison: case CauseOfDeath::GhulEaten:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathPoison']);
                break;
            case CauseOfDeath::Hanging: case CauseOfDeath::FleshCage:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathHanging']);
                break;
            case CauseOfDeath::Headshot:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathHeadshot']);
                break;
            default: 
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathDefault']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $day ?? $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $zone );
    }

    public function citizenDeathOnWatch( Citizen $citizen, int $zombies = 0, ?Zone $zone = null, ?int $day = null ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathOnWatch']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $day ?? $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $zone );
    }

    public function homeUpgrade( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'home' => $citizen->getHome()->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'homeUpgrade']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function workshopConvert( Citizen $citizen, array $items_in, array $items_out ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 
            'list1' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items_in ),
            'list2' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items_out ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'workshopConvert']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function outsideMove( Citizen $citizen, Zone $zone1, Zone $zone2, bool $depart ): TownLogEntry {
        $is_zero_zone = ($zone1->getX() === 0 && $zone1->getY() === 0);

        $d_north = $zone2->getY() > $zone1->getY();
        $d_south = $zone2->getY() < $zone1->getY();
        $d_east  = $zone2->getX() > $zone1->getX();
        $d_west  = $zone2->getX() < $zone1->getX();

        $str = 'Horizont';
        if ($d_north) {
            if ($d_east)     $str = 'Nordosten';
            elseif ($d_west) $str = 'Nordwesten';
            else             $str = 'Norden';
        } elseif ($d_south) {
            if ($d_east)     $str = 'Südosten';
            elseif ($d_west) $str = 'Südwesten';
            else             $str = 'Süden';
        } elseif ($d_east)   $str = 'Osten';
        elseif ($d_west)     $str = 'Westen';

        // This breaks the sneak out capability of the ghoul. The caller of this function that would trigger this if statement is disabled.
        if ($is_zero_zone) 
        {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str);
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townMoveLeave']);
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townMoveEnter']);
            }
        }
        else 
        {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str, 'profession' => $citizen->getProfession()->getId());
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideMoveLeave']);
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideMoveEnter']);
            }
        }
        
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setZone( $is_zero_zone ? null : $zone1 )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function outsideDig( Citizen $citizen, ?ItemPrototype $item, ?DateTimeInterface $time = null ): TownLogEntry {
        $found_something = $item !== null;
        if ($found_something) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideDigSuccess']);
        }
        else {
            $variables = array('citizen' => $citizen->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideDigFail']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function outsideUncover( Citizen $citizen ): TownLogEntry {
        $bc = $citizen->getZone()->getBuryCount() > 0;
        if ($bc) {
            $variables = array('citizen' => $citizen->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideUncover']);
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'type' => $citizen->getZone()->getPrototype()->getLabel());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideUncoverComplete']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function outsideFoundHiddenItems( Citizen $citizen, $items ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'items' => array_map( function($e) {
            if(array_key_exists('count', $e)) {
                return array('id' => $e['item']->getPrototype()->getId(),'count' => $e['count']);
            } else {
                return array('id' => $e->getPrototype->getId());
            }
            }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideFoundHiddenItems']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function constructionsBuildingCompleteAllOrNothing( town $town, $tempDef ): TownLogEntry {
        $variables = array('def' => $tempDef);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteAllOrNothing']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyInternalAttackKill( Citizen $zombie, Citizen $victim ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'victim' => $victim->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackKill']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie )
            ->setSecondaryCitizen( $victim );
    }

    public function nightlyInternalAttackDestroy( Citizen $zombie, Building $building ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'building' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackDestroy']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyInternalAttackWell( Citizen $zombie, int $units ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'num' => $units);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackWell']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyInternalAttackStart(Town $town): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackStart']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyInternalAttackNothing( Citizen $zombie ): TownLogEntry {
        $templateList = [
            'nightlyInternalAttackNothing1',
            'nightlyInternalAttackNothing2',
            'nightlyInternalAttackNothing3',
            'nightlyInternalAttackNothing4',
        ];
        $variables = array('zombie' => $zombie->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $templateList[array_rand($templateList,1)]]);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyInternalAttackNothingSummary( Town $town, $useless ): TownLogEntry {
        $variables = array('count' => $useless);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackNothingSummary']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackCancelled( Town $town ): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackCancelled']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBegin( Town $town, int $num_zombies ): TownLogEntry {
        $variables = array('num' => $num_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackBegin']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackSummary( Town $town, bool $door_open, int $num_zombies ): TownLogEntry {
        if ($door_open) {
            $variables = array('num' => $num_zombies);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackSummaryOpenDoor']);
        }
        elseif ($num_zombies > 0) {
            $variables = array('num' => $num_zombies);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackSummarySomeZombies']);
        }
        else {
            $variables = [];
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackSummaryNoZombies']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchers( Town $town, $watchers ): TownLogEntry {
        $citizenList = [];
        foreach ($watchers as $watcher) {
            $citizenList[] = array('id' => $watcher->getCitizen()->getId());
        }
        $variables = array('citizens' => $citizenList);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackWatchers']);
        
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackLazy( Town $town, int $num_attacking_zombies ): TownLogEntry {
        $variables = array('num' => $num_attacking_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackLazy']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBuildingDefenseWater( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackBuildingDefenseWater']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackUpgradeBuildingWell( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackUpgradeBuildingWell']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackUpgradeBuildingItems( Building $building, ?array $items ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackUpgradeBuildingItems']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    /**
     * @param Town $town
     * @param Item|ItemPrototype $item
     * @return TownLogEntry
     */
    public function nightlyAttackProductionBlueprint( Town $town, ItemPrototype $item ): TownLogEntry {
        $variables = array('item' => $item->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackProductionBlueprint']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackProduction( Building $building, ?array $items = [] ): TownLogEntry {        
        $variables = array('building' => $building->getPrototype()->getId(), 
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackProduction']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackDestroyBuilding( Town $town, Building $building ): TownLogEntry {
        $variables = array('buildingName' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackDestroyBuilding']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function citizenComplaint( Complaint $complaint ): TownLogEntry {
        $variables = array('citizen' => $complaint->getCulprit()->getId());
        if ($complaint->getSeverity() > Complaint::SeverityNone) {
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenComplaintSet']);
        }
        else {
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenComplaintUnset']);
        }
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $complaint->getAutor()->getTown() )
            ->setDay( $complaint->getAutor()->getTown()->getDay() )
            ->setCitizen( $complaint->getCulprit() )
            ->setTimestamp( new DateTime('now') );
    }

    public function citizenBanish( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenBanish']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setCitizen( $citizen )
            ->setTimestamp( new DateTime('now') );
    }

    public function citizenDisposal( Citizen $actor, Citizen $disposed, int $action, ?array $items = [] ): TownLogEntry {
        switch ($action) {
            case 1:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalDrag']);
                break;
            case 2:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalWater']);
                break;
            case 3:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId(), 
                    'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
                        else { return array('id' => $e[0]->getId()); } }, $items ));
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalCremato']);
                break;
            case 4:
                $variables = array();
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalGhoul']);
                break;
            default:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalDefault']);
                break;
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $actor->getTown() )
            ->setDay( $actor->getTown()->getDay() )
            ->setCitizen( $action != 4 ? $actor : null )
            ->setSecondaryCitizen( $disposed )
            ->setTimestamp( new DateTime('now') );
    }

    public function townSteal( Citizen $victim, ?Citizen $actor, ItemPrototype $item, bool $up, bool $santa = false, $broken = false, bool $leprechaun = false): TownLogEntry {

        if ($up){
            if($santa || $leprechaun){
                $variables = array('victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                if($santa)
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealSanta']);
                else
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealLeprechaun']);
            } 
            else {
                if ($actor) {
                    $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealCaught']);
                }
                else {
                    $variables = array('victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealUncaught']);
                }
            }
        }
        else {
            if ($actor) {
                $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townSmuggleCaught']);
            }
            else {
                $variables = array('victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townSmuggleUncaught']);
            }
        }
            
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $victim->getTown() )
            ->setDay( $victim->getTown()->getDay() )
            ->setCitizen( $victim )
            ->setSecondaryCitizen( $actor )
            ->setTimestamp( new DateTime('now') );
    }

    public function townLoot( Citizen $victim, ?Citizen $actor, ItemPrototype $item, bool $up, bool $santa = false, $broken = false): TownLogEntry {

        $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townLoot']);
            
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $victim->getTown() )
            ->setDay( $victim->getTown()->getDay() )
            ->setCitizen( $victim )
            ->setSecondaryCitizen( $actor )
            ->setTimestamp( new DateTime('now') );
    }

    public function zombieKill( Citizen $citizen, ?ItemPrototype $item, int $kills ): TownLogEntry {
        if ($item) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillWeapon']);
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillHands']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function zombieKillShaman( Citizen $citizen, int $kills ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillShaman']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondChat( Citizen $sender, string $message ): TownLogEntry {
        $variables = array('sender' => $sender->getId(), 'message' => htmlentities( $message ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondChat']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $sender->getTown() )
            ->setDay( $sender->getTown()->getDay() )
            ->setZone( $sender->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $sender );
    }

    public function beyondCampingImprovement( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingImprovement']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingItemImprovement( Citizen $citizen, ItemPrototype $item ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingItemImprovement']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingHide( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingHide']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingUnhide( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingUnhide']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortEnable( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortEnable']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortDisable( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortDisable']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortTakeCitizen( Citizen $citizen, Citizen $target_citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'target_citizen' => $target_citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortTakeCitizen']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortReleaseCitizen( Citizen $citizen, Citizen $target_citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'target_citizen' => $target_citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortReleaseCitizen']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function heroicRescueLog( Citizen $hero, Citizen $citizen, Zone $zone ): TownLogEntry {
        $variables = array('hero' => $hero->getId(), 'citizen' => $citizen->getId(), 'pos' => "[{$zone->getX()},{$zone->getY()}]");
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'heroRescue']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $hero->getTown() )
            ->setDay( $hero->getTown()->getDay() )
            ->setZone( null )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $hero )
            ->setSecondaryCitizen( $citizen );
    }

    public function citizenAttack( Citizen $attacker, Citizen $defender, bool $wounded ): TownLogEntry {
        $variables = array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $wounded ? 'citizenAttackWounded' : 'citizenAttack']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $attacker )
            ->setSecondaryCitizen( $defender );
    }

    public function sandballAttack( Citizen $attacker, Citizen $defender, bool $wounded ): TownLogEntry {
        $variables = array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $wounded ? 'sandballAttackWounded' : 'sandballAttack']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $attacker )
            ->setSecondaryCitizen( $defender );
    }

    public function citizenTownGhoulAttack( Citizen $attacker, Citizen $defender ): TownLogEntry {
        $variables = array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenTownGhoulAttack']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $attacker )
            ->setSecondaryCitizen( $defender );
    }

    public function citizenBeyondGhoulAttack( Citizen $attacker, Citizen $defender, bool $ambient  ): TownLogEntry {
        $variables = $ambient ? [] : array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $ambient ? 'citizenBeyondGhoulAttack1' : 'citizenBeyondGhoulAttack2']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') );
    }

    public function catapultUsage( Citizen $master, Item $item, Zone $target ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'catapultUsage']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([
                'master' => $master->getId(),
                'item' => $item->getPrototype()->getId(),
                'x' => $target->getX(),
                'y' => $target->getY()
            ])
            ->setTown( $master->getTown() )
            ->setDay( $master->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function catapultImpact( Item $item, Zone $target ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'catapultImpact']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([
                'item' => $item->getPrototype()->getId(),
            ])
            ->setTown( $target->getTown() )
            ->setDay( $target->getTown()->getDay() )
            ->setZone( ($target->getX() === 0 && $target->getY() === 0) ? null : $target )
            ->setTimestamp( new DateTime('now') );
    }
}
