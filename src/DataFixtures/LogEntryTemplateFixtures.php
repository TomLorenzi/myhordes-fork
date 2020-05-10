<?php

namespace App\DataFixtures;

use App\Entity\GazetteLogEntry;
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
        ['text'=>'%citizen% hat dem Brunnen %num% Rationen Wasser hinzugefügt.', 'name'=>'wellAdd', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat %item% in den Brunnen geschüttet und damit %num% Rationen Wasser hinzugefügt.', 'name'=>'wellAddItem', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat es regnen lassen! Ihm habt ihr %num% Rationen reinen Wassers im Brunnen zu verdanken!', 'name'=>'wellAddShaman', 'type'=>LogEntryTemplate::TypeWell, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat an dem Bauprojekt %plan% mitgearbeitet und dabei %ap% ausgegeben.', 'name'=>'constructionsInvestAP', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"plan",'name'=>'plan'],['type'=>"ap",'name'=>'ap'])],
        ['text'=>'%citizen% hat einen Bauplan für %plan% gefunden.', 'name'=>'constructionsNewSite', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"plan",'name'=>'plan'])],
        ['text'=>'%citizen% hat einen Bauplan für %plan% gefunden. Dafür ist das Bauprojekt %parent% nötig.', 'name'=>'constructionsNewSiteDepend', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"plan",'name'=>'plan'],['type'=>"plan",'name'=>'parent'])],
        ['text'=>'Es wurde ein neues Gebäude gebaut: %plan%.', 'name'=>'constructionsBuildingCompleteNoResources', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'plan'])],
        ['text'=>'Es wurde ein neues Gebäude gebaut: %plan%. Der Bau hat folgende Ressourcen verbraucht: %list%', 'name'=>'constructionsBuildingComplete', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'plan'],['type'=>'list','name'=>'list','listType'=>'item'])],
        ['text'=>'Durch die Konstruktion von %building% hat die Stadt folgende Gegenstände erhalten: %list%', 'name'=>'constructionsBuildingCompleteSpawnItems', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>'list','name'=>'list','listType'=>'item'])],
        ['text'=>'Durch die Konstruktion von %building% wurde der Brunnen um %num% Rationen Wasser aufgefüllt!', 'name'=>'constructionsBuildingCompleteWell', 'type'=>LogEntryTemplate::TypeConstruction, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeWell, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%citizen% hat das Stadttor %action%.', 'name'=>'doorControl', 'type'=>LogEntryTemplate::TypeDoor, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'action'])],
        ['text'=>'Das Stadttor wurde automatisch %action%.', 'name'=>'doorControlAuto', 'type'=>LogEntryTemplate::TypeDoor, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"transString",'name'=>'action'])],
        ['text'=>'%citizen% hat die Stadt %action%.', 'name'=>'doorPass', 'type'=>LogEntryTemplate::TypeDoor, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'action'])],
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
        ['text'=>'%citizen% hat das Stadtgebiet in Richtung %direction% verlassen.', 'name'=>'townMoveLeave', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>LogEntryTemplate::TypeDoor, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'direction'])],
        ['text'=>'%citizen% hat das Stadtgebiet aus Richtung %direction% betreten.', 'name'=>'townMoveEnter', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>LogEntryTemplate::TypeDoor, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'direction'])],
        ['text'=>'%citizen% (%profession%) ist Richtung %direction% aufgebrochen.', 'name'=>'outsideMoveLeave', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'direction'],['type'=>"profession",'name'=>'profession'])],
        ['text'=>'%citizen% (%profession%) ist aus dem %direction% angekommen.', 'name'=>'outsideMoveEnter', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'direction'],['type'=>"profession",'name'=>'profession'])],
        ['text'=>'%citizen% hat ein(e,n) %item% ausgegraben!', 'name'=>'outsideDigSuccess', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat hier durch Graben nichts gefunden...', 'name'=>'outsideDigFail', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat etwas Schutt entfernt.', 'name'=>'outsideUncover', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat diese Ruine freigelegt. Es handelt sich um %type%. Hurra!', 'name'=>'outsideUncoverComplete', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"transString",'name'=>'type'])],
        ['text'=>'Der Bau des Gebäudes Alles oder nichts hat der Stadt %def% vorübergehende Verteidungspunkte eingebracht. Allerdings wurde dabei der gesamte Inhalt der Bank zerstört! Hoffen wir, dass es das wert war...', 'name'=>'constructionsBuildingCompleteAllOrNothing', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"num",'name'=>'def'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat %victim% zerfleischt!', 'name'=>'nightlyInternalAttackKill', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'],['type'=>"citizen",'name'=>'victim'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat den Brunnen kontaminiert! Das kostet uns %num% Rationen Wasser!', 'name'=>'nightlyInternalAttackWell', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeWell, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'],['type'=>"num",'name'=>'num'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat die Baustelle für %building% verwüstet!', 'name'=>'nightlyInternalAttackDestroy', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeConstruction, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'],['type'=>"plan",'name'=>'building'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat unanständige Nachrichten auf die Heldentafel geschrieben!', 'name'=>'nightlyInternalAttackNothing1', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat sich wieder hingelegt!', 'name'=>'nightlyInternalAttackNothing2', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und verlangt nach einem Kaffee!', 'name'=>'nightlyInternalAttackNothing3', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'])],
        ['text'=>'%zombie% ist von den Toten auferstanden und hat eine Runde auf dem Stadtplatz gedreht!', 'name'=>'nightlyInternalAttackNothing4', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'zombie'])],
        ['text'=>'Resigniert und untröstlich sahen die Bürger, wie eine Horde von %num% Zombies sich in Richtung Stadt bewegte... Wie aus dem Nichts stand die Meute auf einmal vor dem Stadttor...', 'name'=>'nightlyAttackBegin', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"num",'name'=>'num'])],
        ['text'=>'... das OFFEN stand! %num% Zombies sind in die Stadt eingedrungen!', 'name'=>'nightlyAttackSummaryOpenDoor', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"num",'name'=>'num'])],
        ['text'=>'%num% Zombies sind durch unsere Verteidigung gebrochen!', 'name'=>'nightlyAttackSummarySomeZombies', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"num",'name'=>'num'])],
        ['text'=>'Nicht ein Zombie hat die Stadt betreten!', 'name'=>'nightlyAttackSummaryNoZombies', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array()],
        ['text'=>'%num% Zombies attackieren die Stadtbewohner!', 'name'=>'nightlyAttackLazy', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"num",'name'=>'num'])],
        ['text'=>'Der Einsatz von %building% für die Verteidigung der Stadt hat uns %num% Rationen Wasser gekostet.', 'name'=>'nightlyAttackBuildingDefenseWater', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassWarning, 'secondaryType'=>LogEntryTemplate::TypeWell, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>"num",'name'=>'num'])],
        ['text'=>'Durch die Verbesserung von %building% wurdem dem Brunnen %num% Rationen Wasser hinzugefügt.', 'name'=>'nightlyAttackUpgradeBuildingWell', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeWell, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>"num",'name'=>'num'])],
        ['text'=>'Durch die Verbesserung von %building% hat die Stadt folgende Gegenstände erhalten: %items%', 'name'=>'nightlyAttackUpgradeBuildingItems', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>"list",'name'=>'items','listType'=>'item'])],
        ['text'=>'Was für ein Glück! Kurz nach dem Angriff wurde ein Bauplan in die Stadt geweht. Ihr erhaltet: %item%', 'name'=>'nightlyAttackProductionBlueprint', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"item",'name'=>'item'])],
        ['text'=>'Heute Nacht wurden von %building% folgende Gegenstände produziert: %items%', 'name'=>'nightlyAttackProduction', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"plan",'name'=>'building'],['type'=>"list",'name'=>'items','listType'=>'item'])],
        ['text'=>'Am Ende der Schlacht zerfiel das Gebäude %buildingName% zu Staub.', 'name'=>'nightlyAttackDestroyBuilding', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"plan",'name'=>'buildingName'])],
        ['text'=>'Jemand hat eine anonyme Anzeige gegen %citizen% vorgebracht!', 'name'=>'citizenComplaintSet', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'Jemand hat seine anonyme Anzeige gegen %citizen% zurückgezogen.', 'name'=>'citizenComplaintUnset', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'Die letzte Anzeige hat das Fass zum Überlaufen gebracht. %citizen% wurde von den Bürgern der Stadt verbannt!', 'name'=>'citizenBanish', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat die Leiche von %disposed% aus der Stadt gezerrt.', 'name'=>'citizenDisposalDrag', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'disposed'])],
        ['text'=>'%citizen% hat die Leiche von %disposed% vernichtet, indem er sie mit Wasser übergossen hat.', 'name'=>'citizenDisposalWater', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'disposed'])],
        ['text'=>'%citizen% hat aus der Leiche von %disposed% eine leckere Mahlzeit gegrillt. Die Stadt hat %items% erhalten.', 'name'=>'citizenDisposalCremato', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeBank, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'disposed'],['type'=>'list','name'=>'items','listType'=>'item'])],
        ['text'=>'%citizen% hat die Leiche von %disposed% vernichtet.', 'name'=>'citizenDisposalDefault', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'disposed'])],
        ['text'=>'Der Weihnachtsmann wurde dabei beobachtet, wie er %item% von %victim% gestohlen hat', 'name'=>'townStealSanta', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'victim'],['type'=>"item",'name'=>'item'])],
        ['text'=>'HALTET DEN DIEB! %actor% ist bei %victim% eingebrochen und hat %item% gestohlen!', 'name'=>'townStealCaught', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'actor'],['type'=>"citizen",'name'=>'victim'],['type'=>"item",'name'=>'item'])],
        ['text'=>'VERDAMMT! Es scheint, jemand ist bei %victim% eingebrochen und hat %item% gestohlen...', 'name'=>'townStealUncaught', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'victim'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%actor% ist bei %victim% eingebrochen und hat %item% hinterlassen...', 'name'=>'townSmuggleCaught', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'actor'],['type'=>"citizen",'name'=>'victim'],['type'=>"item",'name'=>'item'])],
        ['text'=>'Es scheint, jemand ist bei %victim% eingebrochen und hat %item% hinterlassen...', 'name'=>'townSmuggleUncaught', 'type'=>LogEntryTemplate::TypeHome, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>LogEntryTemplate::TypeVarious, 'variableTypes'=>array(['type'=>"citizen",'name'=>'victim'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat mit dem Gegenstand %item% %kills% Zombie(s) getötet.', 'name'=>'zombieKillWeapon', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'],['type'=>'num','name'=>'kills'])],
        ['text'=>'Schreiend und fuchtelnd hat %citizen% %kills% Zombies getötet.', 'name'=>'zombieKillHands', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'kills'])],
        ['text'=>'%citizen% hat %kills% Zombies getötet.', 'name'=>'zombieKillShaman', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"num",'name'=>'kills'])],
        ['text'=>'%sender%: %message%', 'name'=>'beyondChat', 'type'=>LogEntryTemplate::TypeChat, 'class'=>LogEntryTemplate::ClassChat, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'sender'],['type'=>"chat",'name'=>'message'])],
        ['text'=>'%citizen% hat sich ein paar Minuten Zeit genommen, um sein Versteck zu verbessern.', 'name'=>'beyondCampingImprovement', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat %item% aufgestellt, um das Versteck zu verbessern.', 'name'=>'beyondCampingItemImprovement', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat sich für heute Nacht ein Versteck gesucht ...', 'name'=>'beyondCampingHide', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat sein Versteck verlassen.', 'name'=>'beyondCampingUnhide', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat beschlossen auf einen Eskortenanführer zu warten...', 'name'=>'beyondEscortEnable', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat beschlossen sich wieder allein fortzubewegen.', 'name'=>'beyondEscortDisable', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat %target_citizen% überzeugt, ihm zu folgen.', 'name'=>'beyondEscortTakeCitizen', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'target_citizen'])],
        ['text'=>'%citizen% hat %target_citizen% aus der Eskorte entlassen.', 'name'=>'beyondEscortReleaseCitizen', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'target_citizen'])],
        ['text'=>'%citizen% starb, als er auf lächerliche Weise von der Mauer fiel!', 'name'=>'citizenDeathOnWatch', 'type'=>LogEntryTemplate::TypeCitizens, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'Tapfere Bürger haben auf den Stadtmauern Stellung bezogen : %citizens%', 'name'=>'nightlyAttackWatchers', 'type'=>LogEntryTemplate::TypeNightly, 'class'=>LogEntryTemplate::ClassCritical, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"list",'name'=>'citizens','listType' =>'citizen'])],


        // Gazette: Fun Texts
        ['text'=>'Gestern war ein unbedeutender Tag. Einem Gerücht zufolge wurden %citizen1% und %citizen2% dabei beobachtet, wie sie zusammen im Brunnen badeten. Wenn morgen alle mit einer Pilzinfektion flach liegen, ist ja wohl klar, an wem das lag.',
            'name'=>'gazetteFun_001',
            'type'=>LogEntryTemplate::TypeGazette,
            'class'=>LogEntryTemplate::ClassGazetteNews,
            'secondaryType'=>GazetteLogEntry::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Was für ein denkwürdiger Tag! Die Zombies spielten keine Rolle mehr, nachdem %citizen1% zur Mittagszeit nackt auf der Mauer einmal um die Stadt rannte. Kommentar von %citizen2% dazu: "Der Anblick war nicht von schlechten Eltern."',
            'name'=>'gazetteFun_002',
            'type'=>LogEntryTemplate::TypeGazette,
            'class'=>LogEntryTemplate::ClassGazetteNews,
            'secondaryType'=>GazetteLogEntry::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],

        // Gazette: No deaths in town
        ['text'=>'%citizen1% verbrachten die ganze Nacht heulend in ihrem Haus, bis zu dem Punkt, dass jeder dachte, die Zombies würden Bürger-Steaks aus ihm machen. Es stellte sich heraus, dass sie gerade einen massiven Zusammenbruch hatten. Letzte Nacht gab es keine Toten in der Stadt.',
            'name'=>'gazetteTownNoDeaths_001',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>1,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'%citizen1% und %citizen2% wurden in letzter Minute gerettet, als sie sich gestern Abend bereit machten, sich in ihren Häusern zu erhängen. Kommentar: "Ich dachte, sie würden mich bei lebendigem Leib auffressen, und das wollte ich nicht mehr erleben". Im Nachhinein betrachtet war es eine schlechte Entscheidung, da es gestern Abend keine Zombies in die Stadt geschafft haben.',
            'name'=>'gazetteTownNoDeaths_002',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],['type'=>"citizen",'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Eine großartige Nacht für einige wohlverdiente Feierlichkeiten. Keine Todesopfer in der Stadt infolge des Angriffs! Eine beträchtliche Horde griff von Osten aus an.',
            'name'=>'gazetteTownNoDeaths_003',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Eine friedliche Nacht in der Stadt. Die Horde von %attack% Zombies, die letzte Nacht kam, traf einige Teile der Stadt ziemlich hart, aber es gibt nichts Bemerkenswertes zu berichten.',
            'name'=>'gazetteTownNoDeath_004',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Abgesehen davon, gut, dass letzte Nacht niemand starb. Eine Horde von fast %attack% Zombies heulte die ganze Nacht draußen, aber keiner von ihnen schaffte es, unsere Verteidigung zu durchbrechen.',
            'name'=>'gazetteTownNoDeath_005',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Nahrungsmittelknappheit bei der Horde : %attack% Zombies, und nicht einer von ihnen bekam letzte Nacht etwas zu fressen, unsere Abwehr hielt gut stand.',
            'name'=>'gazetteTownNoDeath_006',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Es sieht so aus, als hätten wir alles richtig gemacht, da letzte Nacht keine Zombies die Mauern durchbrochen haben.',
            'name'=>'gazetteTownNoDeath_007',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Es sei darauf hingewiesen, dass unsere Verteidigung nicht weit von der großen Südmauer entfernt dem furchterregenden Angriff der Horden letzte Nacht standgehalten hat. Um die %attack% Zombies versuchten alles, aber keine Verluste an Menschenleben während des Angriffs!',
            'name'=>'gazetteTownNoDeath_008',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Keine Todesopfer von letzter Nacht zu berichten. Man könnte sogar sagen, dass die Gemeinschaft (endlich) herausgefunden hat, wie sie sich organisieren muss, um nicht ausgelöscht zu werden.',
            'name'=>'gazetteTownNoDeath_009',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Keine Todesopfer heute Abend. Alles ist in Ordnung. Ich garantiere Euch jedoch nur, dass die Zombies heute Nacht verhungern werden: Unsere morgigen Chancen sind..., naja, sagen wir, verbesserungswürdig.',
            'name'=>'gazetteTownNoDeath_010',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Nach dem Angriff sind keine Verluste in den Reihen zu melden (zumindest keine in der Stadt). "Ja, ich denke aber schon, dass wir morgen Nacht alle sterben werden!", so %citizen1%, ein skeptischer Bürger.',
            'name'=>'gazetteTownNoDeath_011',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Unsere Verteidigung an der Ostmauer scheint zufriedenstellend zu sein. Die furchterregenden Kreaturen der Horde wurden in Schach gehalten... dieses Mal!',
            'name'=>'gazetteTownNoDeath_012',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Einige Bürger kamen letzte Nacht ins Schwitzen. Eine Welle von etwa %attack% Monstern versuchte, unsere Stadt zu zerstören, wenn auch ohne Erfolg.',
            'name'=>'gazetteTownNoDeath_013',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Die Zombies von gestern Abend hatten nichts außer unseren Fetzen, in die sie ihre Zähne bekommen konnten, und einige Tierkadaver... Unsere Verteidigung hat sich gut gehalten.',
            'name'=>'gazetteTownNoDeath_014',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Die Zombies griffen uns eine Zeit lang hart an, aber keiner kam durch... Aber zweifelt nicht eine Sekunde daran, dass sie heute Abend zurückkommen werden, noch hungriger und sicherlich zahlenmäßig größer...',
            'name'=>'gazetteTownNoDeath_015',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Heute Morgen feierte %citizen1% das Vereiteln der Zombiehorden von gestern Abend, indem er splitternackt durch die Straßen rannte. "Ich wollte den Anbruch dieses neuen Tages auf angemessene Weise feiern", erklärte der Bürger.',
            'name'=>'gazetteTownNoDeath_016',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Wir verabschiedeten uns liebevoll von dem alten Hund, der im Dorf lebte... Das unaufhörliche Bellen, das alle in der Nachbarschaft verärgerte, ist seit dem Angriff von gestern Abend für immer verstummt. Das arme Ding... Die gute Nachricht ist, dass es in der Stadt keine nennenswerten Verluste an Menschenleben gab.',
            'name'=>'gazetteTownNoDeath_017',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Wir hatten alles, was wir brauchten nahe dem westlichen Viertel, um die schlurfenden Leichen von gestern Abend fernzuhalten, soviel ist sicher. Null Verluste - abgesehen von ein paar Häusern, die sie getroffen haben.',
            'name'=>'gazetteTownNoDeath_018',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Ihr hast sie letzte Nacht gehört... die Schreie, das Stöhnen. Um die %attack% Zombies herum griffen an. Diesmal konnten wir uns durchsetzen, aber morgen... morgen wird es noch schlimmer...',
            'name'=>'gazetteTownNoDeath_019',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Da gestern Abend rund %attack% Zombies vor den Toren standen, hätten wir das Schlimmste befürchten können, aber kein einziger ist reingekommen: gute Zeiten! Brecht Euch sich aber nicht den Arm, wenn Ihr Euch gegenseitig auf die Schulter klopft!',
            'name'=>'gazetteTownNoDeath_020',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteNoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],

        // Gazette: One death in town.
        ['text'=>'%cadaver1% hatte gestern Abend kein Glück. Abgesehen davon war es eine ruhige Nacht in der Stadt...',
            'name'=>'gazetteTownOneDeath_001',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteOneDeath,
            'secondaryType'=>GazetteLogEntry::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Alle scheinen sich still und leise über den Tod von %cadaver1% gestern Abend zu freuen... Allerdings hat niemand erklärt, warum. %citizen1% kommentierte: "Seine Mutter war ein Hamster, und sein Vater roch nach Holunderbeeren".',
            'name'=>'gazetteTownOneDeath_002',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteOneDeath,
            'secondaryType'=>GazetteLogEntry::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Jeder hörte %cadaver1% schreien, als er von den Zombies auseinander gerissen wurde. Offensichtlich versuchte niemand zu helfen. Überlebensinstinkt. Wirst du jetzt nachts schlafen können?',
            'name'=>'gazetteTownOneDeath_003',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteOneDeath,
            'secondaryType'=>GazetteLogEntry::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Ich bin sicher, dass nicht nur ich der Meinung bin, dass wir eine Rechnung mit %cadaver1% zu begleichen hatten. Letztendlich scheint es also Karma gewesen zu sein, dass ausgerechnet er heute Nacht ums Leben kam.',
            'name'=>'gazetteTownOneDeath_004',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteOneDeath,
            'secondaryType'=>GazetteLogEntry::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Einige Bürger wurden Zeugen eines seltsamen Vorfalls... Man könnte sagen, dass die Zombies gestern Abend ausschließlich für %cadaver1% gekommen sind. Sie trugen die Leiche den ganzen Weg zur Baustelle, bevor die Zerstückelung begann!',
            'name'=>'gazetteTownOneDeath_005',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteOneDeath,
            'secondaryType'=>GazetteLogEntry::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Einige sagen, daß der Tod von %cadaver1% nicht dem Glück zu verdanken ist... das einzige Opfer gestern Abend... Könnte jemand unter uns seinen Tod provoziert haben?',
            'name'=>'gazetteTownOneDeath_006',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteOneDeath,
            'secondaryType'=>GazetteLogEntry::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Gazette: Two deaths in town
        ['text'=>'Ausgangssperre gilt für alle. Auch für %cadaver1% und %cadaver2% – das haben sie nun davon.',
            'name'=>'gazetteTownTwoDeaths_001',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteTwoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresTwoCadavers,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver2'],
            ],
        ],
        ['text'=>'So richtig scheint keiner über den Tod von %cadaver1% und %cadaver2% zu trauern. Sie waren wohl nicht die beliebtesten in der Stadt.',
            'name'=>'gazetteTownTwoDeaths_002',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteTwoDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresTwoCadavers,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver2'],
            ],
        ],

        // Gazette: Multiple deaths in town.
        ['text'=>'Eine schreckliche Nacht für die Stadt. Die lebenden Toten massakrierten %deaths% unserer Gemeinde während des Angriffs. Vielleicht möchtet ihr vor heute Abend noch einmal einen Blick auf unsere Verteidigung werfen...',
            'name'=>'gazetteTownMultiDeaths_001',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Die Zombies fanden gestern Abend an der Nordwand eine Schwäche in unserer Verteidigung... Einige Häuser hielten dem Angriff stand. Andere nicht... ... %deaths% tot. Ende der Geschichte.',
            'name'=>'gazetteTownMultiDeaths_002',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Einige Bewohner brachen heute Morgen ob der Opfer in Tränen aus. Tränen der Freude mit Sicherheit, nicht eines der %deaths% Opfer des letzten Angriffs zu sein.',
            'name'=>'gazetteTownMultiDeaths_003',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Eine Handvoll Zombies durchbrach unsere Verteidigungsanlagen in der Nähe des nördlichen Viertels, wir haben keine Ahnung, wie... Wie es das "Glück" wollte, sind %deaths% Bürger tot, aber ihr habt seltsamerweise überlebt. ... Klingt das nicht ein wenig verdächtig?',
            'name'=>'gazetteTownMultiDeaths_004',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Eine Flutwelle von Zombies stürzte letzte Nacht gegen unsere Stadt! Bürger wurden in ihren eigenen Häusern verschlungen oder in die Wüste geschleift... Noch so eine Nacht, und wir werden nicht mehr hier sein, um darüber zu reden.',
            'name'=>'gazetteTownMultiDeaths_005',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Wir müssen uns beeilen; unsere Unfähigkeit, zufriedenstellende Verteidigungsanlagen zu errichten, kostete letzte Nacht %deaths% Bürgern das Leben. Zu eurer Information: Gestern Abend wurde die Stadt von %attack% Zombies angegriffen.',
            'name'=>'gazetteTownMultiDeaths_006',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresAttackDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Die Verteidigungsanlagen waren gestern Abend unzureichend. %deaths% Bürger bezahlten für eure mangelnde Organisation mit ihrem Leben.',
            'name'=>'gazetteTownMultiDeaths_007',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Letzte Nacht haben es %deaths% Bürger nicht rechtzeitig nach Hause geschafft. Einige Teile von ihnen wurden in der Nähe des westlichen Viertels gefunden. Augenzeugen berichten, dass die Anwohner riefen: "Lauf, Forrest, lauf!", bevor sie vor Lachen ausbrachen und in ihr Haus rannten.',
            'name'=>'gazetteTownMultiDeaths_008',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Was für ein Riesenschlamassel: %deaths% starben letzte Nacht in der Stadt! Ein Massaker, zu dem noch der zertrümmerter Schädel eines Haustiers zu zählen ist, der in den Toren verkeilt gefunden wurde. Vermisst jemand einen Hund?',
            'name'=>'gazetteTownMultiDeaths_008',
            'type'=>LogEntryTemplate::TypeGazetteTown,
            'class'=>LogEntryTemplate::ClassGazetteMultiDeaths,
            'secondaryType'=>GazetteLogEntry::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],

/*

*/
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
