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
        ['text'=>'%sender%: %message%', 'name'=>'beyondChat', 'type'=>LogEntryTemplate::TypeChat, 'class'=>LogEntryTemplate::ClassChat, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"chat",'name'=>'message'])],
        ['text'=>'%citizen% hat sich ein paar Minuten Zeit genommen, um sein Versteck zu verbessern.', 'name'=>'beyondCampingImprovement', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat %item% aufgestellt, um das Versteck zu verbessern.', 'name'=>'beyondCampingItemImprovement', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"item",'name'=>'item'])],
        ['text'=>'%citizen% hat sich für heute Nacht ein Versteck gesucht ...', 'name'=>'beyondCampingHide', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat sein Versteck verlassen.', 'name'=>'beyondCampingUnhide', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat beschlossen auf einen Eskortenanführer zu warten...', 'name'=>'beyondEscortEnable', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat beschlossen sich wieder allein fortzubewegen.', 'name'=>'beyondEscortDisable', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'])],
        ['text'=>'%citizen% hat %target_citizen% überzeugt, ihm zu folgen.', 'name'=>'beyondEscortTakeCitizen', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'target_citizen'])],
        ['text'=>'%citizen% hat %target_citizen% aus der Eskorte entlassen.', 'name'=>'beyondEscortReleaseCitizen', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassInfo, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'target_citizen'])],
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
