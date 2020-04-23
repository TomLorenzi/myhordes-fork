<?php


namespace App\Service;

use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\Forum;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    private $conf;
    private $translator;

    const ErrorNone = 0;
    const ErrorTownClosed          = ErrorHelper::BaseTownSelectionErrors + 1;
    const ErrorUserAlreadyInGame   = ErrorHelper::BaseTownSelectionErrors + 2;
    const ErrorUserAlreadyInTown   = ErrorHelper::BaseTownSelectionErrors + 3;
    const ErrorNoDefaultProfession = ErrorHelper::BaseTownSelectionErrors + 4;

    public function __construct(ConfMaster $conf,
        EntityManagerInterface $em, GameValidator $v, Locksmith $l, ItemFactory $if, TownHandler $th,
        StatusFactory $sf, RandomGenerator $rg, InventoryHandler $ih, CitizenHandler $ch, ZoneHandler $zh, LogTemplateHandler $lh,
        TranslatorInterface $translator)
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
        $this->conf = $conf;
        $this->translator = $translator;
    }

    private static $town_name_snippets = [
        'de' => [
            [  // Sächlich Singular
                ['Tödliches','Modriges','Schimmliges','Eisiges','Rotes','Einsames','Ghulverseuchtes','Zombifiziertes','Bekanntes','Abgenagtes','Verstörendes','Letztes'],
                ['Wasserloch','Hospital','Trainingslager','Pony','Niemandsland','Gericht','Reich','Dreckloch','Gehirn','Rattenloch','Gebiet','Lager'],
            ],
            [  // Männlich Singular
                ['Eifriger','Weinender','Schimmliger','Alberner','Autoritärer','Einsamer','Triefender','Kontaminierter','Verschlafener','Masochistischer','Besoffener'],
                ['Müllberg','Seelenfänger','Monarch','Fels','Untergang','Wald','Folterkeller','Bezirk','Bunker','Tisch','Husten','Laster'],
            ],
            [  // Weiblich Singular
                ['Eifrige','Weinende','Schimmlige','Alberne','Autoritäre','Einsame','Triefende','Kontaminierte','Verschlafene','Masochistische','Besoffene'],
                ['Region','Insel','Anhöhe','Felsspalte','Apokalypse','Wiese','Höhle','Kammer','Untiefe','Miliz','Träne','Latrine'],
            ],
            [  // Plural
                ['Eifrige','Modrige','Glitschige','Eisige','Drogensüchtige','Gespenstische','Ghulverseuchte','Zombifizierte','Bewegte','Betrunkene','Virulente','Betroffene'],
                ['Metzger','Zombieforscher','Gestalten','Wächter','Todesgesänge','Schaffner','Soldaten','Zwillinge','Regionen','Oberfläche','Schmarotzer','Entwickler'],
            ],
            [  // Plural mit Suffix
                ['Ghulgebeine','Gesänge','Schmerzen','Schreie','Räume','Meute','Ghetto','Bürger','Hinterlassenschaft','Revier','Folterkeller','Alkoholpanscher'],
                ['des Todes','der Verdammnis','ohne Zukunft','am Abgrund','der Verwirrten','ohne Ideen','der Versager','der Ghule','der Superhelden','der Mutlosen','der Fröhlichen','der Revolutionäre'],
            ],
        ],
        'en' => [
            [   // Prefixed Adjective
                ['Deadly', 'Mouldy', 'Moldy', 'Icy', 'Red', 'Lonely', 'Ghoulish', 'Zombified', 'Known', 'Gnawed', 'Disturbing', 'Last', 'Eager', 'Crying', 'Silly', 'Authoritarian', 'Lonely', 'Dripping', 'Contaminated', 'Sleepy', 'Masochistic', 'Drunk', 'Musty', 'Slippery', 'Icy', 'Drug addicts', 'Spooky', 'Ghoul-infested', 'Moving', 'Virulent', 'Affected'],
                ['Waterhole', 'Hospital', 'Training camp', 'Pony', 'No man\'s land', 'Court', 'Empire', 'Shithole', 'Brain', 'Rathole', 'Area', 'Camp', 'Garbage Mountain', 'Soul Catcher', 'Monarch', 'Rock', 'Fall', 'Forest', 'Torture Basement', 'District', 'Bunker', 'Table', 'Cough', 'Truck', 'Butchers', 'Zombie researchers', 'Figures', 'Guardians', 'Death songs', 'Conductor', 'Soldiers', 'Twins', 'Regions', 'Surface', 'Parasites', 'Developers'],
            ],
            [  // Suffixed
              ['Ghoul bones', 'Songs', 'Pain', 'Screams', 'Rooms', 'Mob', 'Ghetto', 'Citizens', 'Legacy', 'Territory', 'Torture chamber', 'Alcohol adulterator'],
              ['of Death', 'of Damnation', 'without Future', 'at the Abyss', 'of the Confused', 'without Ideas', 'of the Failures', 'of the Ghouls', 'of the Superheroes', 'of the Discouraged', 'of the Cheerful', 'of the Revolutionaries'],
            ],
        ],
        'fr' => [
            [
                // Masculin singular
                ['Abîme','Antre','Avant-Poste','Bidonville','Camp','Canyon','Centre','Cimetière','Cloaque','Colisée','Comté','Coteau','Echo','Enfer','Espace','Espoir','Fort','Gouffre','Hameau','Hypogée','Lieu-dit','Monolithe','Mont','Mystère','Refuge','Sommet','Souterrain','Tertre','Théâtre','Tombeau','Trou','Tumulus','Vide','Village'],
                ['abandonné','abattu','abrité','accablé','affligé','anecdotique de l\'hiver','angoissant','angoissant de l\'Indicible','antique','antique de La Nuit Profonde','antique des Affamés','anéanti','archaïque','arriviste','atrabilaire','atrabilaire des Idiots','atroce','attardé','attardé de l\'automne','attristé','attristé des Ellipses','austère','battu','blafard','blafard des Insurgés','brutal','brutal des Sarcasmes','caché','chaud','chaud de La Faim','chaud des Damnés','chaud des Peaux','colérique','colérique d\'Ani La Peintre','consterné','contaminé','contaminé du Tréfonds','craquelé','croulant','croulant des Damnés','crépusculaire','d\'Ani Enciélé','d\'Ani Le Coloré','d\'Arkham','d\'Eole l\'inspiratrice','d\'Eole notre guide','d\'Hadès','d\'Halloween','d\'Irma','d\'Irvie Aux Gommes','damné','damné de Twin','de Celles Qui Prient','de Dizan','de Feu','de Goatswood','de Hiko le Fruité','de Hiko Twin','de Hk L\'insoumis','de Junon Pervertie','de Kiroukou le poète','de L\'Angoisse','de l\'Anguille Courbée','de l\'Armageddon','de l\'automne','de l\'Effroi','de l\'est','de l\'hiver','de l\'Or Bleu','de l\'orient','de l\'Oublié','de l\'Usurpateur','de l\'Âtre Ancien','de l\'été','de la Mort Lente','de la Nouvelle Espérance','de La Nuit Profonde','de la Paix Verte','de la rédemption','de la Soif','de la sorcière','de LeChuck','de Molineux','de Murakami','de Refactor Yota','de Shaolin Kiroukou','de Shining Gygy','de Shoot\'em Up Bumdum','de Shubi le coquin','de Thinkslow','de Threepwood','de Twin','de Warp l\'architecte','de Warp le Magicien','de Whitetigle','de Whitetigle le juste','de Yota le Terrible','de Yukito la cruelle','des Abcès Douloureux','des Affamés','des Anonymes','des Arts Ubuesques','des Aveugles Brûlés','des Brutes','des Capraphiles','des Chairs','des Citoyens Anonymes','des Conquis','des Cordes','des Damnés','des Demies Vies','des Eclairs Vacillants','des effusions de sang','des Ellipses','des Enfers','des Gardiens Effacés','des Grands Anciens','des Hordes','des Hydratones','des Idiots','des Insoumis','des Insurgés','des Lapinous','des Larmes de Sang','des Liners Détruits','des Lépreux Morts','des Membres de Pus','des Miraculés Bannis','des morts qui dansent','des Norrissiens','des Noëlistes','des Nuits de l\'Horreur','des Nuits sans Fin','des Oubliés','des Parcs Ensevelis','des Perdus','des Piouz Transis','des Pouilleux','des Premiers Jours','des Psychotiques','des Sadiques','des Sanglots Sourds','des Sarcasmes','des Tzongres Noirs','des Vents Brûlants','des Vents Sifflants','des Vétérans','des Éclaireurs Disparus','despotique','desuet','desuet de l\'été','dirigiste','disparu','du Cyclope','du Gant de Combat','du Haut','du Morne Quotidien','du nord','du Plasticien Taquin','du Premier Jour','du Printemps','du Rebond Alpha','du Roi Corbeau','du sud','du Tréfonds','du Vent Fou','dur','découragé','découragé de Molineux','déprimant','désemparé','déserté','désolé des Sarcasmes','déséquilibré','enseveli','enterré','enterré des Lapinous','fossile','froid','funeste','funeste des Déshérités','funeste des Norrissiens','funèbre','funèbre des Demies Vies','féroce','féroce de Warp le Magicien','gangrené','glauque','glauque de La Faim','grave','grave des Chairs','grave des Hordes','gris','humide','ignorant','implacable','inculte','infecté','inférieur','inférieur d\'HypIrvie','inférieur des Brûlés','inhumain','inviolé de l\'Indicible','isolé des Déshérités','isolé sans avenir','jauni','jauni de Twin','lamentable','lamentable des Conquis','lugubre','lugubre de l\'Usurpateur','macabre','maudit','maussade','mauvais','moisi','morne','morose','mort','mort de l\'Oublié','mort de Molineux','mélancolique','nocturne','noir','nordique','nostalgique','obscur','passé','passé de Goatswood','passé des Viles Sangsues','passéiste','pathétique','pathétique d\'Irma','pervers','pervers de Dunwich','pervers des Insoumis','pitoyable de Nô','pourpre','pourpre de Shining Gygy','psychotique','putride','putride de l\'orient','pénible','pénible de Hiko Sharingan','pénible de La Peste Noire','périmé','ravagé','ravagé de l\'Effroi','ravagé de l\'Indicible','rouge','rude','rustre','rustre de l\'Âtre Ancien','rustre des Lames de Fond','réactionnaire','rétrograde','rétrograde de la citrouille creuse','sanglant','sanguinaire','sans avenir','sans Lendemain','sauvage','sinistre','sinistre des Nuits sans Fin','sombre','sordide','sordide du Gant de Combat','supérieur','taciturne','taciturne de Whitetigle','taciturne sans Fin','tendu de l\'est','terni','totalitair','triste','tétanisé','tétanisé sans avenir','vandale','verdâtre','verdâtre des Chairs','versicolore de La Faim','éploré','épouvantable','étrange'],
            ],
            [
                // Féminin singular
              ['Annexe','Arène','Attraction','Butte','Caserne','Caverne','Cavité','Citadelle','Cité','Coalition','Colline','Communauté','Contrée','Crique','Croisée','Côte','Désolation','Dévastation','Enclave','Engeance','Etendue','Faille','Falaise','Fosse','Fossé','Frontière','Gangrène','Ignorance','Immensité','Indolence','Installation','Jonction','Multitude','Orbite','Pampa','Paranoïa','Plaine','Plantation','Prairie','Promenade','Retraite','Région','Rémanence','Steppe','Structure','Surface','Terre','Toundra','Tranchée','Vallée'],
              ['abattue','abattue d\'Ani Le Coloré','abattue de l\'est','abritée','accablée','accablée de l\'été','affligée','anecdotique','angoissante','angoissante de La Faim','antique','anéantie','archaïque','arriérée','atrabilaire','atrabilaire de l\'Abîme','atrabilaire de Twin','atroce','atroce de l\'est','atroce des Lapinous','attardée','attardée des Damnés','attristée','attristée de Molineux','austère','austère du Tréfonds','aux sorcières','barbare','battue','blafarde','blafarde aux citrouilles','brutale','cachée','chaude','colérique','consternée','contaminée','corrompue','craquelée','croulante','crépusculaire','d\'Ani Enciélé','d\'Ani La Peintre','d\'Ani Le Coloré','d\'Arkham','d\'Eole notre guide','d\'Halloween','d\'HypIrvie','d\'Irma','d\'Irvie Aux Gommes','d\'Irvie Le Terrible','damnée','de Abyssal\' Hk','de Bumdum le Versicolore','de Celles Qui Prient','de Chéloné La Lente','de Dizan','de Feu','de Goatswood','de Hiko le Fruité','de Hk L\'insoumis','de Hk le Colorimétrique','de l\'Abîme','de L\'Angoisse','de l\'Anguille Courbée','de l\'Armageddon','de l\'Effroi','de l\'hiver','de l\'Indicible','de L\'Obscurité Vaine','de l\'Or Bleu','de l\'orient','de l\'Oublié','de l\'ouest','de l\'Usurpateur','de l\'Âtre Ancien','de la Chimie Naturelle','de La Faim','de la fête des morts','de La Marque du Loup','de la Mort Lente','de la Paix Verte','de La Peste Noire','de La Peur Au Ventre','de la rédemption','de la Soif','de la sorcière','de LeChuck','de McBess le Folâtre','de Molineux','de Murakami','de Nô','de Particules\' Bumdum','de Putréfaction','de Shaolin Kiroukou','de Shining Gygy','de Shubi le coquin','de Thinkslow','de Threepwood','de Twin','de Warp l\'architecte','de Warp le Magicien','de Whitetigle','de Whitetigle le juste','de Yota le Terrible','de Yukito la cruelle','des Abcès Douloureux','des Affamés','des Anonymes','des Antivilles','des Arts Ubuesques','des Aveugles Brûlés','des Bactéries','des Bannis','des Brutes','des Brûlés','des Capraphiles','des Chairs','des Citoyens Anonymes','des Citoyens Perdus','des Conquis','des Cordes','des Damnés','des Demies Vies','des Déshérités','des effusions de sang','des Ellipses','des Eléments Tristes','des Fouineurs Enterrés','des Frutimetières','des Grands Anciens','des Hordes','des Idiots','des Insoumis','des Insurgés','des Jours sans Fin','des Jumeaux Mobiles','des Lames de Fond','des Larmes de Sang','des Liners Détruits','des Lépreux Morts','des Maux Oubliés','des Membres de Pus','des Miraculés Bannis','des Norrissiens','des Noëlistes','des Nuits de l\'Horreur','des Nuits sans Fin','des Oubliés','des Parcs Ensevelis','des Peaux','des Pensées Futiles','des Perdus','des Piouz Transis','des Pouilleux','des Premiers Jours','des Psychotiques','des Sadiques','des Sanglots Sourds','des Sarcasmes','des Tzongres Noirs','des Vents Sifflants','des Viles Sangsues','des Éclaireurs Disparus','despotique','desuete','dirigiste','disparue des Oubliés','du chaudron','du Cyclope','du Gant de Combat','du Haut','du Mistral Perdant','du Morne Quotidien','du nord','du Plasticien Taquin','du Poisson Bulle','du Premier Jour','du Printemps','du Pus','du Rebond Alpha','du Roi Corbeau','du sud','du Tréfonds','du Vent Fou','du Vieux Parc à Fruits','dure','découragée','déprimante','désemparée','désemparée de la citrouille creuse','désertée','désolée','déséquilibrée','ensevelie','enterrée','funeste','funeste des Ellipses','funèbre','féroce','féroce de Feu','glauque','grave','grave de la Mort Lente','grise','grise de Hk L\'insoumis','humide','ignorante','impitoyable','implacable','inculte des Hordes','infectée','inflexible','inférieure','inhumaine','inviolée','jauni de l\'Abîme','jaunie','jaunie de Refactor Yota','lamentable','lamentable de Threepwood','lamentable des Brutes','livide','livide sans avenir','lugubre','macabre','macabre de La Peste Noire','maussade','maussade d\'HypIrvie','mauvaise aux potimarrons','mauvaise de l\'Abîme','mauvaise de l\'été','moisie','moisie des Antivilles','morne','morose','morte','médiocre','médiocre de Whitetigle','mélancolique','nocturne','nocturne de l\'ouest','nocturne sans Fin','noire','nordique','nordique de Dizan','nostalgique','obscure','obsédée des Lapinous','passée','passéiste','pathétique','perdue','perdue des Insoumis','perverse','pitoyable','pitoyable de Twin','pourpre','pourpre du Gant de Combat','psychotique','psychotique de Dizan','putride','putride des Hydratones','putride sans avenir','pénible','pénible des Sarcasmes','périmée','ravagée','rouge','rouge des Idiots','rude','rude des Cordes','rustre','réactionnaire','rétrograde de Dunwich','sanglante','sanguinaire','sans avenir','sans but','sans Fin','sans Lendemain','sauvage','sombre','sordide','supérieure','taciturne','tendue d\'Arkham','ternie','ternie de Goatswood','totalitaire','triste','triste de Dunwich','triste des Piouz Transis','triste sans but','tétanisée','vandale','vandale des Noëlistes','verdâtre','versicolore','violée','violée des Chairs','éplorée','éplorée sans avenir','épouvantable','étrange','étrange de l\'orient'],
            ],
            [
                // Masculin plural
                ['Abîmes','Bas-fonds','Canyons','Colisées','Comtés','Coteaux','Espaces','Forts','Fossés','Remparts','Sommets','Songes','Souterrains','Tertres','Théâtres','Trous'],
                ['abandonnés','abattus','abrités de Shining Gygy','abrités des Chairs','affligés','affligés d\'Halloween','angoissants','angoissants de la fête des morts','antiques','archaïques','arrivistes','arriérés','atrabilaires','atroces','attardés','aux sorcières','battus','blafards des Peaux','brutals','brutals des morts qui dansent','cachés du sud','colériques','colériques de la Soif','consternés','corrompus','craquelés','croulants','crépusculaires','d\'Ani Enciélé','d\'Arkham','d\'Eole l\'inspiratrice','d\'Eole notre guide','d\'Hadès','d\'Irma','d\'Irvie Aux Gommes','d\'Irvie Le Terrible','de Bumdum le Versicolore','de Dizan','de Feu','de Goatswood','de Gyhyom le Fluorescent','de Hiko Sharingan','de Hiko Twin','de Kiroukou le poète','de l\'Abîme','de L\'Angoisse','de l\'Effroi','de l\'est','de l\'hiver','de l\'Indicible','de l\'Oublié','de l\'Âtre Ancien','de La Faim','de la fête des morts','de La Marque du Loup','de La Nuit Profonde','de la Paix Verte','de La Peste Noire','de la Soif','de LeChuck','de Nô','de Particules\' Bumdum','de Putréfaction','de Shaolin Kiroukou','de Thinkslow','de Threepwood','de Warp le Magicien','de Whitetigle','de Whitetigle le juste','de Yukito la cruelle','des Affamés','des Anonymes','des Antivilles','des Arts Ubuesques','des Bactéries','des Brutes','des Brûlés','des Capraphiles','des Citoyens Anonymes','des Citoyens Perdus','des Cordes','des Déshérités','des Eclairs Vacillants','des effusions de sang','des Ellipses','des Eléments Tristes','des Enfers','des Gardiens Effacés','des Grands Anciens','des Hordes','des Insoumis','des Insurgés','des Jumeaux Mobiles','des Lames de Fond','des Lapinous','des Larmes de Sang','des Liners Détruits','des Maux Oubliés','des Miraculés Bannis','des Nuits de l\'Horreur','des Nuits sans Fin','des Peaux','des Pensées Futiles','des Perdus','des Piouz Transis','des Premiers Jours','des Sadiques','des Sanglots Sourds','des Sarcasmes','des Vents Brûlants','dirigistes','du Cyclope','du Gant de Combat','du Mistral Perdant','du Morne Quotidien','du nord','du Poisson Bulle','du Printemps','du Pus','du Tréfonds','du Vent Fou','découragés','désemparés','désemparés d\'Hadès','désertés','déséquilibrés','enterrés','fossiles','froids de Murakami','funestes','funestes de la Mort Lente','funèbres','gangrenés','gris','implacables','infectés','inflexibles','inhumains','inviolés','inviolés du sud','isolés','isolés de Putréfaction','jaunis','lamentables','livides','mauvais','mauvais des Perdus','mornes de Hiko Twin','mornes du chaudron','moroses','moroses des Peaux','médiocres','noirs','nordiques','nordiques du Tréfonds','nostalgiques','obscurs','obsolètes','obsolètes aux potimarrons','passéistes','passés des Peaux','perdus','pourpres','psychotiques','putrides','putrides des Insoumis','pénibles du Cyclope','périmés','ravagés','rouges','rudes','réactionnaires','rétrogrades','sanglants','sans avenir','sans but','sans Fin','sans Lendemain','sombres','sordides','tendus','ternis','totalitaires','tristes de Hk L\'insoumis','vandales','verdâtres','versicolores','étranges']
            ],
            [
                // Féminin plural
                ['Arènes','Buttes','Casernes','Cavernes','Cavités','Coalitions','Collines','Contrées','Croisées','Côtes','Désolations','Dévastations','Engeances','Etendues','Fosses','Immensités','Inconnues','Installations','Jonctions','Multitudes','Orbites','Plaines','Prairies','Promenades','Retraites','Régions','Steppes','Structures','Surfaces','Terres','Vallées'],
                ['abandonnées','abattues','abritées','abritées du Pus','accablées','affligées','antiques','antiques d\'Arkham','anéanties','anéanties sans but','archaïques','arrivistes','arriérées','atrabilaires','attardées','attristées','austères','aux potimarrons','aux sorcières','blafardes','brutales','cachées','chaudes','colériques','consternées','contaminées','corrompues','craquelées','croulantes','crépusculaires','d\'Ani Enciélé','d\'Ani La Peintre','d\'Ani Le Coloré','d\'HypIrvie','d\'Irma','d\'Irvie Aux Gommes','d\'Irvie Le Terrible','damnées','damnées d\'Irma','de Abyssal\' Hk','de Celles Qui Prient','de Dizan','de Dunwich','de Feu','de Goatswood','de Hiko le Fruité','de Hiko Sharingan','de Hk L\'insoumis','de Junon Pervertie','de Kiroukou le poète','de Korppi','de l\'Abîme','de L\'Angoisse','de l\'Anguille Courbée','de l\'Armageddon','de l\'automne','de l\'Effroi','de l\'Indicible','de L\'Obscurité Vaine','de l\'Or Bleu','de l\'orient','de l\'ouest','de l\'Âtre Ancien','de l\'été','de la Chimie Naturelle','de La Faim','de La Marque du Loup','de la Mort Lente','de La Nuit Profonde','de la Paix Verte','de La Peste Noire','de La Peur Au Ventre','de la rédemption','de la Soif','de la sorcière','de LeChuck','de McBess le Folâtre','de Molineux','de Murakami','de Particules\' Bumdum','de Putréfaction','de Shining Gygy','de Shoot\'em Up Bumdum','de Shubi le coquin','de Thinkslow','de Threepwood','de Warp le Magicien','de Whitetigle','de Whitetigle le juste','de Yota le Terrible','de Yukito la cruelle','des Abcès Douloureux','des Affamés','des Anonymes','des Arts Ubuesques','des Aveugles Brûlés','des Bannis','des Brutes','des Brûlés','des Capraphiles','des Citoyens Perdus','des Conquis','des Cordes','des Demies Vies','des Déshérités','des effusions de sang','des Ellipses','des Eléments Tristes','des Enfers','des Frutimetières','des Grands Anciens','des Idiots','des Insoumis','des Jumeaux Mobiles','des Lames de Fond','des Lapinous','des Larmes de Sang','des Liners Détruits','des Lépreux Morts','des Maux Oubliés','des Miraculés Bannis','des morts qui dansent','des Norrissiens','des Noëlistes','des Nuits de l\'Horreur','des Oubliés','des Peaux','des Pensées Futiles','des Piouz Transis','des Premiers Jours','des Sadiques','des Tremblements de Terre','des Vents Sifflants','des Viles Sangsues','des Vétérans','des États Désunis','desuetes','dirigistes','disparues','disparues des Capraphiles','du Cyclope','du Gant de Combat','du Mistral Perdant','du Morne Quotidien','du nord','du Poisson Bulle','du Premier Jour','du Printemps','du Pus','du Rebond Alpha','du sud','du Tréfonds','du Vent Fou','déprimantes','désemparées','désertées','désertées de Nô','désolées','désolées du Haut','déséquilibrées','ensevelies','enterrées','fossiles','froides','froides sans avenir','funestes','funèbres','funèbres des Damnés','féroces','féroces sans but','gangrenées','glauques','glauques des Anonymes','grises','grises de l\'Usurpateur','humides','humides des Brutes','impitoyables','implacables','incultes','inhumaines sans Fin','inviolées du Printemps','isolées de Thinkslow','jaunies','jaunies des Lapinous','lamentables','livides','lugubres','macabres','maudites','maudites de l\'hiver','maussades','mauvaises','moisies','mornes du Pus','moroses','mortes','médiocres','mélancoliques','nocturnes','noires','noires des Pouilleux','nordiques','nordiques de l\'est','nostalgiques','obscures','obsolètes','obsédées','passées','passéistes','pathétiques','perdues','perverses','pitoyables','pitoyables de Dunwich','pourpres','pourpres de Nô','pourpres de Whitetigle','pourpres des citrouilles','psychotiques','putrides','pénibles','pénibles des Antivilles','périmées','ravagées','ravagées de l\'Oublié','rouges','rudes','rustres','réactionnaires','rétrogrades','rétrogrades des Hordes','sanglantes','sanguinaires','sans Fin','sans Lendemain','sauvages','sombres','sordides','taciturnes','tendues','ternies','totalitaires','tristes','tétanisées','vandales','versicolores','violées','violées de l\'hiver','éplorées','épouvantables','étranges']
            ]
        ]
    ];

    public function createTownName($language): string {
        return implode(' ', array_map(function(array $list): string {
            return $list[ array_rand( $list ) ];
        }, static::$town_name_snippets[$language][array_rand( static::$town_name_snippets[$language] )]));
    }

    private function getDefaultZoneResolution( TownConf $conf, ?int &$offset_x, ?int &$offset_y ): int {
        $resolution = mt_rand( $conf->get(TownConf::CONF_MAP_MIN, 0), $conf->get(TownConf::CONF_MAP_MAX, 0) );
        $safe_border = ceil($resolution/4.0);
        $offset_x = $safe_border + mt_rand(0, $resolution - 2*$safe_border);
        $offset_y = $safe_border + mt_rand(0, $resolution - 2*$safe_border);
        return $resolution;
    }

    public function createTown( ?string $name, ?string $language, int $population, string $type ): ?Town {
        if (!$this->validator->validateTownType($type))
            return null;

        $townClass = $this->entity_manager->getRepository(TownClass::class)->findOneByName( $type );

        // Initial: Create town
        $town = new Town();
        $town
            ->setType( $townClass );

        $conf = $this->conf->getTownConfiguration($town);

        if ($population <= 0 || $population < $conf->get(TownConf::CONF_POPULATION_MIN, 0) || $population > $conf->get(TownConf::CONF_POPULATION_MAX, 0))
            return null;

        $this->translator->setLocale($language ?? 'de');

        $town
            ->setPopulation( $population )
            ->setName( $name ?: $this->createTownName($language) )
            ->setLanguage( $language )
            ->setBank( new Inventory() )
            ->setWell( mt_rand( $conf->get(TownConf::CONF_WELL_MIN, 0), $conf->get(TownConf::CONF_WELL_MAX, 0) ) );

        foreach ($this->entity_manager->getRepository(BuildingPrototype::class)->findProspectivePrototypes($town, 0) as $prototype)
            $this->town_handler->addBuilding( $town, $prototype );

        foreach ($conf->get(TownConf::CONF_BUILDINGS_UNLOCKED) as $str_prototype)
            $this->town_handler->addBuilding( $town, $this->entity_manager->getRepository(BuildingPrototype::class)->findOneByName( $str_prototype ) );

        foreach ($conf->get(TownConf::CONF_BUILDINGS_CONSTRUCTED) as $str_prototype) {
            /** @var BuildingPrototype $proto */
            $proto = $this->entity_manager->getRepository(BuildingPrototype::class)->findOneByName( $str_prototype );
            $b = $this->town_handler->addBuilding( $town, $proto );
            $b->setAp( $proto->getAp() )->setComplete( true );
        }

        $this->town_handler->calculate_zombie_attacks( $town, 3 );

        $map_resolution = $this->getDefaultZoneResolution( $conf, $ox, $oy );
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

        $spawn_ruins = $conf->get(TownConf::CONF_NUM_RUINS, 0);

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
                if ($conf->get(TownConf::CONF_FEATURE_CAMPING, false)) {
                    $zone_list[$i]->setBlueprint(Zone::BlueprintAvailable);
                }

                if ($this->random_generator->chance(0.4)) $zone_list[$i]->setBuryCount( mt_rand(6, 20) );
            } else
                $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i]->getX(),2) + pow($zone_list[$i]->getY(),2) )/18) * 3);

            if ($zombies_base > 0) {
                $zombies_base = max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) );
                $zone_list[$i]->setZombies( $zombies_base )->setInitialZombies( $zombies_base );
            }
        }

        $item_spawns = $conf->get(TownConf::CONF_DISTRIBUTED_ITEMS, []);
        shuffle($zone_list);
        for ($i = 0; $i < min(count($item_spawns),count($zone_list)); $i++)
            $this->inventory_handler->forceMoveItem( $zone_list[$i]->getFloor(), $this->item_factory->createItem( $item_spawns[$i] ) );

        $this->zone_handler->dailyZombieSpawn( $town, 1, ZoneHandler::RespawnModeNone );

        $town->setForum((new Forum())->setTitle($town->getName()));

        $ownerUser = $this->entity_manager->getRepository(User::class)->findOneById(66);

        $threadBank = new Thread();
        $threadBank->setTitle($this->translator->trans('Bank', [], 'game'));
        $threadBank->setPinned(true);
        $threadBank->setOwner($ownerUser);
        $threadBank->setLastPost(new \DateTime());
        $postBank = new Post();
        $postBank->setDate(new \DateTime());
        $postBank->setOwner($ownerUser);
        $postBank->setText($this->translator->trans('In diesem Thread dreht sich alles um die Bank.', [], 'game'));
        $threadBank->addPost($postBank);

        $town->getForum()->addThread($threadBank);

        $threadDailyVote = new Thread();
        $threadDailyVote->setTitle($this->translator->trans('Verbesserung des Tages', [], 'game'));
        $threadDailyVote->setPinned(true);
        $threadDailyVote->setOwner($ownerUser);
        $threadDailyVote->setLastPost(new \DateTime());
        $postDailyVote = new Post();
        $postDailyVote->setDate (new \DateTime());
        $postDailyVote->setOwner($ownerUser);
        $postDailyVote->setText($this->translator->trans('In diesem Thread dreht sich alles um die geplanten Verbesserungen des Tages.', [], 'game'));
        $threadDailyVote->addPost($postDailyVote);

        $town->getForum()->addThread($threadDailyVote);

        $threadWorkshop = new Thread();
        $threadWorkshop->setTitle($this->translator->trans('Werkstatt', [], 'game'));
        $threadWorkshop->setPinned(true);
        $threadWorkshop->setOwner($ownerUser);
        $threadWorkshop->setLastPost(new \DateTime());
        $postWorkshop = new Post();
        $postWorkshop->setDate (new \DateTime());
        $postWorkshop->setOwner($ownerUser);
        $postWorkshop->setText($this->translator->trans('In diesem Thread dreht sich alles um die Werkstatt und um Ressourcen.', [], 'game'));
        $threadWorkshop->addPost($postWorkshop);

        $town->getForum()->addThread($threadWorkshop);

        $threadBuilding = new Thread();
        $threadBuilding->setTitle($this->translator->trans('Konstruktionen', [], 'game'));
        $threadBuilding->setPinned(true);
        $threadBuilding->setOwner($ownerUser);
        $threadBuilding->setLastPost(new \DateTime());
        $postBuilding = new Post();
        $postBuilding->setDate (new \DateTime());
        $postBuilding->setOwner($ownerUser);
        $postBuilding->setText($this->translator->trans('In diesem Thread dreht sich alles um zukünftige Bauprojekte.', [], 'game'));
        $threadBuilding->addPost($postBuilding);

        $town->getForum()->addThread($threadBuilding);

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
            ->setCauseOfDeath( $this->entity_manager->getRepository( CauseOfDeath::class )->findOneByRef( CauseOfDeath::Unknown ) );
        (new Inventory())->setCitizen( $citizen );
        $this->citizen_handler->inflictStatus( $citizen, 'clean' );

        $this->citizen_handler->applyProfession( $citizen, $base_profession );

        $this->inventory_handler->forceMoveItem( $chest, $this->item_factory->createItem( 'chest_citizen_#00' ) );
        $this->inventory_handler->forceMoveItem( $chest, $this->item_factory->createItem( 'food_bag_#00' ) );

        $heroic_actions = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findAll();
        foreach ($heroic_actions as $heroic_action)
            /** @var $heroic_action HeroicActionPrototype */
            $citizen->addHeroicAction( $heroic_action );

        $this->entity_manager->persist( $this->log->citizenJoin( $citizen ) );

        return $citizen;
    }
}
