<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\DigTimer;
use App\Entity\Forum;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\ItemGroup;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\WellCounter;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class GameFactory
{
    private $entity_manager;
    private $validator;
    private $locksmith;
    private $item_factory;
    private $status_factory;
    private $random_generator;
    private $inventory_handler;
    private $citizen_handler;
    private $zone_handler;
    private $town_handler;
    private $log;

    const ErrorNone = 0;
    const ErrorTownClosed          = ErrorHelper::BaseTownSelectionErrors + 1;
    const ErrorUserAlreadyInGame   = ErrorHelper::BaseTownSelectionErrors + 2;
    const ErrorUserAlreadyInTown   = ErrorHelper::BaseTownSelectionErrors + 3;
    const ErrorNoDefaultProfession = ErrorHelper::BaseTownSelectionErrors + 4;



    public function __construct(
        EntityManagerInterface $em, GameValidator $v, Locksmith $l, ItemFactory $if, TownHandler $th,
        StatusFactory $sf, RandomGenerator $rg, InventoryHandler $ih, CitizenHandler $ch, ZoneHandler $zh, LogTemplateHandler $lh)
    {
        $this->entity_manager = $em;
        $this->validator = $v;
        $this->locksmith = $l;
        $this->item_factory = $if;
        $this->status_factory = $sf;
        $this->random_generator = $rg;
        $this->inventory_handler = $ih;
        $this->citizen_handler = $ch;
        $this->zone_handler = $zh;
        $this->town_handler = $th;
        $this->log = $lh;
    }

    private static $town_name_snippets = [
        [
            ['Tödliches','Modriges','Schimmliges','Eisiges','Rotes','Einsames','Ghulverseuchtes','Zombifiziertes','Bekanntes','Abgenagtes','Verstörendes','Letztes'],
            ['Wasserloch','Hospital','Trainingslager','Pony','Niemandsland','Gericht','Reich','Dreckloch','Gehirn','Rattenloch','Gebiet','Lager'],
        ],
        [
            ['Eifriger','Weinender','Schimmliger','Alberner','Autoritärer','Einsamer','Triefender','Kontaminierter','Verschlafener','Masochistischer','Besoffener','Kontaminierter'],
            ['Müllberg','Seelenfänger','Monarch','Fels','Untergang','Wald','Folterkeller','Bezirk','Bunker','Tisch','Husten','Laster'],
        ],
        [
            ['Eifrige','Modrige','Glitschige','Eisige','Drogensüchtige','Gespenstische','Ghulverseuchte','Zombifizierte','Bewegte','Betrunkene','Virulente','Betroffene'],
            ['Metzger','Zombieforscher','Gestalten','Wächter','Todesgesänge','Schaffner','Soldaten','Zwillinge','Regionen','Oberfläche','Schmarotzer','Entwickler'],
        ],
        [
            ['Ghulgebeine','Gesänge','Schmerzen','Schreie','Räume','Meute','Ghetto','Bürger','Hinterlassenschaft','Revier','Folterkeller','Alkoholpanscher'],
            ['des Todes','der Verdammnis','ohne Zukunft','am Abgrund','der Verwirrten','ohne Ideen','der Versager','der Ghule','der Superhelden','der Mutlosen','der Fröhlichen','der Revolutionäre'],
        ],
    ];

    public function createTownName(): string {
        return implode(' ', array_map(function(array $list): string {
            return $list[ array_rand( $list ) ];
        }, static::$town_name_snippets[array_rand( static::$town_name_snippets )]));
    }

    private function getDefaultWell(TownClass $town_type): int {
        return mt_rand( $town_type->getWellMin(), $town_type->getWellMax() );
    }

    private function getDefaultRuinCount(TownClass $town_type): int {
        return mt_rand( $town_type->getRuinsMin(), $town_type->getRuinsMax() );
    }

    private function getDefaultZoneResolution( TownClass $town_type, ?int &$offset_x, ?int &$offset_y ): int {
        $resolution = mt_rand( $town_type->getMapMin(), $town_type->getMapMax() );
        $safe_border = ceil($resolution/4.0);
        $offset_x = $safe_border + mt_rand(0, $resolution - 2*$safe_border);
        $offset_y = $safe_border + mt_rand(0, $resolution - 2*$safe_border);
        return $resolution;
    }

    public function createTown( ?string $name, int $population, string $type ): ?Town {
        if (!$this->validator->validateTownType($type) || !$this->validator->validateTownPopulation( $population, $type ))
            return null;

        $townClass = $this->entity_manager->getRepository(TownClass::class)->findOneByName( $type );
        $town = new Town();
        $town
            ->setType( $townClass )
            ->setPopulation( $population )
            ->setName( $name ?: $this->createTownName() )
            ->setBank( new Inventory() )
            ->setWell( $this->getDefaultWell($townClass) );

        foreach ($this->entity_manager->getRepository(BuildingPrototype::class)->findProspectivePrototypes($town, 0) as $prototype)
            $this->town_handler->addBuilding( $town, $prototype );

        $this->town_handler->calculate_zombie_attacks( $town, 3 );

        $map_resolution = $this->getDefaultZoneResolution( $townClass, $ox, $oy );
        for ($x = 0; $x < $map_resolution; $x++)
            for ($y = 0; $y < $map_resolution; $y++) {
                $zone = new Zone();
                $zone
                    ->setX( $x - $ox )
                    ->setY( $y - $oy )
                    ->setFloor( new Inventory() )
                    ->setDiscoveryStatus( ($x - $ox == 0 && $y - $oy == 0) ? Zone::DiscoveryStateCurrent : Zone::DiscoveryStateNone )
                    ->setZombieStatus( ($x - $ox == 0 && $y - $oy == 0) ? Zone::ZombieStateExact : Zone::ZombieStateUnknown )
                    ->setZombies( 0 )
                    ->setInitialZombies( 0 )
                ;
                $town->addZone( $zone );
            }

        $spawn_ruins = $this->getDefaultRuinCount( $townClass );
        /** @var Zone[] $zone_list */
        $zone_list = array_filter($town->getZones()->getValues(), function(Zone $z) {return $z->getX() !== 0 || $z->getY() !== 0;});
        shuffle($zone_list);

        $previous = [];

        for ($i = 0; $i < min($spawn_ruins+2,count($zone_list)); $i++) {
            $zombies_base = 0;
            if ($i < $spawn_ruins) {
                $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i]->getX(),2) + pow($zone_list[$i]->getY(),2) )/18) * 18);

                $ruin_types = $this->entity_manager->getRepository(ZonePrototype::class)->findByDistance( abs($zone_list[$i]->getX()) + abs($zone_list[$i]->getY()) );

                $iterations = 0;
                do {
                    $target_ruin = $this->random_generator->pickLocationFromList( $ruin_types );
                    $iterations++;
                } while ( isset( $previous[$target_ruin->getId()] ) && $iterations <= $previous[$target_ruin->getId()] );

                if (!isset( $previous[$target_ruin->getId()] )) $previous[$target_ruin->getId()] = 1;
                else $previous[$target_ruin->getId()]++;

                $zone_list[$i]->setPrototype( $target_ruin );

                if ($this->random_generator->chance(0.4)) $zone_list[$i]->setBuryCount( mt_rand(6, 20) );
            } else
                $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i]->getX(),2) + pow($zone_list[$i]->getY(),2) )/18) * 3);

            if ($zombies_base > 0) {
                $zombies_base = max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) );
                $zone_list[$i]->setZombies( $zombies_base )->setInitialZombies( $zombies_base );
            }
        }

        $item_spawns = [
            'bplan_box_e_#00', 'bplan_box_e_#00', 'bplan_box_#00',
            'bplan_box_#00', 'bplan_box_#00', 'bplan_box_#00', 'bplan_box_#00'
        ];
        shuffle($zone_list);
        for ($i = 0; $i < min(count($item_spawns),count($zone_list)); $i++)
            $zone_list[$i]->getFloor()->addItem( $this->item_factory->createItem( $item_spawns[$i] ) );

        $this->zone_handler->dailyZombieSpawn( $town, 1, ZoneHandler::RespawnModeNone );

        $town->setForum( (new Forum())->setTitle( $town->getName() ) );

        return $town;
    }

    public function createCitizen( Town &$town, User &$user, ?int &$error ): ?Citizen {
        $error = self::ErrorNone;
        $lock = $this->locksmith->waitForLock('join-town');

        $active_citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser( $user );
        if ($active_citizen !== null) {
            $error = self::ErrorUserAlreadyInGame;
            return null;
        }

        if (!$town->isOpen()) {
            $error = self::ErrorTownClosed;
            return null;
        }
        foreach ($town->getCitizens() as $existing_citizen)
            if ($existing_citizen->getUser()->getId() === $user->getId()) {
                $error = self::ErrorUserAlreadyInTown;
                return null;
            }

        $base_profession = $this->entity_manager->getRepository(CitizenProfession::class)->findDefault();
        if ($base_profession === null) {
            $error = self::ErrorNoDefaultProfession;
            return null;
        }

        $home = new CitizenHome();
        $home
            ->setChest( $chest = new Inventory() )
            ->setPrototype( $this->entity_manager->getRepository( CitizenHomePrototype::class )->findOneByLevel(0) )
            ;

        $citizen = new Citizen();
        $citizen->setUser( $user )
            ->setTown( $town )
            ->setInventory( new Inventory() )
            ->setHome( $home )
            ->setCauseOfDeath( $this->entity_manager->getRepository( CauseOfDeath::class )->findOneByRef( CauseOfDeath::Unknown ) )
            ->setWellCounter( new WellCounter() );
        (new Inventory())->setCitizen( $citizen );
        $this->citizen_handler->inflictStatus( $citizen, 'clean' );

        $this->citizen_handler->applyProfession( $citizen, $base_profession );

        $chest
            ->addItem( $this->item_factory->createItem( 'chest_citizen_#00' ) )
            ->addItem( $this->item_factory->createItem( 'food_bag_#00' ) );

        $heroic_actions = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findAll();
        foreach ($heroic_actions as $heroic_action)
            /** @var $heroic_action HeroicActionPrototype */
            $citizen->addHeroicAction( $heroic_action );

        $this->entity_manager->persist( $this->log->citizenJoin( $citizen ) );

        return $citizen;
    }
}