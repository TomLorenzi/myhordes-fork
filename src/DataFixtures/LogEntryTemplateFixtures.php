<?php

namespace App\DataFixtures;

use App\Entity\LogEntryTemplate;
use App\Entity\TownClass;
use App\Entity\TownLogEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class LogEntryTemplateFixtures extends Fixture
{
    public static $log_entry_template_data = [
        ['text'=>'%citizen% hat der Stadt folgendes gespendet: %item%', 'name'=>'bankGive', 'type'=>LogEntryTemplate::TypeBank, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat folgenden Gegenstand aus der Bank genommen: %item%', 'name'=>'bankTake', 'type'=>LogEntryTemplate::TypeBank, 'class'=>LogEntryTemplate::ClassWarning, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat folgenden Gegenstand hier abgelegt: %item%', 'name'=>'itemFloorDrop', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat diesen Gegenstand mitgenommen: %item%', 'name'=>'itemFloorTake', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat eine Ration Wasser genommen.', 'name'=>'wellTake', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat mehr Wasser genommen, als erlaubt ist...', 'name'=>'wellTakeMuch', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassWarning, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat dem Brunnen %num% Rationen Wasser hinzugefügt.', 'name'=>'wellAddOne', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat %item% in den Brunnen geschüttet und damit %num% Rationen Wasser hinzugefügt.', 'name'=>'wellAddMany', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat es regnen lassen! Ihm habt ihr %num% Rationen reinen Wassers im Brunnen zu verdanken!', 'name'=>'wellAddShaman', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat an dem Bauprojekt %plan% mitgearbeitet und dabei %ap% ausgegeben.', 'name'=>'constructionsInvestAP', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"plan",'name'=>'plan'],['type'=>"ap",'name'=>'ap'])],
        ['text'=>'%citizen% hat einen Bauplan für %plan% gefunden.', 'name'=>'constructionsNewSite', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"plan",'name'=>'plan'])],
        ['text'=>'%citizen% hat einen Bauplan für %plan% gefunden. Dafür ist das Bauprojekt %parent% nötig.', 'name'=>'constructionsNewSiteDepend', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"plan",'name'=>'plan'],['type'=>"plan",'name'=>'parent'])],
        ['text'=>'Es wurde ein neues Gebäude gebaut: %plan%.', 'name'=>'constructionsBuildingCompleteNoResources', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'plan'])],
        ['text'=>'Es wurde ein neues Gebäude gebaut: %plan%. Der Bau hat folgende Ressourcen verbraucht: %list%', 'name'=>'constructionsBuildingComplete', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'plan'],['type'=>'list','name'=>'list','listType'=>'item'])],
        ['text'=>'Durch die Konstruktion von %building% hat die Stadt folgende Gegenstände erhalten: %list%', 'name'=>'constructionsBuildingCompleteSpawnItems', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>'list','name'=>'list','listType'=>'item'])],
        ['text'=>'Durch die Konstruktion von %building% wurde der Brunnen um %num% Rationen Wasser aufgefüllt!', 'name'=>'constructionsBuildingCompleteWell', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeWell, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat das Stadttor %action%.', 'name'=>'doorControl', 'type'=>LogEntryTemplate::TypeDoor, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"action",'name'=>'action'])],
        ['text'=>'Das Stadttor wurde automatisch %action%.', 'name'=>'doorControlAuto', 'type'=>LogEntryTemplate::TypeDoor, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"action",'name'=>'action'])],
        ['text'=>'%citizen% hat die Stadt %action%.', 'name'=>'doorPass', 'type'=>LogEntryTemplate::TypeDoor, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"action",'name'=>'action'])],
        ['text'=>'Ein neuer Bürger ist in der Stadt angekommen: %citizen%.', 'name'=>'citizenJoin', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat seine neue Berufung als %profession% gefunden.', 'name'=>'citizenProfession', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"profession",'name'=>'profession'])],
        ['text'=>'%num% Zombies haben vergeblich versucht, in das Haus von %citizen% einzudringen.', 'name'=>'citizenZombieAttackRepelled', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat seinen letzten Atemzug getan: %cod%!', 'name'=>'citizenDeathDefault', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'],['type'=>"cod",'name'=>'cod'])],
        ['text'=>'%citizen% wurde von %num% Zombies zerfleischt!', 'name'=>'citizenDeathNightlyAttack', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'])],
        ['text'=>'Hat jemand in letzter Zeit von %citizen% gehört? Er scheint verschwunden zu sein ...', 'name'=>'citizenDeathVanished', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'],['type'=>"cod",'name'=>'cod'])],
        ['text'=>'%citizen% hat dem Druck nicht länger standgehalten und sein Leben beendet: %cod%.', 'name'=>'citizenDeathCyanide', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'],['type'=>"cod",'name'=>'cod'])],
        ['text'=>'Verrat! %citizen% ist gestorben: %cod%.', 'name'=>'citizenDeathPoison', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'],['type'=>"cod",'name'=>'cod'])],
        ['text'=>'%citizen% hat das Fass zum Überlaufen gebracht. Die Stadt hat seinen Tod entschieden: %cod%.', 'name'=>'citizenDeathHanging', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'],['type'=>"cod",'name'=>'cod'])],
        ['text'=>'%citizen% wurde standrechtlich erschossen. Lang lebe das Diktat!', 'name'=>'citizenDeathHeadshot', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'],['type'=>"cod",'name'=>'cod'])],
        ['text'=>'%citizen% hat seine Behausung in ein(e,n) %home% verwandelt...', 'name'=>'homeUpgrade', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"home",'name'=>'home'])],
        ['text'=>'%citizen% hat folgendes hergestellt: %list2%; dabei wurden folgende Gegenstände verbraucht: %list1%.', 'name'=>'workshopConvert', 'type'=>LogEntryTemplate::TypeWorkshop, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>'list','name'=>'list1','listType'=>'item'],['type'=>'list','name'=>'list2','listType'=>'item'])],
        ['text'=>'%citizen% hat das Stadtgebiet in Richtung %direction% verlassen.', 'name'=>'outsideMoveTownEntry', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>LogEntryTemplate::TypeDoor, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'direction'],['type'=>"profession",'name'=>'profession'])],
        ['text'=>'%citizen% (%profession%) ist Richtung %direction% aufgebrochen.', 'name'=>'outsideMove', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'direction'],['type'=>"profession",'name'=>'profession'])],
        ['text'=>'%citizen% hat ein(e,n) %item% ausgegraben!', 'name'=>'outsideDigSuccess', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat hier durch Graben nichts gefunden...', 'name'=>'outsideDigFail', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat etwas Schutt entfernt.', 'name'=>'outsideUncover', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat diese Ruine freigelegt. Es handelt sich um %type%. Hurra!', 'name'=>'outsideUncoverComplete', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"ruin",'name'=>'type'])],
        ['text'=>'Alles oder Nichts! Jeder einzelne Gegenstand in der Bank wurde zerstört. Die Stadt erhält %def% Verteidigung dafür.', 'name'=>'constructionsBuildingCompleteAllOrNothing', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"num",'name'=>'def'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat %victim% zerfleischt!', 'name'=>'nightlyInternalAttackKill', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'],['type'=>"citizen",'name'=>'victim'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'', 'name'=>'', 'type'=>LogEntryTemplate::, 'class'=>LogEntryTemplate::, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_town_classes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Log Entry Templates: ' . count(static::$log_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$log_entry_template_data) );

        // Iterate over all entries
        foreach (static::$log_entry_template_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(LogEntryTemplate::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new LogEntryTemplate();

            // Set property
            $entity
                ->setText( $entry['text'] )
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setClass($entry['class'])
                ->setSecondaryType( $entry['secondaryType'] )
                ->setVariableTypes($entry['variableTypes'])
            ;

            $manager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Log Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_town_classes( $manager, $output );
        $output->writeln("");
    }
}
