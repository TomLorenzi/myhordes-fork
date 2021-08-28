<?php


namespace App\DataFixtures;


use App\Entity\HeroSkillPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class HeroSkillFixtures extends Fixture {

    protected static $hero_skills = [
        ['name' => 'manipulator', 'title' => 'Tipp-Ex', 'description' => 'Du kannst 2 Mal pro Partie einen Registereintrag unkenntlich machen. Dazu musst du nur auf das kleine Icon "Fälschen" klicken. Dieses befindet sich links neben dem "störenden" Registereintrag. ;-)', 'icon' => 'small_falsify', 'daysNeeded' => 3],
        ['name' => 'clairvoyance', 'title' => 'Hellseherei', 'description' => 'Du erfährst, wie aktiv ein bestimmter Bürger in deiner Stadt spielt. Du musst dazu lediglich bei ihm daheim vorbeischauen...', 'icon' => 'small_view', 'daysNeeded' => 7],
        ['name' => 'writer', 'title' => 'Rundbrief', 'description' => 'Mit dieser Funktion kannst du alle Stadteinwohner auf einmal anschreiben. Du kannst damit in den Foren zudem Rollenspielthreads (RP) erstellen (Übertreibe es aber bitte nicht - Danke!).', 'icon' => 'item_rp_sheets', 'daysNeeded' => 16],
        ['name' => 'brothers', 'title' => 'Freundschaft', 'description' => 'Mit dieser Fähigkeit kannst du 1 Mal pro Partie (Stadt) jemandem einen Heldentag spendieren! Die anderen werden in dir den Messias erkennen! BEACHTE: Verschenkte Heldentage werden dir von deinem Konto abgezogen. Um jemandem einen Heldentag zu schenken, musst mit ihm in der gleichen Zone stehen oder, wenn du in der Stadt bist, ihn daheim besuchen.', 'icon' => 'r_share', 'daysNeeded' => 25],
        ['name' => 'dictator', 'title' => 'Diktator', 'description' => 'Schreibe auf die Tafel, die sich auf der Übersichtsseite deiner Stadt befindet. Darüberhinaus kannst du auch deinen Mitbürgern ein Bauprojekt empfehlen. Das Gebäude mit den meisten Empfehlungen wird auf der Bauseite der Stadt hervorgehoben.', 'icon' => 'small_chat', 'daysNeeded' => 31],
        ['name' => 'largechest1', 'title' => 'Große Truhe', 'description' => 'Du erhältst einen zusätzlichen Platz in deiner Truhe.', 'icon' => 'item_home_box', 'daysNeeded' => 45],
        ['name' => 'healthybody', 'title' => 'Top in Form', 'description' => 'Solange du "Clean" bist (sprich: Du hast in der Partie noch keine Drogen zu Dir genommen.), verfügst du über einen zusätzlichen Zonenkontrollpunkt in der Außenwelt.', 'icon' => 'status_clean', 'daysNeeded' => 61],
        ['name' => 'omniscience', 'title' => 'Allwissenheit', 'description' => 'Allwissenheit funktioniert wie Hellseherei, außer, dass du jetzt eine Übersicht zur Aktivität aller Stadteinwohner bekommst. Klicke dazu in der Bürgerliste einfach auf den Button "Allwissenheit".', 'icon' => 'small_view', 'daysNeeded' => 75],
        ['name' => 'resourcefulness', 'title' => 'Einfallsreichtum', 'description' => 'Du beginnst jede neue Stadt mit einem zusätzlichen nützlichen Gegenstand.', 'icon' => 'item_chest_hero', 'daysNeeded' => 91],
        ['name' => 'largechest2', 'title' => 'Doppelter Boden', 'description' => 'Du bekommst einen weiteren Platz in deiner Truhe.', 'icon' => 'item_home_box', 'daysNeeded' => 105],
        ['name' => 'luckyfind', 'title' => 'Schönes Fundstück', 'description' => 'Mit deiner Heldenfähigkeit "Fundstück" stöberst du jetzt NOCH BESSERE Gegenstände auf.', 'icon' => 'item_chest_hero', 'daysNeeded' => 121],
        ['name' => 'largerucksack1', 'title' => 'Aufgeräumte Tasche', 'description' => 'Du bekommst einen zusätzlichen Platz in deinem Rucksack.', 'icon' => 'item_bag', 'daysNeeded' => 135],
        ['name' => 'secondwind', 'title' => 'Zweite Lunge', 'description' => 'Du verfügst ab sofort über eine mächtige Heldenfähigkeit, mit der du 6 AP wiederherstellen kannst und die deine Müdigkeit aufhebt.', 'icon' => 'small_pa', 'daysNeeded' => 151],
        ['name' => 'breakfast1', 'title' => 'Weitsichtig', 'description' => 'Du beginnst jede neue Stadt mit einer zusätzlichen Nahrungsmittelration.', 'icon' => 'item_food_bag', 'daysNeeded' => 165],
        ['name' => 'apag', 'title' => 'Profi-Fotograph', 'description' => 'Du beginst jede neue Stadt mit einer Kamera aus Vorkriegstagen.', 'icon' => 'f_cam', 'daysNeeded' => 181],
        ['name' => 'brick', 'title' => 'Panzerschrank', 'description' => 'Dank deiner zahlreichen Zombiekontakte verfügst du in der Außenwelt ab sofort über einen zusätzlichen Zonenkontrollpunkt.', 'icon' => 'item_shield', 'daysNeeded' => 195],
        ['name' => 'treachery', 'title' => 'Hinterhältigkeit', 'description' => 'Die "Tipp-Ex" Fähigkeit wird noch besser! Ab sofort kannst du pro Partie (Stadt) noch mehr Registereinträge fälschen! Du kannst ab sofort 4 Mal pro Partie einen Registereintrag unkenntlich machen.', 'icon' => 'small_falsify', 'daysNeeded' => 211],
        ['name' => 'cheatdeath', 'title' => 'Den Tod besiegen', 'description' => 'Sobald diese neue Heldenfähigkeit aktiviert wurde, spürst du beim nächsten Zombieangriff keinen Durst, keine Drogenabhängigkeit und keine Infektion (nur eine Nacht lang gültig). Verhindert auch Ghulverhungern (Hungerbalken steigt trotzdem).', 'icon' => 'small_wrestle', 'daysNeeded' => 226],
        ['name' => 'revenge', 'title' => 'Süße Rache', 'description' => 'Solltest du am dritten Tag oder an einem späteren Zeitpunkt verbannt werden, bekommst du automatisch etwas Gift geschenkt, das du nach Belieben einsetzen kannst... Tja, man hätte dich besser nicht ärgern sollen!', 'icon' => 'item_april_drug', 'daysNeeded' => 301],
        ['name' => 'procamp', 'title' => 'Proficamper', 'description' => 'Die Nachteile, die bei wiederholtem Campen auftreten, fallen bei dir nicht mehr so stark aus: Somit kannst du in einer Stadt öfter campen.', 'icon' => 'small_camp', 'daysNeeded' => 391],
        ['name' => 'medicine1', 'title' => 'Erfahrener Junkie', 'description' => 'Du beginnst jede neue Stadt mit einer Ration Paracetoid 7g in deinem Rucksack.', 'icon' => 'item_disinfect', 'daysNeeded' => 481],
        ['name' => 'mayor', 'title' => 'Bürgermeister', 'description' => 'Du kannst Privatstädte gründen (nach deinem nächsten Tod, auf der Seite "Spielen").', 'icon' => 'item_map', 'daysNeeded' => 631],
        ['name' => 'architect', 'title' => 'Architekt', 'description' => 'Du beginnst jede Stadt mit einem Gebäudeplan.', 'icon' => 'item_bplan_c', 'daysNeeded' => 800],
        ['name' => 'prowatch', 'title' => 'Profiwächter', 'description' => 'Du hast permanent um 5% bessere Chancen auf der Nachtwache.', 'icon' => 'r_guard', 'daysNeeded' => 1001],
    ];

    private $entityManager;

    private function insertHeroSkills(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Hero Skills: ' . count(static::$hero_skills) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$hero_skills) );

        foreach(static::$hero_skills as $entry) {
            $entity = $this->entityManager->getRepository(HeroSkillPrototype::class)->findOneBy(['name' => $entry['name']]);

            if($entity === null) {
                $entity = (new HeroSkillPrototype())->setName($entry['name']);
            }

            $entity->setTitle($entry['title'])
                    ->setDescription($entry['description'])
                    ->setDaysNeeded($entry['daysNeeded'])
                    ->setIcon($entry['icon']);

            $manager->persist($entity);
            $progress->advance();
        }
        $manager->flush();
        $progress->finish();
    }

    public function __construct(EntityManagerInterface $em) {
        $this->entityManager = $em;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Hero Skills Database</info>' );
        $output->writeln("");

        $this->insertHeroSkills($manager, $output);
        $output->writeln("");
    }
}