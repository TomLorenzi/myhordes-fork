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
use Doctrine\ORM\Mapping\Entity;
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
                    $listArray = [];
                    switch ($typeEntry['listType']) {
                        case 'item':
                            $listArray = array_map( function($e) { if(array_key_exists('count', $e)) {return array('item' => $this->fetchVariableObject("item", $e['id']),'count' => $e['count']);}
                                else { return $this->fetchVariableObject("item", $e['id']); } ;}, $variables[$typeEntry['name']] );
                            break;
                        default:
                            break;
                    }
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
            ->setType( TownLogEntry::TypeBank )
            ->setClass( $toBank ? TownLogEntry::ClassNone : TownLogEntry::ClassWarning )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setText( $this->trans->trans(
                $toBank
                    ? '%citizen% hat der Stadt folgendes gespendet: %item%'
                    : '%citizen% hat folgenden Gegenstand aus der Bank genommen: %item%', [
                        '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                        '%item%'    => $this->wrap( $this->iconize( $item ) ),
            ], 'game' ) );


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
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans(
                $toFloor
                    ? '%citizen% hat folgenden Gegenstand hier abgelegt: %item%'
                    : '%citizen% hat diesen Gegenstand mitgenommen: %item%', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%item%'    => $this->wrap( $this->iconize( $item ) ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeWell )
            ->setClass( $tooMuch ? TownLogEntry::ClassWarning : TownLogEntry::ClassNone )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans(
                $tooMuch
                    ? '%citizen% hat mehr Wasser genommen, als erlaubt ist...'
                    : '%citizen% hat eine Ration Wasser genommen.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeWell )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans($item
                ? '%citizen% hat %item% in den Brunnen geschüttet und damit %num% Rationen Wasser hinzugefügt.'
                : '%citizen% hat dem Brunnen %num% Rationen Wasser hinzugefügt.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%item%'    => $this->wrap( $this->iconize( $item ) ),
                '%num%'     => $this->wrap( "$count" ),
            ], 'game' ) );
    }

    public function wellAddShaman( Citizen $citizen, int $count ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'num' => $count);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('wellAddShaman');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeWell )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat es regnen lassen! Ihm habt ihr %num% Rationen reinen Wassers im Brunnen zu verdanken!', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%num%'     => $this->wrap( "$count" ),
            ], 'game' ) );
    }

    public function constructionsInvestAP( Citizen $citizen, BuildingPrototype $proto, int $ap ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'ap' => $ap);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsInvestAP');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeConstruction )
            ->setClass( TownLogEntry::ClassNone )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans( '%citizen% hat an dem Bauprojekt %plan% mitgearbeitet und dabei %ap% ausgegeben.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%plan%'    => $this->wrap( $this->iconize( $proto ) ),
                '%ap%'      => "<div class='ap'>{$ap}</div>"
            ], 'game' ) );
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
        $str = '%citizen% hat einen Bauplan für %plan% gefunden.';
        if ($proto->getParent()) $str .= ' Dafür ist das Bauprojekt %parent% nötig.';
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeConstruction )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans( $str, [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%plan%'    => $this->wrap( $this->iconize( $proto ) ),
                '%parent%'  => $proto->getParent() ? $this->wrap( $this->iconize( $proto->getParent() ) ) : '???',
            ], 'game' ) );
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
        $list = array_map( function(ItemGroupEntry $e) { return $this->wrap( $this->iconize( $e ) ); }, $list );
        $uses_res = !empty($list);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeConstruction )
            ->setSecondaryType( TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans( $uses_res
                ? 'Es wurde ein neues Gebäude gebaut: %plan%. Der Bau hat folgende Ressourcen verbraucht: %list%'
                : 'Es wurde ein neues Gebäude gebaut: %plan%.', [
                '%plan%' => $this->wrap( $this->iconize( $proto ) ),
                '%list%' => implode( ', ', $list )
            ], 'game' ) );
    }

    public function constructionsBuildingCompleteSpawnItems( Building $building, $items ): TownLogEntry {
        $list = array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $items );
        $proto = $building->getPrototype();

        $variables = array('building' => $proto->getId(), 
            'list' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } ;}, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteSpawnItems');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeConstruction )
            ->setSecondaryType( TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans( 'Durch die Konstruktion von %building% hat die Stadt folgende Gegenstände erhalten: %list%', [
                '%building%' => $this->wrap( $this->iconize( $proto ) ),
                '%list%'     => implode( ', ', $list )
            ], 'game' ) );
    }

    public function constructionsBuildingCompleteWell( Building $building, int $water ): TownLogEntry {

        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $water);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteWell');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeConstruction )
            ->setSecondaryType( TownLogEntry::TypeWell )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans( 'Durch die Konstruktion von %building% wurde der Brunnen um %num% Rationen Wasser aufgefüllt!', [
                '%building%' => $this->wrap( $this->iconize( $building ) ),
                '%num%'      => $this->wrap( "{$water}" )
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeDoor )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat das Stadttor %action%.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%action%'  => $this->wrap( $open ? $this->trans->trans('geöffnet', [], 'game') : $this->trans->trans('geschlossen', [], 'game') ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeDoor )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setText( $this->trans->trans('Das Stadttor wurde automatisch %action%.', [
                '%action%'  => $this->wrap( $open ? $this->trans->trans('geöffnet', [], 'game') : $this->trans->trans('geschlossen', [], 'game') ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeDoor )
            ->setClass( TownLogEntry::ClassNone )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat die Stadt %action%.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%action%'  => $this->wrap( $in ? $this->trans->trans('betreten', [], 'game') : $this->trans->trans('verlassen', [], 'game') ),
            ], 'game' ) );
    }

    public function citizenJoin( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenJoin');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeCitizens )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('Ein neuer Bürger ist in der Stadt angekommen: %citizen%.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
    }

    public function citizenProfession( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'profession' => $citizen->getProfession()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenProfession');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeCitizens )
            ->setClass( TownLogEntry::ClassNone )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat seine neue Berufung als %profession% gefunden.', [
                '%citizen%'    => $this->wrap( $this->iconize( $citizen ) ),
                '%profession%' => $this->wrap( $this->iconize( $citizen->getProfession() ) ),
            ], 'game' ) );
    }

    public function citizenZombieAttackRepelled( Citizen $citizen, int $def, int $zombies ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'num' => $zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenZombieAttackRepelled');
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeCitizens )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%zombies% haben vergeblich versucht, in das Haus von %citizen% einzudringen.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%zombies%' => $this->wrap( $this->trans->trans( '%num% Zombies', ['%num%' => $zombies], 'game' ) ),
                '%defense%' => $this->wrap( "{$def}" ),
            ], 'game' ) );
    }

    public function citizenDeath( Citizen $citizen, int $zombies = 0, ?Zone $zone = null ): TownLogEntry {
        switch ($citizen->getCauseOfDeath()->getRef()) {
            case CauseOfDeath::NightlyAttack:
                $variables = array('citizen' => $citizen->getId(), 'num' => $zombies);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathNightlyAttack');
                $str = T::__('%citizen% wurde von %zombies% zerfleischt!','game');
                break;
            case CauseOfDeath::Vanished:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathVanished');
                $str = T::__('Hat jemand in letzter Zeit von %citizen% gehört? Er scheint verschwunden zu sein ...','game');
                break;
            case CauseOfDeath::Cyanide:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathCyanide');
                $str = T::__('%citizen% hat dem Druck nicht länger standgehalten und sein Leben beendet: %cod%.','game');
                break;
            case CauseOfDeath::Posion: case CauseOfDeath::GhulEaten:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathPoison');
                $str = T::__('Verrat! %citizen% ist gestorben: %cod%.','game');
                break;
            case CauseOfDeath::Hanging: case CauseOfDeath::FleshCage:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathHanging');
                $str = T::__('%citizen% hat das Fass zum Überlaufen gebracht. Die Stadt hat seinen Tod entschieden: %cod%.','game');
                break;
            case CauseOfDeath::Headshot:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathHeadshot');
                $str = T::__('%citizen% wurde standrechtlich erschossen. Lang lebe das Diktat!','game');
                break;
            default: 
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDeathDefault');
                $str = T::__('%citizen% hat seinen letzten Atemzug getan: %cod%!','game');
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeCitizens )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $zone )
            ->setText( $this->trans->trans($str, [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%zombies%' => $this->wrap( $this->trans->trans( '%num% Zombies', ['%num%' => $zombies], 'game' ) ),
                '%cod%'     => $this->wrap( $this->iconize( $citizen->getCauseOfDeath() ) ),
            ], 'game' ) );
    }

    public function homeUpgrade( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'home' => $citizen->getHome()->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('homeUpgrade');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeHome )
            ->setClass( TownLogEntry::ClassNone )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat seine Behausung in ein(e,n) %home% verwandelt...', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%home%'    => $this->wrap( $this->iconize( $citizen->getHome() ) ),
            ], 'game' ) );
    }

    public function workshopConvert( Citizen $citizen, array $items_in, array $items_out ): TownLogEntry {
        $list1 = array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $items_in  );
        $list2 = array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $items_out );

        $variables = array('citizen' => $citizen->getId(), 
            'list1' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } ;}, $items_in ),
            'list2' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } ;}, $items_out ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('workshopConvert');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeWorkshop )
            ->setSecondaryType( TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% folgendes hergestellt: %list2%; dabei wurden folgende Gegenstände verbraucht: %list1%.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%list1%' => implode( ', ', $list1 ),
                '%list2%' => implode( ', ', $list2 )
            ], 'game' ) );
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
            $base = $depart ?  T::__('%citizen% hat das Stadtgebiet in Richtung %direction% verlassen.', 'game') : T::__('%citizen% hat das Stadtgebiet aus Richtung %direction% betreten.', 'game');
        else $base = $depart ? T::__('%citizen% (%profession%) ist Richtung %direction% aufgebrochen.', 'game') :  T::__('%citizen% (%profession%) ist aus dem %direction% angekommen.', 'game');

        if ($is_zero_zone) {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str);
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townMoveLeave');
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townMoveEnter');
            }
        }
        else {
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
            ->setType( TownLogEntry::TypeVarious )
            ->setSecondaryType( $is_zero_zone ? TownLogEntry::TypeDoor : null )
            ->setClass( TownLogEntry::ClassNone )
            ->setTown( $citizen->getTown() )
            ->setZone( $is_zero_zone ? null : $zone1 )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans($base, [
                '%citizen%'   => $this->wrap( $this->iconize( $citizen ) ),
                '%direction%' => $this->wrap( $this->trans->trans($str, [], 'game') ),
                '%profession%' => $this->wrap( $this->iconize( $citizen->getProfession(), true ) ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() )
            ->setText( $this->trans->trans($found_something ? '%citizen% hat ein(e,n) %item% ausgegraben!' : '%citizen% hat hier durch Graben nichts gefunden...', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%item%' => $item ? $this->wrap( $this->iconize( $item ) ) : ''
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( ($citizen->getZone()->getBuryCount() === 0) ? TownLogEntry::ClassCritical : TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() )
            ->setText( $this->trans->trans( $bc ? '%citizen% hat etwas Schutt entfernt.' : '%citizen% hat diese Ruine freigelegt. Es handelt sich um %type%. Hurra!', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%type%'    => ($citizen->getZone()->getBuryCount() > 0) ? '' : $this->wrap( $this->trans->trans( $citizen->getZone()->getPrototype()->getLabel(), [], 'game' ) )
            ], 'game' ) );
    }

    public function constructionsBuildingCompleteAllOrNothing( town $town, $tempDef ): TownLogEntry {
        $variables = array('def' => $tempDef);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('constructionsBuildingCompleteAllOrNothing');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Der Bau des Gebäudes Alles oder nichts hat der Stadt %def% vorübergehende Verteidungspunkte eingebracht. Allerdings wurde dabei der gesamte Inhalt der Bank zerstört! Hoffen wir, dass es das wert war...', [
                '%def%' => $this->wrap($tempDef),
            ], 'game' ) );
    }

    public function nightlyInternalAttackKill( Citizen $zombie, Citizen $victim ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'victim' => $victim->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyInternalAttackKill');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie )
            ->setSecondaryCitizen( $victim )
            ->setText( $this->trans->trans('%zombie% ist von den Toten auferstanden und hat %victim% zerfleischt!', [
                '%zombie%' => $this->wrap( $this->iconize( $zombie ) ),
                '%victim%' => $this->wrap( $this->iconize( $victim ) ),
            ], 'game' ) );
    }

    public function nightlyInternalAttackDestroy( Citizen $zombie, Building $building ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'building' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyInternalAttackDestroy');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeConstruction )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie )
            ->setText( $this->trans->trans('%zombie% ist von den Toten auferstanden und hat die Baustelle für %building% verwüstet!', [
                '%zombie%' => $this->wrap( $this->iconize( $zombie ) ),
                '%building%' => $this->wrap( $this->iconize( $building ) ),
            ], 'game' ) );
    }

    public function nightlyInternalAttackWell( Citizen $zombie, int $units ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'num' => $units);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyInternalAttackWell');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeWell )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie )
            ->setText( $this->trans->trans('%zombie% ist von den Toten auferstanden und hat den Brunnen kontaminiert! Das kostet uns %num% Rationen Wasser!', [
                '%zombie%' => $this->wrap( $this->iconize( $zombie ) ),
                '%num%'    => $this->wrap( "{$units}" ),
            ], 'game' ) );
    }

    public function nightlyInternalAttackNothing( Citizen $zombie ): TownLogEntry {
        $list = [
            T::__('%zombie% ist von den Toten auferstanden und hat unanständige Nachrichten auf die Heldentafel geschrieben!', 'game'),
            T::__('%zombie% ist von den Toten auferstanden und hat sich wieder hingelegt!', 'game'),
            T::__('%zombie% ist von den Toten auferstanden und verlangt nach einem Kaffee!', 'game'),
            T::__('%zombie% ist von den Toten auferstanden und hat eine Runde auf dem Stadtplatz gedreht!', 'game'),
        ];
        $str = $list[array_rand($list,1)];

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
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie )
            ->setText( $this->trans->trans($str, [
                '%zombie%' => $this->wrap( $this->iconize( $zombie ) ),
            ], 'game' ) );
    }

    public function nightlyAttackBegin( Town $town, int $num_zombies ): TownLogEntry {
        $variables = array('num' => $num_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackBegin');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Resigniert und untröstlich sahen die Bürger wie eine Horde von %num% Zombies sich in Richtung Stadt bewegte... Wie aus dem Nichts stand die Meute auf einmal vor dem Stadttor...', [
                '%num%' => $this->wrap( "{$num_zombies}" ),
            ], 'game' ) );
    }

    public function nightlyAttackSummary( Town $town, bool $door_open, int $num_zombies ): TownLogEntry {
        
        if ($door_open)
            $str = T::__('... das OFFEN stand! %num% Zombies sind in die Stadt eingedrungen!', 'game');
        else $str = $num_zombies > 0 ? T::__('%num% Zombies sind durch unsere Verteidigung gebrochen!', 'game') : T::__('Nicht ein Zombie hat die Stadt betreten!', 'game');

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
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans($str, [
                '%num%' => $this->wrap( "{$num_zombies}" ),
            ], 'game' ) );
    }

    public function nightlyAttackLazy( Town $town, int $num_attacking_zombies ): TownLogEntry {
        $variables = array('num' => $num_attacking_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackLazy');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('%num% Zombies attackieren die Stadtbewohner!', [
                '%num%' => $this->wrap( "{$num_attacking_zombies}" ),
            ], 'game' ) );
    }

    public function nightlyAttackBuildingDefenseWater( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackBuildingDefenseWater');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeWell )
            ->setClass( TownLogEntry::ClassWarning )
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Der Einsatz von %building% für die Verteidigung der Stadt hat uns %num% Rationen Wasser gekostet.', [
                '%building%' => $this->wrap( $this->iconize( $building ) ),
                '%num%'      => $this->wrap( "{$num}" ),
            ], 'game' ) );
    }

    public function nightlyAttackUpgradeBuildingWell( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackUpgradeBuildingWell');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeWell )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Durch die Verbesserung von %building% wurdem dem Brunnen %num% Rationen Wasser hinzugefügt.', [
                '%building%' => $this->wrap( $this->iconize( $building ) ),
                '%num%'      => $this->wrap( "{$num}" ),
            ], 'game' ) );
    }

    public function nightlyAttackUpgradeBuildingItems( Building $building, ?array $items ): TownLogEntry {
        $items = [];

        $variables = array('building' => $building->getPrototype()->getId(), 
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } ;}, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackUpgradeBuildingItems');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Durch die Verbesserung von %building% hat die Stadt folgende Gegenstände erhalten: %items%', [
                '%building%' => $this->wrap( $this->iconize( $building ) ),
                '%items%'    => implode( ', ', $items )
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Was für ein Glück! Kurz nach dem Angriff wurde ein Bauplan in die Stadt geweht. Ihr erhaltet: %item%', [
                '%item%' => $this->wrap( $this->iconize( $item ) ),
            ], 'game' ) );
    }

    public function nightlyAttackProduction( Building $building, ?array $items = [] ): TownLogEntry {        
        $variables = array('building' => $building->getPrototype()->getId(), 
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } ;}, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackProduction');

        $items = array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $items );
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setSecondaryType( TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Heute Nacht wurden von %building% folgende Gegenstände produziert: %items%', [
                '%building%' => $this->wrap( $this->iconize( $building ) ),
                '%items%'    => implode( ', ', $items )
            ], 'game' ) );
    }

    public function nightlyAttackDestroyBuilding( Town $town, Building $building ): TownLogEntry {
        $variables = array('buildingName' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('nightlyAttackDestroyBuilding');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeNightly )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans('Am Ende der Schlacht zerfiel das Gebäude %buildingName% zu Staub.', [
                '%buildingName%' => $this->wrap( $this->iconize( $building ) ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeHome )
            ->setClass( $complaint->getSeverity() === Complaint::SeverityNone ? TownLogEntry::ClassInfo : TownLogEntry::ClassCritical )
            ->setTown( $complaint->getAutor()->getTown() )
            ->setDay( $complaint->getAutor()->getTown()->getDay() )
            ->setCitizen( $complaint->getCulprit() )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans( $complaint->getSeverity() > Complaint::SeverityNone
                    ? T::__('Jemand hat eine anonyme Anzeige gegen %citizen% vorgebracht!', 'game')
                    : T::__('Jemand hat seine anonyme Anzeige gegen %citizen% zurückgezogen.', 'game'),
                [
                    '%citizen%'  => $this->wrap( $this->iconize( $complaint->getCulprit() ) ),
                ], 'game' ) );
    }

    public function citizenBanish( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenBanish');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeHome )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setCitizen( $citizen )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans( 'Die letzte Anzeige hat das Fass zum Überlaufen gebracht. %citizen% wurde von den Bürgern der Stadt verbannt!' ,
                [
                    '%citizen%'  => $this->wrap( $this->iconize( $citizen ) ),
                ], 'game' ) );
    }

    public function citizenDisposal( Citizen $actor, Citizen $disposed, int $action, ?array $items = [] ): TownLogEntry {
        $str = '';
        switch ($action) {
            case 1:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalDrag');
                $str = T::__('%citizen% hat die Leiche von %disposed% aus der Stadt gezerrt.', 'game');
                break;
            case 2:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalWater');
                $str = T::__('%citizen% hat die Leiche von %disposed% vernichtet, indem er sie mit Wasser übergossen hat.', 'game');
                break;
            case 3:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId(), 
                    'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
                        else { return array('id' => $e[0]->getId()); } ;}, $items ));
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalCremato');
                $str = T::__('%citizen% hat aus der Leiche von %disposed% eine leckere Mahlzeit gegrillt. Die Stadt hat %items% erhalten.', 'game');
                break;
            default:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('citizenDisposalDefault');
                $str = T::__('%citizen% hat die Leiche von %disposed% vernichtet.', 'game');
                break;
        }

        $items = array_map( function($e) { return $this->wrap( $this->iconize( $e ) ); }, $items );

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeHome )
            ->setSecondaryType( empty($items) ? TownLogEntry::TypeVarious : TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $actor->getTown() )
            ->setDay( $actor->getTown()->getDay() )
            ->setCitizen( $actor )
            ->setSecondaryCitizen( $disposed )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans($str, [
                '%citizen%'  => $this->wrap( $this->iconize( $actor ) ),
                '%disposed%' => $this->wrap( $this->iconize( $disposed ) ),
                '%items%'    => implode( ', ', $items )
            ], 'game' ) );
    }

    public function townSteal( Citizen $victim, ?Citizen $actor, Item $item, bool $up, bool $santa = false): TownLogEntry {

        if ($up){
            if($santa){
                $variables = array('victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townStealSanta');
                $str = T::__("Der Weihnachtsmann wurde dabei beobachtet, wie er %item% von %victim% gestohlen hat", 'game');
            } else {
                if ($actor) {
                    $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townStealCaught');
                }
                else {
                    $variables = array('victim' => $victim->getId(), 'item' => $item->getPrototype()->getId());
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('townStealUncaught');
                }
                $str = $actor
                    ? T::__('HALTET DEN DIEB! %actor% ist bei %victim% eingebrochen und hat %item% gestohlen!', 'game')
                    : T::__('VERDAMMT! Es scheint, jemand ist bei %victim% eingebrochen und hat %item% gestohlen...', 'game');
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
            $str = $actor
                ? T::__('%actor% ist bei %victim% eingebrochen und hat %item% hinterlassen...', 'game')
                : T::__('Es scheint, jemand ist bei %victim% eingebrochen und hat %item% hinterlassen...', 'game');
        }
            
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeHome )
            ->setSecondaryType( empty($items) ? TownLogEntry::TypeVarious : TownLogEntry::TypeBank )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $victim->getTown() )
            ->setDay( $victim->getTown()->getDay() )
            ->setCitizen( $victim )
            ->setSecondaryCitizen( $actor )
            ->setTimestamp( new DateTime('now') )
            ->setText( $this->trans->trans( $str , [
                '%actor%'    => $actor ? $this->wrap( $this->iconize( $actor ) ) : '',
                '%victim%'   => $this->wrap( $this->iconize( $victim ) ),
                '%item%'     => $this->wrap( $this->iconize( $item ) ),
            ], 'game' ) );
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
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans(
                $item
                    ? '%citizen% hat mit dem Gegenstand %item% %kills% Zombie(s) getötet.'
                    : 'Schreiend und fuchtelnd hat %citizen% %kills% Zombies getötet.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%item%'    => $item ? $this->wrap( $this->iconize( $item ) ) : '',
                '%kills%'   => $this->wrap( "{$kills}" ),
            ], 'game' ) );
    }

    public function zombieKillShaman( Citizen $citizen, int $kills ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('zombieKillShaman');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassCritical )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans(
                '%citizen% hat %kills% Zombies getötet.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%kills%'   => $this->wrap( "{$kills}" ),
            ], 'game' ) );
    }

    public function beyondChat( Citizen $sender, string $message ): TownLogEntry {
        $variables = array('sender' => $sender->getId(), 'message' => ': ' . htmlentities( $message ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondChat');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeChat )
            ->setClass( TownLogEntry::ClassChat )
            ->setTown( $sender->getTown() )
            ->setDay( $sender->getTown()->getDay() )
            ->setZone( $sender->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $sender )
            ->setText( $this->wrap( $this->iconize( $sender ) ) . ': ' . htmlentities( $message ) );
    }

    public function beyondCampingImprovement( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingImprovement');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat sich ein paar Minuten Zeit genommen, um sein Versteck zu verbessern.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
    }

    public function beyondCampingItemImprovement( Citizen $citizen, Item $item ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingItemImprovement');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat %item% aufgestellt, um das Versteck zu verbessern.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%item%' => $this->wrap( $this->iconize( $item ) ),
            ], 'game' ) );
    }

    public function beyondCampingHide( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingHide');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat sich für heute Nacht ein Versteck gesucht ...', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
    }

    public function beyondCampingUnhide( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondCampingUnhide');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat sein Versteck verlassen.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
    }

    public function beyondEscortEnable( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortEnable');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat beschlossen auf einen Eskortenanführer zu warten...', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
    }

    public function beyondEscortDisable( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortDisable');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat beschlossen sich wieder allein fortzubewegen.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
            ], 'game' ) );
    }

    public function beyondEscortTakeCitizen( Citizen $citizen, Citizen $target_citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'target_citizen' => $target_citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortTakeCitizen');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat %target_citizen% überzeugt, ihm zu folgen.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%target_citizen%' => $this->wrap( $this->iconize( $target_citizen ) ),
            ], 'game' ) );
    }

    public function beyondEscortReleaseCitizen( Citizen $citizen, Citizen $target_citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'target_citizen' => $target_citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneByName('beyondEscortReleaseCitizen');

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setType( TownLogEntry::TypeVarious )
            ->setClass( TownLogEntry::ClassInfo )
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setText( $this->trans->trans('%citizen% hat %target_citizen% aus seiner Eskorte entlassen.', [
                '%citizen%' => $this->wrap( $this->iconize( $citizen ) ),
                '%target_citizen%' => $this->wrap( $this->iconize( $target_citizen ) ),
            ], 'game' ) );
    }
}