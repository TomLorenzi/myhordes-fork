<?php

namespace App\DataFixtures;

use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenStatus;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class CitizenFixtures extends Fixture implements DependentFixtureInterface
{
    public static $profession_data = [
        ['hero' => false, 'icon' => 'looser', 'name'=>'none'        ,'label'=>'Gammler',    'items' => ['basic_suit_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
        ['hero' => false, 'icon' => 'basic',  'name'=>'basic'       ,'label'=>'Einwohner',  'items' => ['basic_suit_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
        ['hero' => true, 'icon' => 'dig',    'name'=>'collec'      ,'label'=>'Buddler',    'items' => ['basic_suit_#00','pelle_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
        ['hero' => true, 'icon' => 'shield', 'name'=>'guardian'    ,'label'=>'Wächter',    'items' => ['basic_suit_#00','shield_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
        ['hero' => true, 'icon' => 'vest',   'name'=>'hunter'      ,'label'=>'Aufklärer',  'items' => ['basic_suit_#00','vest_on_#00'], 'items_alt' => ['basic_suit_dirt_#00','vest_off_#00'] ],
        ['hero' => true, 'icon' => 'tamer',  'name'=>'tamer'       ,'label'=>'Dompteur',   'items' => ['basic_suit_#00','tamed_pet_#00'], 'items_alt' => ['basic_suit_dirt_#00','tamed_pet_drug_#00','tamed_pet_off_#00'] ],
        ['hero' => true, 'icon' => 'tech',   'name'=>'tech'        ,'label'=>'Techniker',  'items' => ['basic_suit_#00','keymol_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
        ['hero' => true, 'icon' => 'shaman', 'name'=>'shaman'      ,'label'=>'Schamane',   'items' => ['basic_suit_#00','shaman_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
        ['hero' => true, 'icon' => 'book',   'name'=>'survivalist' ,'label'=>'Einsiedler', 'items' => ['basic_suit_#00','surv_book_#00'], 'items_alt' => ['basic_suit_dirt_#00'] ],
    ];

    public static $citizen_status = [
        ['name' => 'clean', 'label' => 'Clean'],
        ['name' => 'hasdrunk', 'label' => 'Getrunken'],
        ['name' => 'haseaten', 'label' => 'Satt'],
        ['name' => 'camper', 'label' => 'Umsichtiger Camper'],
        ['name' => 'immune', 'label' => 'Immunisiert'],
        ['name' => 'hsurvive', 'label' => 'Den Tod besiegen'],
        ['name' => 'tired', 'label' => 'Erschöpfung'],
        ['name' => 'terror', 'label' => 'Angststarre'],
        ['name' => 'thirst1', 'label' => 'Durst'],
        ['name' => 'thirst2', 'label' => 'Dehydriert'],
        ['name' => 'drugged', 'label' => 'Rauschzustand'],
        ['name' => 'addict', 'label' => 'Drogenabhängig'],
        ['name' => 'infection', 'label' => 'Infektion'],
        ['name' => 'drunk', 'label' => 'Trunkenheit'],
        ['name' => 'hungover', 'label' => 'Kater'],
        ['name' => 'wound1', 'label' => 'Verwundung - Kopf'],
        ['name' => 'wound2', 'label' => 'Verwundung - Hände'],
        ['name' => 'wound3', 'label' => 'Verwundung - Arme'],
        ['name' => 'wound4', 'label' => 'Verwundung - Bein'],
        ['name' => 'wound5', 'label' => 'Verwundung - Auge'],
        ['name' => 'wound6', 'label' => 'Verwundung - Fuß'],
        ['name' => 'ghul', 'label' => 'Ghul'],
        ['name' => 'healed', 'label' => 'Bandagiert'],

        ['name' => 'tg_dice' ],
        ['name' => 'tg_cards'],
        ['name' => 'tg_meta_wound'],
        ['name' => 'tg_clothes'],
        ['name' => 'tg_teddy'],
        ['name' => 'tg_guitar'],
        ['name' => 'tg_sbook'],
        ['name' => 'tg_steal'],
        ['name' => 'tg_home_upgrade'],
        ['name' => 'tg_never'],
        ['name' => 'tg_hero'],
        ['name' => 'tg_chk_forum'],
        ['name' => 'tg_chk_active'],
        ['name' => 'tg_hide'],
        ['name' => 'tg_tomb'],
    ];

    public static $causes_of_death = [
        [ 'ref' => CauseOfDeath::Unknown      , 'label' => 'Unbekannte Todesursache', 'icon' => 'unknown', 'desc' => 'Du weist nicht, was gerade geschehen ist... plötzlich warst du einfach nicht mehr am leben.' ],
        [ 'ref' => CauseOfDeath::NightlyAttack, 'label' => 'Zombieangriff', 'icon' => 'die2nite', 'desc' => 'Du hattest dich daheim eingebunkert und dachtest so überleben zu können... Als sie vor deiner Türe grunzten und schnüffelten, warst du dann mucksmäuschenstill. Leider hattest du ein Fenster nicht ausreichend vernagelt, sodass sie schließlich in dein Wohnzimmer stolperten. Vor Angst erstarrt hast du dann keinen Ton mehr rausgebracht und musstest ansehen, wie sie dich ins Schlafzimmer zerrten, um sich an deinen inneren Organen zu laben und dich langsam zu verspeisen.' ],
        [ 'ref' => CauseOfDeath::Vanished     , 'label' => 'Außerhalb der Stadt verschwunden', 'icon' => 'vanish', 'desc' => 'Kurz nach Mitternacht wurde es unheimlich still... so, als ob jegliches Leben mit einem Schlag ausgelöscht worden wäre. Die Nacht kam dir noch eisiger als sonst vor... und plötzlich sahst du sie: Dutzende, hunderte, und alle schwankten auf dich zu! Ihre Hände reckten sich nach dir. Sie ergriffen, zogen und rissen dich auseinander... Dann haben sie dich mitgenommmen und sich an dir bis zum frühen Morgen gütlich getan.' ],
        [ 'ref' => CauseOfDeath::Dehydration  , 'label' => 'Dehydration', 'icon' => 'dehydrated', 'desc' => 'Dehydratation ist wirklich was Übles. Muskelspasmen, Atemprobleme und unaushaltbare Gliederschmerzen treten in der finalen Phase auf. Du hast nach etwas Trinkbarem gesucht - überall.... Vergeblich. Deine Mitbürger behaupten sogar, dich dabei gesehen zu haben, wie du in den letzten Minuten schaufelweise Sand in dich geschüttet hättest... Herrje...' ],
        [ 'ref' => CauseOfDeath::GhulStarved  , 'label' => 'Verhungert', 'icon' => 'starved', 'desc' => 'Von Hungerschmerzen geplagt bist du den ganzen Tag durch die Stadt getorkelt... immer auf der Suche nach einem Bissen Fleisch...doch vergebens. Am Ende des Tages haben dich deine Kräfte dann schließlich verlassen. Langsam, ganz langsam, bist du zu Boden gesunken. Selbst deine letzten Atemzüge waren eine Pein... Das Letzte was die Welt noch von dir vernahm war ein unmenschlicher, gellender Schrei gen Himmel und das warst du tot.' ],
        [ 'ref' => CauseOfDeath::Addiction    , 'label' => 'Drogenabhängigkeit', 'icon' => 'addicted', 'desc' => 'Drogen sind echt was Tolles, solange du genügend hast. Wenn sie dir aber ausgehen, sieht\'s schon anders aus... Schweißausbrüche, Panikattacken, Zittern... du rennst alle zehn Minuten aufs Klo, um dich zu übergeben. Am Ende, wenn du deinen Verstand bereits verloren hast, schluckst du dann Steine, weil du sie mit Steroiden verwechselst.' ],
        [ 'ref' => CauseOfDeath::Infection    , 'label' => 'Infektion', 'icon' => 'infection', 'desc' => 'Stück für Stück hat dich die Krankheit von innen her aufgefressen... Die Infektionssymptome waren auch nicht mehr zu übersehen: eiternde Wunden, Hematome, faulendes Fleisch... Nach mehreren Stunden Leiden hast du den Tod schließlich als Erleichterung empfunden.' ],
        [ 'ref' => CauseOfDeath::Cyanide      , 'label' => 'Zyanid', 'icon' => 'cyanide', 'desc' => 'Du dachtest dir: "Bevor ich noch eine weitere Minute mit diesen Losern verbringe, kürze ich das Ganze ein wenig ab...". Was soll\'s? Einen Tag mehr oder weniger, was macht das schon für einen Unterschied...' ],
        [ 'ref' => CauseOfDeath::Posion       , 'label' => 'Ermordung durch Gift', 'icon' => 'poison', 'desc' => 'Sorglos hast du dieses Produkt runtergeschluckt... Das darin enthaltene Gift hat nur wenige Sekunden gebraucht, um über dein Blutkreislaufsystem zum Herzen zu gelangen. Du hast dein Bewusstsein verloren. Atemstillstand und Herzversagen haben dir dann den Rest gegeben. Du wurdest vergifet!! Wie hinterhältig!' ],
        [ 'ref' => CauseOfDeath::GhulEaten    , 'label' => 'Mord', 'icon' => 'eaten', 'desc' => 'Argh!! Es scheint, als ob sich ein Ghul unter die Einwohner gemischt hätte! Völlig ahnungs- und wehrlos wurdest du von etwas oder jemandem angefallen und übel vermöbelt. Als er dann seine fauligen Zähne in deinen Hals schlug warst du noch bei Bewusstsein... Doch du hattest nur noch einen Gedanken: Wer war das?' ],
        [ 'ref' => CauseOfDeath::GhulBeaten   , 'label' => 'Aggression', 'icon' => 'beaten', 'desc' => 'Du wurdest übel zusammengeschlagen und bist an deinen inneren Verletzungen gestorben... Dir gings eh nicht mehr so gut, dein Immunsystem war bereits geschwächt, da steckt man Schläge nicht mehr so einfach weg. So ein Mist aber auch, mit deiner besonderen Fähigkeit hättest du in deiner Stadt noch ein Weilchen Furcht und Schrecken verbreiten können...' ],
        [ 'ref' => CauseOfDeath::Hanging      , 'label' => 'Erhängt', 'icon' => 'hanged', 'desc' => 'Ein paar Leute in der Stadt mochten dich ganz offensichtlich nicht, darunter befanden sich mehrere Mitbürger, deine Nachbarn - ja sogar ein paar von deinen Freunden! Aus diesem Grund haben sie dich zu einer Baustelle geschleppt und dir einen Strick um den Hals gelegt. Das ganze ging ruck zuck. Innerhalb weniger Sekunden bist du einen Meter über den Boden geschwebt. Nach einem kurzen Applaus löste sich die Gruppe auf und jeder kehrte zu seiner Arbeit zurück.' ],
        [ 'ref' => CauseOfDeath::FleshCage    , 'label' => 'Im Fleischkäfig geendet', 'icon' => 'caged', 'desc' => 'Ein paar Leute in der Stadt mochten dich ganz offensichtlich nicht, darunter befanden sich mehrere Mitbürger, deine Nachbarn - ja sogar ein paar von deinen Freunden! Aus diesem Grund haben sie dich zur Stadtmauer geschleppt, dich in einen Käfig gesteckt und diesen über den Rand gestoßen. Das ganze ging ruck zuck. Jetzt bist du wohl Zombiefutter... Nach einem kurzen Applaus löste sich die Gruppe auf und jeder kehrte zu seiner Arbeit zurück.' ],
        [ 'ref' => CauseOfDeath::Strangulation, 'label' => 'Strangulation', 'icon' => 'strangulation', 'desc' => 'Grüße von Brainbox aus der Vergangenheit, der am 22.02.2020 vor seinem PC sitzt und diesen Text schreibt.' ],
        [ 'ref' => CauseOfDeath::Headshot     , 'label' => 'Kopfschuss', 'icon' => 'headshot', 'desc' => 'Das hast du wohl nicht erwartet... Wie wäre es, wenn du dich das nächste mal an die Regeln hälst?' ],
    ];

    public static $home_levels = [
        0 => [ 'label' => 'Feldbett',              'icon' => 'home_lv0', 'def' =>  0, 'ap' => 0, 'resources' => [], 'building' => null, 'upgrades' => false, 'theft' => false ],
        1 => [ 'label' => 'Zelt',                  'icon' => 'home_lv1', 'def' =>  1, 'ap' => 2, 'resources' => [], 'building' => null, 'upgrades' => true,  'theft' => false ],
        2 => [ 'label' => 'Baracke',               'icon' => 'home_lv2', 'def' =>  3, 'ap' => 6, 'resources' => [], 'building' => null, 'upgrades' => true,  'theft' => false ],
        3 => [ 'label' => 'Hütte',                 'icon' => 'home_lv3', 'def' =>  6, 'ap' => 4, 'resources' => ['wood2_#00' => 1], 'building' => null, 'upgrades' => true,  'theft' => false ],
        4 => [ 'label' => 'Haus',                  'icon' => 'home_lv4', 'def' => 10, 'ap' => 6, 'resources' => ['metal_#00' => 1], 'building' => null, 'upgrades' => true,  'theft' => false ],
        5 => [ 'label' => 'Umzäuntes Haus',        'icon' => 'home_lv5', 'def' => 14, 'ap' => 6, 'resources' => ['wood2_#00' => 3, 'metal_#00' => 2], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
        6 => [ 'label' => 'Befestigte Unterkunft', 'icon' => 'home_lv6', 'def' => 20, 'ap' => 7, 'resources' => ['concrete_wall_#00' => 1, 'wood2_#00' => 3, 'metal_#00' => 4], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
        7 => [ 'label' => 'Bunker',                'icon' => 'home_lv7', 'def' => 28, 'ap' => 7, 'resources' => ['meca_parts_#00' => 3, 'concrete_wall_#00' => 2, 'plate_raw_#00' => 1, 'metal_#00' => 6], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
        8 => [ 'label' => 'Schloss',               'icon' => 'home_lv8', 'def' => 50, 'ap' => 7, 'resources' => ['meca_parts_#00' => 5, 'concrete_wall_#00' => 2, 'plate_raw_#00' => 3, 'wood2_#00' => 5, 'metal_#00' => 10], 'building' => 'small_strategy_#01', 'upgrades' => true, 'theft' => true ],
    ];

    public static $home_upgrades = [
        [ 'name' => 'curtain', 'label' => 'Großer Vorhang', 'desc' => 'Mit dieser alten, schmutzigen Jutesackleinwand kannst du deine Habseligkeiten vor den neugierigen Blicken deiner Nachbarn schützen.', 'levels' => [
            1 => [ 4, [] ]
        ] ],
        [ 'name' => 'lab', 'label' => 'Hobbylabor', 'desc' => 'Ein in dein Wohnzimmer geschaufeltes Loch dient dir als Versuchsküche für deine pharmazeutischen Experimente.', 'levels' => [
            1 => [ 6, ['machine_1_#00' => 1] ], 2 => [ 4, ['electro_#00' => 1] ], 3 => [ 4, ['tube_#00' => 1] ], 4 => [ 6, ['engine_#00' => 1] ]
        ] ],
        [ 'name' => 'kitchen', 'label' => 'Küche', 'desc' => 'In dieser notdürftig zusammengeschraubten Küche können schmackhafte und \'gesunde\' Speisen zubereitet werden.', 'levels' => [
            1 => [ 6, [] ], 2 => [ 3, ['small_knife_#00' => 1]], 3 => [ 4, ['machine_2_#00' => 1] ], 4 => [ 4, ['machine_3_#00' => 1]]
        ] ],
        [ 'name' => 'alarm', 'label' => 'Primitives Alarmsystem', 'desc' => 'Eisenteile, die an einem Faden hängen - so einfach und so effektiv kann ein Alarmsystem sein. Wenn jemand versuchen sollte, bei dir einzubrechen, wird er zwangsläufig die halbe Stadt aufwecken...', 'levels' => [
            1 => [ 4, ['metal_#00' => 1] ]
        ] ],
        [ 'name' => 'rest', 'label' => 'Ruheecke', 'desc' => 'Was hier als \'Ruhe-Ecke\' bezeichnet wird, ist in Wahrheit nichts anderes als ein mit Kartons gefülltes Loch im Boden... der ideale Ort, wenn deine Kräfte schwinden und du dich für ein Nickerchen zurückziehen willst.', 'levels' => [
            1 => [ 6, [] ], 2 => [ 3, ['wood2_#00' => 1] ], 3 => [ 4, ['bed_#00' => 1] ]
        ] ],
        [ 'name' => 'lock', 'label' => 'Türschloss', 'desc' => 'Dieses rudimentäre Schließsystem schützt dein Haus vor Diebstahl.', 'levels' => [
            1 => [ 6, ['chain_#00' => 1] ]
        ] ],
        [ 'name' => 'fence', 'label' => 'Zaun', 'desc' => 'Wenn dich deine Wände nicht mehr ausreichend schützen, solltest du den Bau eines Zauns erwägen.', 'levels' => [
            1 => [ 3, ['chain_#00' => 1, 'metal_beam_#00' => 1] ]
        ] ],
        [ 'name' => 'chest', 'label' => 'Stauraum', 'desc' => 'Deine persönliche Truhe vergrößert sich. ', 'levels' => [
            1 => [ 2, [] ], 2 => [ 2, [] ], 3 => [ 2, [] ], 4 => [ 3, [] ], 5 => [ 4, [] ], 6 => [ 6, [] ], 7 => [ 6, [] ], 8 => [ 6, [] ], 9 => [ 6, [] ], 10 => [ 6, [] ], 11 => [ 6, [] ], 12 => [ 6, [] ], 13 => [ 6, [] ]
        ] ],
        [ 'name' => 'defense', 'label' => 'Verstärkungen', 'desc' => 'Dein Haus wird mit allen zur Verfügung stehenden Mitteln technisch verstärkt und auf Vordermann gebraucht. Diese Maßnahmen verlängern dein Leben... zumindest ein wenig.', 'levels' => [
            1 => [ 3, [] ], 2 => [ 3, ['fence_#00' => 1] ], 3 => [ 3, ['fence_#00' => 1] ], 4 => [ 3, ['fence_#00' => 1] ], 5 => [ 6, ['fence_#00' => 1] ], 6 => [ 6, ['fence_#00' => 1] ], 7 => [ 6, ['fence_#00' => 1, 'metal_#00' => 1] ], 8 => [ 6, ['fence_#00' => 1, 'metal_#00' => 1] ], 9 => [ 6, ['fence_#00' => 1, 'metal_#00' => 1] ], 10 => [ 6, ['fence_#00' => 1, 'metal_#00' => 1] ]
        ] ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_professions(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Citizen professions: ' . count(static::$profession_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$profession_data) );

        // Iterate over all entries
        foreach (static::$profession_data as $entry) {
            // Get existing entry, or create new one
            /** @var CitizenProfession $entity */
            $entity = $this->entityManager->getRepository(CitizenProfession::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new CitizenProfession();
            else {
                $entity->getProfessionItems()->clear();
                $entity->getAltProfessionItems()->clear();
            }

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setHeroic( $entry['hero'] );

            foreach ( $entry['items'] as $p_item ) {
                $i = $manager->getRepository(ItemPrototype::class)->findOneByName( $p_item );
                if (!$i) throw new Exception('Item prototype not found: ' . $p_item);
                $entity->addProfessionItem($i);
            }

            foreach ( $entry['items_alt'] as $p_item ) {
                $i = $manager->getRepository(ItemPrototype::class)->findOneByName( $p_item );
                if (!$i) throw new Exception('Item prototype not found: ' . $p_item);
                $entity->addAltProfessionItem($i);
            }

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_status_types(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Status: ' . count(static::$citizen_status) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$citizen_status) );

        // Iterate over all entries
        foreach (static::$citizen_status as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CitizenStatus::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new CitizenStatus();

            // Set property
            $entity->setName( $entry['name'] );
            $entity->setLabel( isset($entry['label']) ? $entry['label'] : $entry['name'] );
            $entity->setIcon( isset($entry['icon']) ? $entry['icon'] : $entry['name'] );
            $entity->setHidden( !isset($entry['label']) );

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_home_prototypes(ObjectManager $manager, ConsoleOutputInterface $out)
    {
        $out->writeln('<comment>Home Prototypes: ' . count(static::$home_levels) . ' fixture entries available.</comment>');

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$citizen_status) );

        // Iterate over all entries
        foreach (static::$home_levels as $level => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CitizenHomePrototype::class)->findOneByLevel( $level );
            if ($entity === null) $entity = new CitizenHomePrototype();

            $entity->setLevel($level)->setAp( $entry['ap'] )->setIcon( $entry['icon'] )
                ->setAllowSubUpgrades( $entry['upgrades'] )->setDefense( $entry['def'] )->setLabel( $entry['label'] )
                ->setTheftProtection( $entry['theft'] );

            $building = empty($entry['building']) ? null : $manager->getRepository(BuildingPrototype::class)->findOneByName( $entry['building'] );
            if (!empty($building) && !$building) throw new Exception("Unable to locate building prototype '{$entry['building']}'");
            $entity->setRequiredBuilding( $building );

            if (empty($entry['resources'])) {
                if ($entity->getResources()) {
                    $manager->remove( $entity->getResources() );
                    $entity->setResources( null );
                }
            } else {

                if ($entity->getResources()) $entity->getResources()->getEntries()->clear();
                else $entity->setResources( (new ItemGroup())->setName( "hu_{$level}_res" ) );

                foreach ( $entry['resources'] as $item => $count ) {

                    $ip = $manager->getRepository(ItemPrototype::class)->findOneByName( $item );
                    if (!$item) throw new Exception("Unable to locate item prototype '{$item}'");
                    $entity->getResources()->addEntry( (new ItemGroupEntry())->setPrototype( $ip )->setChance( $count ) );

                }

            }
            // Persist
            $manager->persist($entity);

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_home_upgrades(ObjectManager $manager, ConsoleOutputInterface $out)
    {
        $out->writeln('<comment>Home Upgrades: ' . count(static::$home_upgrades) . ' fixture entries available.</comment>');

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$citizen_status) );

        // Iterate over all entries
        foreach (static::$home_upgrades as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new CitizenHomeUpgradePrototype();

            $entity->setName( $entry['name'] )->setLabel( $entry['label'] )->setDescription( $entry['desc'] )
                ->setIcon( $entry['icon'] ?? $entry['name'] );

            // Persist & flush
            $manager->persist($entity);
            $manager->flush();

            // Refresh
            $entity = $this->entityManager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( $entry['name'] );

            foreach ( $entry['levels'] as $level => $res ) {
                $lv_entry = $manager->getRepository(CitizenHomeUpgradeCosts::class)->findOneByPrototype( $entity, $level );
                if (!$lv_entry) $lv_entry = (new CitizenHomeUpgradeCosts())->setPrototype($entity)->setLevel( $level );

                $lv_entry->setAp( $res[0] );
                if (empty($res[1])) {
                    if ($lv_entry->getResources()) {
                        $manager->remove( $lv_entry->getResources() );
                        $lv_entry->setResources( null );
                    }
                } else {

                    if ($lv_entry->getResources()) $lv_entry->getResources()->getEntries()->clear();
                    else $lv_entry->setResources( (new ItemGroup())->setName( "hu_{$entry['name']}_{$level}_res" ) );

                    foreach ( $res[1] as $item => $count ) {

                        $ip = $manager->getRepository(ItemPrototype::class)->findOneByName( $item );
                        if (!$item) throw new Exception("Unable to locate item prototype '{$item}'");
                        $lv_entry->getResources()->addEntry( (new ItemGroupEntry())->setPrototype( $ip )->setChance( $count ) );

                    }
                }

                $manager->persist( $lv_entry );
            }

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_cod(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Causes of Death: ' . count(static::$causes_of_death) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$causes_of_death) );

        // Iterate over all entries
        foreach (static::$causes_of_death as $entry) {
            // Get existing entry, or create new one
            /** @var CauseOfDeath $entity */
            $entity = $this->entityManager->getRepository(CauseOfDeath::class)->findOneByRef( $entry['ref'] );
            if ($entity === null) $entity = (new CauseOfDeath())->setRef( $entry['ref'] );

            // Set property
            $entity
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setDescription( $entry['desc'] );

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }


    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();

        try {
            $output->writeln( '<info>Installing fixtures: Citizen Database</info>' );
            $output->writeln("");

            $this->insert_professions( $manager, $output );
            $output->writeln("");
            $this->insert_status_types( $manager, $output );
            $output->writeln("");

            $this->insert_home_prototypes($manager, $output);
            $output->writeln("");
            $this->insert_home_upgrades($manager, $output);
            $output->writeln("");

            $this->insert_cod($manager, $output);
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

    }

    public function getDependencies()
    {
        return [ RecipeFixtures::class, ItemFixtures::class ];
    }
}
