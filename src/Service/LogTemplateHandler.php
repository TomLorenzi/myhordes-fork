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

    private function wrap(string $obj): string {
        return "<span>$obj</span>";
    }

    /**
     * @param Item|ItemPrototype|ItemGroupEntry|Citizen|CitizenProfession|Building|BuildingPrototype|CauseOfDeath|CitizenHome|CitizenHomePrototype|array $obj
     * @param bool $small
     * @return string
     */
    private function iconize($obj, bool $small = false): string {
        if (is_array($obj) && count($obj) === 2) return $this->iconize( $obj['item'], $small) . ' x ' . $obj['count'];

        if ($obj instanceof Item) {
            $str = $this->iconize( $obj->getPrototype(), $small );
            if($obj->getBroken())
                $str .= " (" . $this->trans->trans("Kaputt", [], 'items') . ")";
            return $str;
        }
        if ($obj instanceof Building)    return $this->iconize( $obj->getPrototype(), $small );
        if ($obj instanceof CitizenHome) return $this->iconize( $obj->getPrototype(), $small );

        if ($small) {
            if ($obj instanceof CitizenProfession) return "<img alt='' src='{$this->asset->getUrl( "build/images/professions/{$obj->getIcon()}.gif" )}' />";
        }

        if ($obj instanceof ItemPrototype)        return "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'items')}";
        if ($obj instanceof ItemGroupEntry)       return "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getPrototype()->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getPrototype()->getLabel(), [], 'items')} <i>x {$obj->getChance()}</i>";
        if ($obj instanceof BuildingPrototype)    return "<img alt='' src='{$this->asset->getUrl( "build/images/building/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'buildings')}";
        if ($obj instanceof Citizen)              return $obj->getUser()->getUsername();
        if ($obj instanceof CitizenProfession)    return "<img alt='' src='{$this->asset->getUrl( "build/images/professions/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'game')}";
        if ($obj instanceof CitizenHomePrototype) return "<img alt='' src='{$this->asset->getUrl( "build/images/home/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'buildings')}";
        if ($obj instanceof CauseOfDeath)         return $this->trans->trans($obj->getLabel(), [], 'game');
        return "";
    }

    public function fetchVariableObject (string $type, int $key) {
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

    public function parseTransParams (array $variableTypes, array $variables): ?array {
        $transParams = [];
        foreach ($variableTypes as $typeEntry) {
            try {       
                if ($typeEntry['type'] === 'itemGroup') {                
                    $itemGroupEntries  = $this->fetchVariableObject($typeEntry['type'], $variables[$typeEntry['name']])->getEntries()->getValues();
                    $transParams['%'.$typeEntry['name'].'%'] = implode( ', ', array_map( function(ItemGroupEntry $e) { return $this->wrap( $this->iconize( $e ) ); }, $itemGroupEntries ));
                }
                elseif ($typeEntry['type'] === 'list') {
                    $listType = $typeEntry['listType'];
                    $listArray = array_map( function($e) use ($listType) { if(array_key_exists('count', $e)) {return array('item' => $this->fetchVariableObject($listType, $e['id']),'count' => $e['count']);}
                        else { return $this->fetchVariableObject($listType, $e['id']); } ;}, $variables[$typeEntry['name']] );
                    if (isset($listArray)) {
                        $transParams['%'.$typeEntry['name'].'%'] = implode( ', ', array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $listArray ) );
                    }
                    else
                        $transParams['%'.$typeEntry['name'].'%'] = "null";
                }
                elseif ($typeEntry['type'] === 'num') {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap($variables[$typeEntry['name']]);
                }
                elseif ($typeEntry['type'] === 'transString') {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap( $this->trans->trans($variables[$typeEntry['name']], [], 'game') );
                }
                elseif ($typeEntry['type'] === 'ap') {
                    $ap = $variables[$typeEntry['name']];
                    $transParams['%'.$typeEntry['name'].'%'] = "<div class='ap'>{$ap}</div>";
                }   
                elseif ($typeEntry['type'] === 'chat') {
                    $transParams['%'.$typeEntry['name'].'%'] = $variables[$typeEntry['name']];
                }     
                else {
                    $transParams['%'.$typeEntry['name'].'%'] = $this->wrap( $this->iconize( $this->fetchVariableObject($typeEntry['type'], $variables[$typeEntry['name']]) ) );
                }
            }
            catch (Exception $e) {
                $transParams['%'.$typeEntry['name'].'%'] = "null";
            }
        }
        
        return $transParams;
    }

    public function bankItemLog( Citizen $citizen, Item $item, bool $toBank ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getPrototype()->getId());
        if ($toBank)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('bankGive');
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('bankTake');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondItemLog( Citizen $citizen, Item $item, bool $toFloor ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getPrototype()->getId());
        if ($toFloor)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('itemFloorDrop');
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('itemFloorTake');
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
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('wellTakeMuch');
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('wellTake');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellAdd( Citizen $citizen, ?Item $item, int $count ): TownLogEntry {
        if (isset($item)) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getPrototype()->getId(), 'num' => $count);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('wellAddItem');
        }   
        else {
            $variables = array('citizen' => $citizen->getId(), 'num' => $count);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('wellAdd');
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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('wellAddShaman');
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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsInvestAP');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsNewSite( Citizen $citizen, BuildingPrototype $proto ): TownLogEntry {
        if ($proto->getParent()){
            $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'parent' => $proto->getParent()->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsNewSiteDepend');
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsNewSite');
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
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingComplete');
        }
        else {
            $variables = array('plan' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteNoResources');
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
              else { return array('id' => $e[0]->getId()); } ;}, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteSpawnItems');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function constructionsBuildingCompleteWell( Building $building, int $water ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $water);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteWell');
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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('doorControl');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('doorControlAuto');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('doorPass');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenJoin');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenProfession');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenZombieAttackRepelled');
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
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathNightlyAttack');
                break;
            case CauseOfDeath::Vanished:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathVanished');
                break;
            case CauseOfDeath::Cyanide:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathCyanide');
                break;
            case CauseOfDeath::Posion: case CauseOfDeath::GhulEaten:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathPoison');
                break;
            case CauseOfDeath::Hanging: case CauseOfDeath::FleshCage:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathHanging');
                break;
            case CauseOfDeath::Headshot:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathHeadshot');
                break;
            default: 
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathDefault');
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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathOnWatch');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('homeUpgrade');

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
              else { return array('id' => $e[0]->getId()); } ;}, $items_in ),
            'list2' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } ;}, $items_out ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('workshopConvert');

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

        if ($is_zero_zone) 
        {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str);
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townMoveLeave');
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townMoveEnter');
            }
        }
        else 
        {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str, 'profession' => $citizen->getProfession()->getId());
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('outsideMoveLeave');
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('outsideMoveEnter');
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
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('outsideDigSuccess');
        }
        else {
            $variables = array('citizen' => $citizen->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('outsideDigFail');
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
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('outsideUncover');
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'type' => $citizen->getZone()->getPrototype()->getLabel());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('outsideUncoverComplete');
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

    public function constructionsBuildingCompleteAllOrNothing( town $town, $tempDef ): TownLogEntry {
        $variables = array('def' => $tempDef);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteAllOrNothing');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyInternalAttackKill( Citizen $zombie, Citizen $victim ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'victim' => $victim->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyInternalAttackKill');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyInternalAttackDestroy');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyInternalAttackWell');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyInternalAttackNothing( Citizen $zombie ): TownLogEntry {
        $templateList = [
            'nightlyInternalAttackNothing1',
            'nightlyInternalAttackNothing2',
            'nightlyInternalAttackNothing3',
            'nightlyInternalAttackNothing4',
        ];
        $variables = array('zombie' => $zombie->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName($templateList[array_rand($templateList,1)]);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyAttackBegin( Town $town, int $num_zombies ): TownLogEntry {
        $variables = array('num' => $num_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackBegin');

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
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackSummaryOpenDoor');
        }
        elseif ($num_zombies > 0) {
            $variables = array('num' => $num_zombies);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackSummarySomeZombies');
        }
        else {
            $variables = [];
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackSummaryNoZombies');
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchers( Town $town ): TownLogEntry {
        $citizenList = [];
        foreach ($town->getCitizenWatches() as $watcher) {
            $citizenList[] = array('id' => $watcher->getCitizen()->getId());
        }
        $variables = array('citizens' => $citizenList);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackWatchers');
        
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackLazy( Town $town, int $num_attacking_zombies ): TownLogEntry {
        $variables = array('num' => $num_attacking_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackLazy');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBuildingDefenseWater( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackBuildingDefenseWater');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackUpgradeBuildingWell( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackUpgradeBuildingWell');

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
              else { return array('id' => $e[0]->getId()); } ;}, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackUpgradeBuildingItems');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackProductionBlueprint');

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
              else { return array('id' => $e[0]->getId()); } ;}, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackProduction');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackDestroyBuilding( Town $town, Building $building ): TownLogEntry {
        $variables = array('buildingName' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackDestroyBuilding');

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
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenComplaintSet');
        }
        else {
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenComplaintUnset');
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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenBanish');

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
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalDrag');
                break;
            case 2:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalWater');
                break;
            case 3:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId(), 
                    'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
                        else { return array('id' => $e[0]->getId()); } ;}, $items ));
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalCremato');
                break;
            default:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalDefault');
                break;
        }

        $items = array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $items );

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $actor->getTown() )
            ->setDay( $actor->getTown()->getDay() )
            ->setCitizen( $actor )
            ->setSecondaryCitizen( $disposed )
            ->setTimestamp( new DateTime('now') );
    }

    public function townSteal( Citizen $victim, ?Citizen $actor, Item $item, bool $up, bool $santa = false): TownLogEntry {

        if ($up){
            if($santa){
                $variables = array('victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townStealSanta');
            } 
            else {
                if ($actor) {
                    $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townStealCaught');
                }
                else {
                    $variables = array('victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townStealUncaught');
                }
            }
        }
        else {
            if ($actor) {
                $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townSmuggleCaught');
            }
            else {
                $variables = array('victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townSmuggleUncaught');
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

    public function zombieKill( Citizen $citizen, ?Item $item, int $kills ): TownLogEntry {
        if ($item) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getPrototype()->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('zombieKillWeapon');
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('zombieKillHands');
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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('zombieKillShaman');

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
        $variables = array('sender' => $sender->getId(), 'message' => ': ' . htmlentities( $message ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondChat');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingImprovement');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingItemImprovement( Citizen $citizen, Item $item ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingItemImprovement');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingHide');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingUnhide');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortEnable');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortDisable');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortTakeCitizen');

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
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortReleaseCitizen');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }
}