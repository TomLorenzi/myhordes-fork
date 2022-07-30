<?php


namespace App\Service;

use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\Forum;
use App\Entity\Gazette;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\Season;
use App\Entity\Shoutbox;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\Thread;
use App\Entity\ThreadTag;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\TownSlotReservation;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use App\Translation\T;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class GameFactory
{
    private EntityManagerInterface $entity_manager;
    private GameValidator $validator;
    private Locksmith $locksmith;
    private ItemFactory $item_factory;
    private RandomGenerator $random_generator;
    private InventoryHandler $inventory_handler;
    private CitizenHandler $citizen_handler;
    private UserHandler $user_handler;
    private ZoneHandler $zone_handler;
    private TownHandler $town_handler;
    private LogTemplateHandler $log;
    private ConfMaster $conf;
    private TranslatorInterface $translator;
    private MazeMaker $maze_maker;
    private CrowService $crow;
    private PermissionHandler $perm;
    private TimeKeeperService $timeKeeper;

    const ErrorNone = 0;
    const ErrorTownClosed          = ErrorHelper::BaseTownSelectionErrors + 1;
    const ErrorUserAlreadyInGame   = ErrorHelper::BaseTownSelectionErrors + 2;
    const ErrorUserAlreadyInTown   = ErrorHelper::BaseTownSelectionErrors + 3;
    const ErrorNoDefaultProfession = ErrorHelper::BaseTownSelectionErrors + 4;

    const ErrorTownNoCoaRoom         = ErrorHelper::BaseTownSelectionErrors + 5;
    const ErrorMemberBlocked         = ErrorHelper::BaseTownSelectionErrors + 6;
    private GameProfilerService $gps;

    public function __construct(ConfMaster $conf,
        EntityManagerInterface $em, GameValidator $v, Locksmith $l, ItemFactory $if, TownHandler $th, TimeKeeperService $ts,
        RandomGenerator $rg, InventoryHandler $ih, CitizenHandler $ch, ZoneHandler $zh, LogTemplateHandler $lh,
        TranslatorInterface $translator, MazeMaker $mm, CrowService $crow, PermissionHandler $perm, UserHandler $uh, GameProfilerService $gps)
    {
        $this->entity_manager = $em;
        $this->validator = $v;
        $this->locksmith = $l;
        $this->item_factory = $if;
        $this->random_generator = $rg;
        $this->inventory_handler = $ih;
        $this->citizen_handler = $ch;
        $this->user_handler = $uh;
        $this->zone_handler = $zh;
        $this->town_handler = $th;
        $this->log = $lh;
        $this->conf = $conf;
        $this->translator = $translator;
        $this->maze_maker = $mm;
        $this->crow = $crow;
        $this->perm = $perm;
        $this->gps = $gps;
        $this->timeKeeper = $ts;
    }

    private static array $town_name_snippets = [
        'de' => [
            [  // Sächlich Singular
                ['Tödliches', 'Modriges', 'Schimmliges', 'Eisiges', 'Rotes', 'Einsames', 'Ghulverseuchtes', 'Zombifiziertes', 'Bekanntes', 'Abgenagtes', 'Verstörendes', 'Letztes'],
                ['Wasserloch', 'Hospital', 'Trainingslager', 'Pony', 'Niemandsland', 'Gericht', 'Reich', 'Dreckloch', 'Gehirn', 'Rattenloch', 'Gebiet', 'Lager'],
            ],
            [  // Männlich Singular
                ['Eifriger', 'Weinender', 'Schimmliger', 'Alberner', 'Autoritärer', 'Einsamer', 'Triefender', 'Kontaminierter', 'Verschlafener', 'Masochistischer', 'Besoffener'],
                ['Müllberg', 'Seelenfänger', 'Monarch', 'Fels', 'Untergang', 'Wald', 'Folterkeller', 'Bezirk', 'Bunker', 'Tisch', 'Husten', 'Laster'],
            ],
            [  // Weiblich Singular
                ['Eifrige', 'Weinende', 'Schimmlige', 'Alberne', 'Autoritäre', 'Einsame', 'Triefende', 'Kontaminierte', 'Verschlafene', 'Masochistische', 'Besoffene'],
                ['Region', 'Insel', 'Anhöhe', 'Felsspalte', 'Apokalypse', 'Wiese', 'Höhle', 'Kammer', 'Untiefe', 'Miliz', 'Träne', 'Latrine'],
            ],
            [  // Plural
                ['Eifrige', 'Modrige', 'Glitschige', 'Eisige', 'Drogensüchtige', 'Gespenstische', 'Ghulverseuchte', 'Zombifizierte', 'Bewegte', 'Betrunkene', 'Virulente', 'Betroffene'],
                ['Metzger', 'Zombieforscher', 'Gestalten', 'Wächter', 'Todesgesänge', 'Schaffner', 'Soldaten', 'Zwillinge', 'Regionen', 'Oberfläche', 'Schmarotzer', 'Entwickler'],
            ],
            [  // Plural mit Suffix
                ['Ghulgebeine', 'Gesänge', 'Schmerzen', 'Schreie', 'Räume', 'Meute', 'Ghetto', 'Bürger', 'Hinterlassenschaft', 'Revier', 'Folterkeller', 'Alkoholpanscher'],
                ['des Todes', 'der Verdammnis', 'ohne Zukunft', 'am Abgrund', 'der Verwirrten', 'ohne Ideen', 'der Versager', 'der Ghule', 'der Superhelden', 'der Mutlosen', 'der Fröhlichen', 'der Revolutionäre', 'von Deepnight'],
            ],
        ],
        'en' => [
            [   // Prefixed Adjective
                ['Deadly', 'Mouldy', 'Moldy', 'Icy', 'Red', 'Lonely', 'Ghoulish', 'Zombified', 'Known', 'Gnawed', 'Disturbing', 'Last', 'Eager', 'Crying', 'Silly', 'Authoritarian', 'Lonely', 'Dripping', 'Contaminated', 'Sleepy', 'Masochistic', 'Drunk', 'Musty', 'Slippery', 'Icy', 'Drug addicts', 'Spooky', 'Ghoul-infested', 'Moving', 'Virulent', 'Affected'],
                ['Waterhole', 'Hospital', 'Training camp', 'Pony', 'No man\'s land', 'Court', 'Empire', 'Shithole', 'Brain', 'Rathole', 'Area', 'Camp', 'Garbage Mountain', 'Soul Catcher', 'Monarch', 'Rock', 'Fall', 'Forest', 'Torture Basement', 'District', 'Bunker', 'Table', 'Cough', 'Truck', 'Butchers', 'Zombie researchers', 'Figures', 'Guardians', 'Death songs', 'Conductor', 'Soldiers', 'Twins', 'Regions', 'Surface', 'Parasites', 'Developers', 'Skool'],
            ],
            [  // Suffixed
                ['Ghoul bones', 'Songs', 'Pain', 'Screams', 'Rooms', 'Mob', 'Ghetto', 'Citizens', 'Legacy', 'Territory', 'Torture chamber', 'Alcohol Adulterants'],
                ['of Death', 'of Damnation', 'without Future', 'at the Abyss', 'of the Confused', 'without Ideas', 'of the Failures', 'of the Ghouls', 'of the Superheroes', 'of the Discouraged', 'of the Cheerful', 'of the Revolutionaries', 'of the Deepnight'],
            ],
        ],
        'fr' => [
            [
                // Masculin singular
                ['Abîme', 'Antre', 'Avant-Poste', 'Bidonville', 'Camp', 'Canyon', 'Centre', 'Cimetière', 'Cloaque', 'Colisée', 'Comté', 'Coteau', 'Echo', 'Enfer', 'Espace', 'Espoir', 'Fort', 'Gouffre', 'Hameau', 'Hypogée', 'Lieu-dit', 'Monolithe', 'Mont', 'Mystère', 'Refuge', 'Sommet', 'Souterrain', 'Tertre', 'Théâtre', 'Tombeau', 'Trou', 'Tumulus', 'Vide', 'Village'],
                ['abandonné', 'abattu', 'abrité', 'accablé', 'affligé', 'anecdotique de l\'hiver', 'angoissant', 'angoissant de l\'Indicible', 'antique', 'antique de La Nuit Profonde', 'antique des Affamés', 'anéanti', 'archaïque', 'arriviste', 'atrabilaire', 'atrabilaire des Idiots', 'atroce', 'attardé', 'attardé de l\'automne', 'attristé', 'attristé des Ellipses', 'austère', 'battu', 'blafard', 'blafard des Insurgés', 'brutal', 'brutal des Sarcasmes', 'caché', 'chaud', 'chaud de La Faim', 'chaud des Damnés', 'chaud des Peaux', 'colérique', 'colérique d\'Ani La Peintre', 'consterné', 'contaminé', 'contaminé du Tréfonds', 'craquelé', 'croulant', 'croulant des Damnés', 'crépusculaire', 'd\'Ani Enciélé', 'd\'Ani Le Coloré', 'd\'Arkham', 'd\'Eole l\'inspiratrice', 'd\'Eole notre guide', 'd\'Hadès', 'd\'Halloween', 'd\'Irma', 'd\'Irvie Aux Gommes', 'damné', 'damné de Twin', 'de Celles Qui Prient', 'de Dizan', 'de Feu', 'de Goatswood', 'de Hiko le Fruité', 'de Hiko Twin', 'de Hk L\'insoumis', 'de Junon Pervertie', 'de Kiroukou le poète', 'de L\'Angoisse', 'de l\'Anguille Courbée', 'de l\'Armageddon', 'de l\'automne', 'de l\'Effroi', 'de l\'est', 'de l\'hiver', 'de l\'Or Bleu', 'de l\'orient', 'de l\'Oublié', 'de l\'Usurpateur', 'de l\'Âtre Ancien', 'de l\'été', 'de la Mort Lente', 'de la Nouvelle Espérance', 'de La Nuit Profonde', 'de la Paix Verte', 'de la rédemption', 'de la Soif', 'de la sorcière', 'de LeChuck', 'de Molineux', 'de Murakami', 'de Refactor Yota', 'de Shaolin Kiroukou', 'de Shining Gygy', 'de Shoot\'em Up Bumdum', 'de Shubi le coquin', 'de Thinkslow', 'de Threepwood', 'de Twin', 'de Warp l\'architecte', 'de Warp le Magicien', 'de Whitetigle', 'de Whitetigle le juste', 'de Yota le Terrible', 'de Yukito la cruelle', 'des Abcès Douloureux', 'des Affamés', 'des Anonymes', 'des Arts Ubuesques', 'des Aveugles Brûlés', 'des Brutes', 'des Capraphiles', 'des Chairs', 'des Citoyens Anonymes', 'des Conquis', 'des Cordes', 'des Damnés', 'des Demies Vies', 'des Eclairs Vacillants', 'des effusions de sang', 'des Ellipses', 'des Enfers', 'des Gardiens Effacés', 'des Grands Anciens', 'des Hordes', 'des Hydratones', 'des Idiots', 'des Insoumis', 'des Insurgés', 'des Lapinous', 'des Larmes de Sang', 'des Liners Détruits', 'des Lépreux Morts', 'des Membres de Pus', 'des Miraculés Bannis', 'des morts qui dansent', 'des Norrissiens', 'des Noëlistes', 'des Nuits de l\'Horreur', 'des Nuits sans Fin', 'des Oubliés', 'des Parcs Ensevelis', 'des Perdus', 'des Piouz Transis', 'des Pouilleux', 'des Premiers Jours', 'des Psychotiques', 'des Sadiques', 'des Sanglots Sourds', 'des Sarcasmes', 'des Tzongres Noirs', 'des Vents Brûlants', 'des Vents Sifflants', 'des Vétérans', 'des Éclaireurs Disparus', 'despotique', 'désuet', 'désuet de l\'été', 'dirigiste', 'disparu', 'du Cyclope', 'du Gant de Combat', 'du Haut', 'du Morne Quotidien', 'du nord', 'du Plasticien Taquin', 'du Premier Jour', 'du Printemps', 'du Rebond Alpha', 'du Roi Corbeau', 'du sud', 'du Tréfonds', 'du Vent Fou', 'dur', 'découragé', 'découragé de Molineux', 'déprimant', 'désemparé', 'déserté', 'désolé des Sarcasmes', 'déséquilibré', 'enseveli', 'enterré', 'enterré des Lapinous', 'fossile', 'froid', 'funeste', 'funeste des Déshérités', 'funeste des Norrissiens', 'funèbre', 'funèbre des Demies Vies', 'féroce', 'féroce de Warp le Magicien', 'gangrené', 'glauque', 'glauque de La Faim', 'grave', 'grave des Chairs', 'grave des Hordes', 'gris', 'humide', 'ignorant', 'implacable', 'inculte', 'infecté', 'inférieur', 'inférieur d\'HypIrvie', 'inférieur des Brûlés', 'inhumain', 'inviolé de l\'Indicible', 'isolé des Déshérités', 'isolé sans avenir', 'jauni', 'jauni de Twin', 'lamentable', 'lamentable des Conquis', 'lugubre', 'lugubre de l\'Usurpateur', 'macabre', 'maudit', 'maussade', 'mauvais', 'moisi', 'morne', 'morose', 'mort', 'mort de l\'Oublié', 'mort de Molineux', 'mélancolique', 'nocturne', 'noir', 'nordique', 'nostalgique', 'obscur', 'passé', 'passé de Goatswood', 'passé des Viles Sangsues', 'passéiste', 'pathétique', 'pathétique d\'Irma', 'pervers', 'pervers de Dunwich', 'pervers des Insoumis', 'pitoyable de Nô', 'pourpre', 'pourpre de Shining Gygy', 'psychotique', 'putride', 'putride de l\'orient', 'pénible', 'pénible de Hiko Sharingan', 'pénible de La Peste Noire', 'périmé', 'ravagé', 'ravagé de l\'Effroi', 'ravagé de l\'Indicible', 'rouge', 'rude', 'rustre', 'rustre de l\'Âtre Ancien', 'rustre des Lames de Fond', 'réactionnaire', 'rétrograde', 'rétrograde de la citrouille creuse', 'sanglant', 'sanguinaire', 'sans avenir', 'sans Lendemain', 'sauvage', 'sinistre', 'sinistre des Nuits sans Fin', 'sombre', 'sordide', 'sordide du Gant de Combat', 'supérieur', 'taciturne', 'taciturne de Whitetigle', 'taciturne sans Fin', 'tendu de l\'est', 'terni', 'totalitaire', 'triste', 'tétanisé', 'tétanisé sans avenir', 'vandale', 'verdâtre', 'verdâtre des Chairs', 'versicolore de La Faim', 'éploré', 'épouvantable', 'étrange'],
            ],
            [
                // Féminin singular
                ['Annexe', 'Arène', 'Attraction', 'Butte', 'Caserne', 'Caverne', 'Cavité', 'Citadelle', 'Cité', 'Coalition', 'Colline', 'Communauté', 'Contrée', 'Crique', 'Croisée', 'Côte', 'Désolation', 'Dévastation', 'Enclave', 'Engeance', 'Etendue', 'Faille', 'Falaise', 'Fosse', 'Fossé', 'Frontière', 'Gangrène', 'Ignorance', 'Immensité', 'Indolence', 'Installation', 'Jonction', 'Multitude', 'Orbite', 'Pampa', 'Paranoïa', 'Plaine', 'Plantation', 'Prairie', 'Promenade', 'Retraite', 'Région', 'Rémanence', 'Steppe', 'Structure', 'Surface', 'Terre', 'Toundra', 'Tranchée', 'Vallée'],
                ['abattue', 'abattue d\'Ani Le Coloré', 'abattue de l\'est', 'abritée', 'accablée', 'accablée de l\'été', 'affligée', 'anecdotique', 'angoissante', 'angoissante de La Faim', 'antique', 'anéantie', 'archaïque', 'arriérée', 'atrabilaire', 'atrabilaire de l\'Abîme', 'atrabilaire de Twin', 'atroce', 'atroce de l\'est', 'atroce des Lapinous', 'attardée', 'attardée des Damnés', 'attristée', 'attristée de Molineux', 'austère', 'austère du Tréfonds', 'aux sorcières', 'barbare', 'battue', 'blafarde', 'blafarde aux citrouilles', 'brutale', 'cachée', 'chaude', 'colérique', 'consternée', 'contaminée', 'corrompue', 'craquelée', 'croulante', 'crépusculaire', 'd\'Ani Enciélé', 'd\'Ani La Peintre', 'd\'Ani Le Coloré', 'd\'Arkham', 'd\'Eole notre guide', 'd\'Halloween', 'd\'HypIrvie', 'd\'Irma', 'd\'Irvie Aux Gommes', 'd\'Irvie Le Terrible', 'damnée', 'de Abyssal\' Hk', 'de Bumdum le Versicolore', 'de Celles Qui Prient', 'de Chéloné La Lente', 'de Dizan', 'de Feu', 'de Goatswood', 'de Hiko le Fruité', 'de Hk L\'insoumis', 'de Hk le Colorimétrique', 'de l\'Abîme', 'de L\'Angoisse', 'de l\'Anguille Courbée', 'de l\'Armageddon', 'de l\'Effroi', 'de l\'hiver', 'de l\'Indicible', 'de L\'Obscurité Vaine', 'de l\'Or Bleu', 'de l\'orient', 'de l\'Oublié', 'de l\'ouest', 'de l\'Usurpateur', 'de l\'Âtre Ancien', 'de la Chimie Naturelle', 'de La Faim', 'de la fête des morts', 'de La Marque du Loup', 'de la Mort Lente', 'de la Paix Verte', 'de La Peste Noire', 'de La Peur Au Ventre', 'de la rédemption', 'de la Soif', 'de la sorcière', 'de LeChuck', 'de McBess le Folâtre', 'de Molineux', 'de Murakami', 'de Nô', 'de Particules\' Bumdum', 'de Putréfaction', 'de Shaolin Kiroukou', 'de Shining Gygy', 'de Shubi le coquin', 'de Thinkslow', 'de Threepwood', 'de Twin', 'de Warp l\'architecte', 'de Warp le Magicien', 'de Whitetigle', 'de Whitetigle le juste', 'de Yota le Terrible', 'de Yukito la cruelle', 'des Abcès Douloureux', 'des Affamés', 'des Anonymes', 'des Antivilles', 'des Arts Ubuesques', 'des Aveugles Brûlés', 'des Bactéries', 'des Bannis', 'des Brutes', 'des Brûlés', 'des Capraphiles', 'des Chairs', 'des Citoyens Anonymes', 'des Citoyens Perdus', 'des Conquis', 'des Cordes', 'des Damnés', 'des Demies Vies', 'des Déshérités', 'des effusions de sang', 'des Ellipses', 'des Eléments Tristes', 'des Fouineurs Enterrés', 'des Frutimetières', 'des Grands Anciens', 'des Hordes', 'des Idiots', 'des Insoumis', 'des Insurgés', 'des Jours sans Fin', 'des Jumeaux Mobiles', 'des Lames de Fond', 'des Larmes de Sang', 'des Liners Détruits', 'des Lépreux Morts', 'des Maux Oubliés', 'des Membres de Pus', 'des Miraculés Bannis', 'des Norrissiens', 'des Noëlistes', 'des Nuits de l\'Horreur', 'des Nuits sans Fin', 'des Oubliés', 'des Parcs Ensevelis', 'des Peaux', 'des Pensées Futiles', 'des Perdus', 'des Piouz Transis', 'des Pouilleux', 'des Premiers Jours', 'des Psychotiques', 'des Sadiques', 'des Sanglots Sourds', 'des Sarcasmes', 'des Tzongres Noirs', 'des Vents Sifflants', 'des Viles Sangsues', 'des Éclaireurs Disparus', 'despotique', 'désuete', 'dirigiste', 'disparue des Oubliés', 'du chaudron', 'du Cyclope', 'du Gant de Combat', 'du Haut', 'du Mistral Perdant', 'du Morne Quotidien', 'du nord', 'du Plasticien Taquin', 'du Poisson Bulle', 'du Premier Jour', 'du Printemps', 'du Pus', 'du Rebond Alpha', 'du Roi Corbeau', 'du sud', 'du Tréfonds', 'du Vent Fou', 'du Vieux Parc à Fruits', 'dure', 'découragée', 'déprimante', 'désemparée', 'désemparée de la citrouille creuse', 'désertée', 'désolée', 'déséquilibrée', 'ensevelie', 'enterrée', 'funeste', 'funeste des Ellipses', 'funèbre', 'féroce', 'féroce de Feu', 'glauque', 'grave', 'grave de la Mort Lente', 'grise', 'grise de Hk L\'insoumis', 'humide', 'ignorante', 'impitoyable', 'implacable', 'inculte des Hordes', 'infectée', 'inflexible', 'inférieure', 'inhumaine', 'inviolée', 'jauni de l\'Abîme', 'jaunie', 'jaunie de Refactor Yota', 'lamentable', 'lamentable de Threepwood', 'lamentable des Brutes', 'livide', 'livide sans avenir', 'lugubre', 'macabre', 'macabre de La Peste Noire', 'maussade', 'maussade d\'HypIrvie', 'mauvaise aux potimarrons', 'mauvaise de l\'Abîme', 'mauvaise de l\'été', 'moisie', 'moisie des Antivilles', 'morne', 'morose', 'morte', 'médiocre', 'médiocre de Whitetigle', 'mélancolique', 'nocturne', 'nocturne de l\'ouest', 'nocturne sans Fin', 'noire', 'nordique', 'nordique de Dizan', 'nostalgique', 'obscure', 'obsédée des Lapinous', 'passée', 'passéiste', 'pathétique', 'perdue', 'perdue des Insoumis', 'perverse', 'pitoyable', 'pitoyable de Twin', 'pourpre', 'pourpre du Gant de Combat', 'psychotique', 'psychotique de Dizan', 'putride', 'putride des Hydratones', 'putride sans avenir', 'pénible', 'pénible des Sarcasmes', 'périmée', 'ravagée', 'rouge', 'rouge des Idiots', 'rude', 'rude des Cordes', 'rustre', 'réactionnaire', 'rétrograde de Dunwich', 'sanglante', 'sanguinaire', 'sans avenir', 'sans but', 'sans Fin', 'sans Lendemain', 'sauvage', 'sombre', 'sordide', 'supérieure', 'taciturne', 'tendue d\'Arkham', 'ternie', 'ternie de Goatswood', 'totalitaire', 'triste', 'triste de Dunwich', 'triste des Piouz Transis', 'triste sans but', 'tétanisée', 'vandale', 'vandale des Noëlistes', 'verdâtre', 'versicolore', 'violée', 'violée des Chairs', 'éplorée', 'éplorée sans avenir', 'épouvantable', 'étrange', 'étrange de l\'orient'],
            ],
            [
                // Masculin plural
                ['Abîmes', 'Bas-fonds', 'Canyons', 'Colisées', 'Comtés', 'Coteaux', 'Espaces', 'Forts', 'Fossés', 'Remparts', 'Sommets', 'Songes', 'Souterrains', 'Tertres', 'Théâtres', 'Trous'],
                ['abandonnés', 'abattus', 'abrités de Shining Gygy', 'abrités des Chairs', 'affligés', 'affligés d\'Halloween', 'angoissants', 'angoissants de la fête des morts', 'antiques', 'archaïques', 'arrivistes', 'arriérés', 'atrabilaires', 'atroces', 'attardés', 'aux sorcières', 'battus', 'blafards des Peaux', 'brutals', 'brutals des morts qui dansent', 'cachés du sud', 'colériques', 'colériques de la Soif', 'consternés', 'corrompus', 'craquelés', 'croulants', 'crépusculaires', 'd\'Ani Enciélé', 'd\'Arkham', 'd\'Eole l\'inspiratrice', 'd\'Eole notre guide', 'd\'Hadès', 'd\'Irma', 'd\'Irvie Aux Gommes', 'd\'Irvie Le Terrible', 'de Bumdum le Versicolore', 'de Dizan', 'de Feu', 'de Goatswood', 'de Gyhyom le Fluorescent', 'de Hiko Sharingan', 'de Hiko Twin', 'de Kiroukou le poète', 'de l\'Abîme', 'de L\'Angoisse', 'de l\'Effroi', 'de l\'est', 'de l\'hiver', 'de l\'Indicible', 'de l\'Oublié', 'de l\'Âtre Ancien', 'de La Faim', 'de la fête des morts', 'de La Marque du Loup', 'de La Nuit Profonde', 'de la Paix Verte', 'de La Peste Noire', 'de la Soif', 'de LeChuck', 'de Nô', 'de Particules\' Bumdum', 'de Putréfaction', 'de Shaolin Kiroukou', 'de Thinkslow', 'de Threepwood', 'de Warp le Magicien', 'de Whitetigle', 'de Whitetigle le juste', 'de Yukito la cruelle', 'des Affamés', 'des Anonymes', 'des Antivilles', 'des Arts Ubuesques', 'des Bactéries', 'des Brutes', 'des Brûlés', 'des Capraphiles', 'des Citoyens Anonymes', 'des Citoyens Perdus', 'des Cordes', 'des Déshérités', 'des Eclairs Vacillants', 'des effusions de sang', 'des Ellipses', 'des Eléments Tristes', 'des Enfers', 'des Gardiens Effacés', 'des Grands Anciens', 'des Hordes', 'des Insoumis', 'des Insurgés', 'des Jumeaux Mobiles', 'des Lames de Fond', 'des Lapinous', 'des Larmes de Sang', 'des Liners Détruits', 'des Maux Oubliés', 'des Miraculés Bannis', 'des Nuits de l\'Horreur', 'des Nuits sans Fin', 'des Peaux', 'des Pensées Futiles', 'des Perdus', 'des Piouz Transis', 'des Premiers Jours', 'des Sadiques', 'des Sanglots Sourds', 'des Sarcasmes', 'des Vents Brûlants', 'dirigistes', 'du Cyclope', 'du Gant de Combat', 'du Mistral Perdant', 'du Morne Quotidien', 'du nord', 'du Poisson Bulle', 'du Printemps', 'du Pus', 'du Tréfonds', 'du Vent Fou', 'découragés', 'désemparés', 'désemparés d\'Hadès', 'désertés', 'déséquilibrés', 'enterrés', 'fossiles', 'froids de Murakami', 'funestes', 'funestes de la Mort Lente', 'funèbres', 'gangrenés', 'gris', 'implacables', 'infectés', 'inflexibles', 'inhumains', 'inviolés', 'inviolés du sud', 'isolés', 'isolés de Putréfaction', 'jaunis', 'lamentables', 'livides', 'mauvais', 'mauvais des Perdus', 'mornes de Hiko Twin', 'mornes du chaudron', 'moroses', 'moroses des Peaux', 'médiocres', 'noirs', 'nordiques', 'nordiques du Tréfonds', 'nostalgiques', 'obscurs', 'obsolètes', 'obsolètes aux potimarrons', 'passéistes', 'passés des Peaux', 'perdus', 'pourpres', 'psychotiques', 'putrides', 'putrides des Insoumis', 'pénibles du Cyclope', 'périmés', 'ravagés', 'rouges', 'rudes', 'réactionnaires', 'rétrogrades', 'sanglants', 'sans avenir', 'sans but', 'sans Fin', 'sans Lendemain', 'sombres', 'sordides', 'tendus', 'ternis', 'totalitaires', 'tristes de Hk L\'insoumis', 'vandales', 'verdâtres', 'versicolores', 'étranges']
            ],
            [
                // Féminin plural
                ['Arènes', 'Buttes', 'Casernes', 'Cavernes', 'Cavités', 'Coalitions', 'Collines', 'Contrées', 'Croisées', 'Côtes', 'Désolations', 'Dévastations', 'Engeances', 'Etendues', 'Fosses', 'Immensités', 'Inconnues', 'Installations', 'Jonctions', 'Multitudes', 'Orbites', 'Plaines', 'Prairies', 'Promenades', 'Retraites', 'Régions', 'Steppes', 'Structures', 'Surfaces', 'Terres', 'Vallées'],
                ['abandonnées', 'abattues', 'abritées', 'abritées du Pus', 'accablées', 'affligées', 'antiques', 'antiques d\'Arkham', 'anéanties', 'anéanties sans but', 'archaïques', 'arrivistes', 'arriérées', 'atrabilaires', 'attardées', 'attristées', 'austères', 'aux potimarrons', 'aux sorcières', 'blafardes', 'brutales', 'cachées', 'chaudes', 'colériques', 'consternées', 'contaminées', 'corrompues', 'craquelées', 'croulantes', 'crépusculaires', 'd\'Ani Enciélé', 'd\'Ani La Peintre', 'd\'Ani Le Coloré', 'd\'HypIrvie', 'd\'Irma', 'd\'Irvie Aux Gommes', 'd\'Irvie Le Terrible', 'damnées', 'damnées d\'Irma', 'de Abyssal\' Hk', 'de Celles Qui Prient', 'de Dizan', 'de Dunwich', 'de Feu', 'de Goatswood', 'de Hiko le Fruité', 'de Hiko Sharingan', 'de Hk L\'insoumis', 'de Junon Pervertie', 'de Kiroukou le poète', 'de Korppi', 'de l\'Abîme', 'de L\'Angoisse', 'de l\'Anguille Courbée', 'de l\'Armageddon', 'de l\'automne', 'de l\'Effroi', 'de l\'Indicible', 'de L\'Obscurité Vaine', 'de l\'Or Bleu', 'de l\'orient', 'de l\'ouest', 'de l\'Âtre Ancien', 'de l\'été', 'de la Chimie Naturelle', 'de La Faim', 'de La Marque du Loup', 'de la Mort Lente', 'de La Nuit Profonde', 'de la Paix Verte', 'de La Peste Noire', 'de La Peur Au Ventre', 'de la rédemption', 'de la Soif', 'de la sorcière', 'de LeChuck', 'de McBess le Folâtre', 'de Molineux', 'de Murakami', 'de Particules\' Bumdum', 'de Putréfaction', 'de Shining Gygy', 'de Shoot\'em Up Bumdum', 'de Shubi le coquin', 'de Thinkslow', 'de Threepwood', 'de Warp le Magicien', 'de Whitetigle', 'de Whitetigle le juste', 'de Yota le Terrible', 'de Yukito la cruelle', 'des Abcès Douloureux', 'des Affamés', 'des Anonymes', 'des Arts Ubuesques', 'des Aveugles Brûlés', 'des Bannis', 'des Brutes', 'des Brûlés', 'des Capraphiles', 'des Citoyens Perdus', 'des Conquis', 'des Cordes', 'des Demies Vies', 'des Déshérités', 'des effusions de sang', 'des Ellipses', 'des Eléments Tristes', 'des Enfers', 'des Frutimetières', 'des Grands Anciens', 'des Idiots', 'des Insoumis', 'des Jumeaux Mobiles', 'des Lames de Fond', 'des Lapinous', 'des Larmes de Sang', 'des Liners Détruits', 'des Lépreux Morts', 'des Maux Oubliés', 'des Miraculés Bannis', 'des morts qui dansent', 'des Norrissiens', 'des Noëlistes', 'des Nuits de l\'Horreur', 'des Oubliés', 'des Peaux', 'des Pensées Futiles', 'des Piouz Transis', 'des Premiers Jours', 'des Sadiques', 'des Tremblements de Terre', 'des Vents Sifflants', 'des Viles Sangsues', 'des Vétérans', 'des États Désunis', 'désuetes', 'dirigistes', 'disparues', 'disparues des Capraphiles', 'du Cyclope', 'du Gant de Combat', 'du Mistral Perdant', 'du Morne Quotidien', 'du nord', 'du Poisson Bulle', 'du Premier Jour', 'du Printemps', 'du Pus', 'du Rebond Alpha', 'du sud', 'du Tréfonds', 'du Vent Fou', 'déprimantes', 'désemparées', 'désertées', 'désertées de Nô', 'désolées', 'désolées du Haut', 'déséquilibrées', 'ensevelies', 'enterrées', 'fossiles', 'froides', 'froides sans avenir', 'funestes', 'funèbres', 'funèbres des Damnés', 'féroces', 'féroces sans but', 'gangrenées', 'glauques', 'glauques des Anonymes', 'grises', 'grises de l\'Usurpateur', 'humides', 'humides des Brutes', 'impitoyables', 'implacables', 'incultes', 'inhumaines sans Fin', 'inviolées du Printemps', 'isolées de Thinkslow', 'jaunies', 'jaunies des Lapinous', 'lamentables', 'livides', 'lugubres', 'macabres', 'maudites', 'maudites de l\'hiver', 'maussades', 'mauvaises', 'moisies', 'mornes du Pus', 'moroses', 'mortes', 'médiocres', 'mélancoliques', 'nocturnes', 'noires', 'noires des Pouilleux', 'nordiques', 'nordiques de l\'est', 'nostalgiques', 'obscures', 'obsolètes', 'obsédées', 'passées', 'passéistes', 'pathétiques', 'perdues', 'perverses', 'pitoyables', 'pitoyables de Dunwich', 'pourpres', 'pourpres de Nô', 'pourpres de Whitetigle', 'pourpres des citrouilles', 'psychotiques', 'putrides', 'pénibles', 'pénibles des Antivilles', 'périmées', 'ravagées', 'ravagées de l\'Oublié', 'rouges', 'rudes', 'rustres', 'réactionnaires', 'rétrogrades', 'rétrogrades des Hordes', 'sanglantes', 'sanguinaires', 'sans Fin', 'sans Lendemain', 'sauvages', 'sombres', 'sordides', 'taciturnes', 'tendues', 'ternies', 'totalitaires', 'tristes', 'tétanisées', 'vandales', 'versicolores', 'violées', 'violées de l\'hiver', 'éplorées', 'épouvantables', 'étranges']
            ]
        ],
        'es' => [
            [
                // Masculino singular
                ['Abismo', 'Acantilado', 'Altar', 'Antro', 'Arrabal', 'Averna', 'Barrio', 'Belén', 'Campo', 'Canto', 'Caserío', 'Cañón', 'Cementerio', 'Cerro', 'Condado', 'Corral', 'Eco', 'Edén', 'Enclave', 'Espacio', 'Fuerte', 'Fundo', 'Fósil', 'Hito', 'Hoyo', 'Infierno', 'Misterio', 'Monolito', 'Monte', 'Montículo', 'Pabellón', 'Paisaje', 'Paraíso', 'Paseo', 'Poblado', 'Pozo', 'Prado', 'Precipicio', 'Recinto', 'Refugio', 'Retiro', 'Rincón', 'Santuario', 'Sepulcro', 'Sitio', 'Subsuelo', 'Suburbio', 'Suspiro', 'Teatro', 'Vacío', 'Valle', 'Vergel', 'Villorio'],
                ['Abandonado', 'Abatido', 'Afligido', 'Agreste', 'Agrietado', 'Aislado', 'Alegre', 'Alto', 'Anticuado', 'Antiguo', 'Apagado', 'Arcaico', 'Ardiente', 'Austero', 'Bajo', 'Bárbaro', 'Caduco', 'Caliente', 'Celestial', 'Chico', 'Colérico', 'Condenado', 'Contaminado', 'Corrupto', 'Crítico', 'Deplorable', 'Deprimente', 'Desafortunado', 'Desagradable', 'Desamparado', 'Desaparecido', 'Desastroso', 'Desdichado', 'Desequilibrado', 'Desgraciado', 'Desierto', 'Desolado', 'Despiadado', 'Despótico', 'Detestable', 'Dichoso', 'Dirigista', 'Duro', 'Enterrado', 'Escondido', 'Extraño', 'Festivo', 'Frío', 'Funesto', 'Fúnebre', 'Gangrenado', 'Grande', 'Grave', 'Grotesco', 'Hediondo', 'Implacable', 'Infame', 'Infectado', 'Inhumano', 'Inmundo', 'Insípido', 'Iracundo', 'Jocoso', 'Lamentable', 'Loco', 'Lúgubre', 'Macabro', 'Maldito', 'Maligno', 'Malo', 'Mediocre', 'Melancólico', 'Mohoso', 'Morado', 'Muerto', 'Mugroso', 'Mustio', 'Nefasto', 'Negro', 'Nostálgico', 'Nuevo', 'Oscuro', 'Paralizado', 'Patético', 'Perdido', 'Perverso', 'Pesaroso', 'Protegido', 'Próspero', 'Psicótico', 'Pálido', 'Pútrido', 'Radiante', 'Rancio', 'Reaccionario', 'Redondo', 'Remoto', 'Repugnante', 'Retrógrado', 'Ridículo', 'Risueño', 'Rojo', 'Rudo', 'Salvaje', 'Sangriento', 'Sanguinario', 'Seco', 'Sepultado', 'Siniestro', 'Sucio', 'Sórdido', 'Taciturno', 'Telúrico', 'Tenebroso', 'Tenso', 'Tormentoso', 'Totalitario', 'Triste', 'Trágico', 'Tétrico', 'Venturoso', 'Villano', 'Violento', 'Árido', 'Áspero'],
                ['del Mago', 'de los Pasos Perdidos', 'del Oriente', 'del Crimen Impune', 'de la Luna', 'del Gran Poder', 'del Chiste Malo', 'de la Paz', 'de Mil Lágrimas', 'de la Ira', 'del Río Seco', 'del Suspiro', 'de la Brisa Fétida', 'de los Lamentos', 'de la Hermandad', 'del Ecuador', 'del Optimismo', 'del Sadismo', 'de la Justicia', 'de los Descosidos', 'de los Guardianes', 'del Héroe Olvidado', 'de la Sonrisa Enigmática', 'de Virgo', 'del Ceño Fruncido', 'de Sagitario', 'de lo Perverso', 'de los Milagros', 'de Orión', 'de Angustia', 'de la Esperanza', 'de Traición', 'del Ave Mensajera', 'del Este', 'de Géminis', 'del Instinto Carnal', 'del Miedo', 'del Espectro Verde', 'de la Patagonia', 'del Rey Cuervo', 'de Carroñeros', 'del Hard Rock', 'del Buen Tiempo', 'del Nosequé', 'de los Eclipses', 'de Paita', 'del Quinto Elemento', 'del Cuervo Loco', 'de lo Desconocido', 'de Nosedonde', 'del Silencio', 'del Pequeño Saltamontes', 'de San Mateo', 'de Aries', 'de Tabernas', 'de Guajira', 'del Otoño', 'del Queseyó', 'del Sueño Inspirador', 'de los Descalzos', 'del Olvido', 'de Baja California', 'de los Bipolares', 'de los Tuertos', 'del Antídoto', 'de la Tierra del Fuego', 'del Gran Guía', 'de los Topos', 'de los Renegados', 'de la Esmeralda', 'del Deseo Inútil', 'de Teneré', 'de Hendrix', 'del Troll Fantasma', 'del Ingenio', 'de Lágrimas Ajenas', 'de Murakami', 'de la Astucia', 'del Maestro Shaolín', 'del Oro Azul', 'del Grito Ahogado', 'de la Piel Cobriza', 'del Cojo', 'de la Verdad Absoluta', 'del Sur', 'de los Mártires', 'del Alma Pura', 'del Punto Rojo', 'de la Legión Púrpura', 'del Hambre', 'de los Caporales', 'de la Independencia', 'del Único Camino', 'del Castigo', 'del Espasmo', 'Sin Fin', 'de la Nada', 'de Carne Débil', 'del Dolor', 'de los Desaparecidos', 'del Socorro', 'de la Envidia', 'de la Aurora', 'del Yonofuí', 'del Norte', 'de Deepnight', 'de San Pedro', 'del Arquitecto Orate', 'del Insomnio', 'de Corazones', 'del Caballo Blanco', 'del Por Qué', 'del Pez Rabioso', 'de la Caridad', 'de los Ancestros Mayas', 'de los Ídolos', 'de Ficachi', 'del Ojo Ciego', 'de Tripas Secas', 'de la Juventud', 'de Atacama', 'de la Gran Mentira', 'del Humo Negro', 'de San Quintín', 'del Pensamiento', 'de Babasónicos', 'de los Brazos Cruzados', 'de la Redención', 'de la Avaricia', 'de Changó', 'de Monegros', 'de la Amargura', 'del Veneno Vil', 'del Pesimismo', 'del Pacífico', 'del Delirium Tremens', 'del Calambre', 'del Sarcasmo', 'de la Razón', 'de San Pablo', 'de la Utopía', 'de la Lluvia Perpetua', 'del Amor Interesado', 'de Ensueño', 'del Loco', 'de la Cachetada', 'de Giar', 'de Binto', 'de la Injusticia', 'de la Santería', 'de Nadie', 'de Zombiepolares', 'del Guante Caído', 'de Kalahari', 'de la Tranquilidad', 'de Tauro', 'de Kraterfire', 'de la Risa Contagiante', 'del Colmo', 'de la Alegría', 'de Sechura', 'de Malkev', 'del Ojo Triste', 'de Capricornio', 'de Sabiduría', 'del Poeta Solitario', 'de Sonora', 'de la Epidemia', 'del Embrujo', 'de los Temblores', 'de H4RO', 'del Mal Menor', 'de Bossu', 'del Oeste', 'del Díscolo', 'del Terrible Susto', 'del Camino Sinuoso', 'de la Carcajada', 'del Gran Jefe', 'del Pasado Brillante', 'del Nunca Jamás', 'de la Gula', 'de los Últimos Héroes', 'del Futuro Incierto', 'de los Babasónicos', 'de AlancapoII', 'del Cada Vez Peor', 'del Profeta', 'de Trendy', 'de Znarf', 'de Badenas Reales', 'de los Condenados', 'Sin Mañana', 'de Don Ramón', 'de Judas', 'del Morro Solar', 'de Fuego', 'del Sentido Común', 'de Paracas', 'del Primer Instinto', 'de Carroña', 'del Apocalipsis', 'de los 80', 'de la Luz', 'del Fin del Mundo', 'de Snow', 'del Cielo Prometido', 'de Quiensabequé', 'de la Soberbia', 'de Nazca', 'de Aldesa', 'de Sangre Caliente', 'de Amasijador', 'de la Peste Negra', 'de Invierno', 'del Pudor', 'de Shini', 'de la Serpiente Sin Fondo', 'de Mentira', 'de 163gal', 'de Primavera']
            ],
            [
                // Femenino singular
                ['Alameda', 'Aldea', 'Alianza', 'Barriada', 'Boca', 'Cala', 'Caverna', 'Ciudadela', 'Cloaca', 'Colina', 'Comarca', 'Cruzada', 'Cueva', 'Cumbre', 'Desolación', 'Ensenada', 'Estepa', 'Falla', 'Feria', 'Fosa', 'Frontera', 'Gracia', 'Gruta', 'Indolencia', 'Inmensidad', 'Ladera', 'Mirada', 'Muralla', 'Nirvana', 'Pampa', 'Piedra', 'Planicie', 'Pradera', 'Región', 'Tierra', 'Tundra', 'Villa', 'Zanja'],
                ['Abandonada', 'Abatida', 'Afligida', 'Agreste', 'Agrietada', 'Aislada', 'Alegre', 'Alta', 'Anticuada', 'Antigua', 'Apagada', 'Arcaica', 'Ardientes', 'Austera', 'Baja', 'Bárbara', 'Caduca', 'Caliente', 'Celestial', 'Chica', 'Colérica', 'Condenada', 'Contaminada', 'Corrupta', 'Crítica', 'Deplorable', 'Deprimente', 'Desafortunada', 'Desagradable', 'Desamparada', 'Desaparecida', 'Desastrosa', 'Desdichada', 'Desequilibrada', 'Desgraciada', 'Desierta', 'Desolada', 'Despiadada', 'Despótica', 'Detestable', 'Dichosa', 'Dirigista', 'Dura', 'Enterrada', 'Escondida', 'Extraña', 'Festiva', 'Fría', 'Funesta', 'Fúnebre', 'Gangrenada', 'Grande', 'Graves', 'Grotesca', 'Hedionda', 'Implacable', 'Infame', 'Infectada', 'Inhumana', 'Inmunda', 'Insípida', 'Iracunda', 'Jocosa', 'Lamentable', 'Loca', 'Lúgubre', 'Macabra', 'Mala', 'Maldita', 'Maligno', 'Mediocre', 'Melancólica', 'Mohosa', 'Morada', 'Muerta', 'Mugrosa', 'Mustia', 'Nefasta', 'Negra', 'Nostálgica', 'Nueva', 'Oscura', 'Paralizada', 'Patética', 'Perdida', 'Perversa', 'Pesarosa', 'Protegidas', 'Próspera', 'Psicótica', 'Pálida', 'Pútrida', 'Radiante', 'Rancia', 'Reaccionaria', 'Redonda', 'Remota', 'Repugnante', 'Retrógrada', 'Ridícula', 'Risueña', 'Roja', 'Ruda', 'Salvaje', 'Sangrienta', 'Sanguinaria', 'Seca', 'Sepultada', 'Siniestra', 'Sucia', 'Sórdida', 'Taciturna', 'Telúrica', 'Tenebrosa', 'Tensa', 'Tormentosa', 'Totalitaria', 'Triste', 'Trágica', 'Tétrica', 'Venturosa', 'Villana', 'Violenta', 'Árida', 'Áspera'],
                ['del Mago', 'de los Pasos Perdidos', 'del Oriente', 'del Crimen Impune', 'de la Luna', 'del Gran Poder', 'del Chiste Malo', 'de la Paz', 'de Mil Lágrimas', 'de la Ira', 'del Río Seco', 'del Suspiro', 'de la Brisa Fétida', 'de los Lamentos', 'de la Hermandad', 'del Ecuador', 'del Optimismo', 'del Sadismo', 'de la Justicia', 'de los Descosidos', 'de los Guardianes', 'del Héroe Olvidado', 'de la Sonrisa Enigmática', 'de Virgo', 'del Ceño Fruncido', 'de Sagitario', 'de lo Perverso', 'de los Milagros', 'de Orión', 'de Angustia', 'de la Esperanza', 'de Traición', 'del Ave Mensajera', 'del Este', 'de Géminis', 'del Instinto Carnal', 'del Miedo', 'del Espectro Verde', 'de la Patagonia', 'del Rey Cuervo', 'de Carroñeros', 'del Hard Rock', 'del Buen Tiempo', 'del Nosequé', 'de los Eclipses', 'de Paita', 'del Quinto Elemento', 'del Cuervo Loco', 'de lo Desconocido', 'de Nosedonde', 'del Silencio', 'del Pequeño Saltamontes', 'de San Mateo', 'de Aries', 'de Tabernas', 'de Guajira', 'del Otoño', 'del Queseyó', 'del Sueño Inspirador', 'de los Descalzos', 'del Olvido', 'de Baja California', 'de los Bipolares', 'de los Tuertos', 'del Antídoto', 'de la Tierra del Fuego', 'del Gran Guía', 'de los Topos', 'de los Renegados', 'de la Esmeralda', 'del Deseo Inútil', 'de Teneré', 'de Hendrix', 'del Troll Fantasma', 'del Ingenio', 'de Lágrimas Ajenas', 'de Murakami', 'de la Astucia', 'del Maestro Shaolín', 'del Oro Azul', 'del Grito Ahogado', 'de la Piel Cobriza', 'del Cojo', 'de la Verdad Absoluta', 'del Sur', 'de los Mártires', 'del Alma Pura', 'del Punto Rojo', 'de la Legión Púrpura', 'del Hambre', 'de los Caporales', 'de la Independencia', 'del Único Camino', 'del Castigo', 'del Espasmo', 'Sin Fin', 'de la Nada', 'de Carne Débil', 'del Dolor', 'de los Desaparecidos', 'del Socorro', 'de la Envidia', 'de la Aurora', 'del Yonofuí', 'del Norte', 'de Deepnight', 'de San Pedro', 'del Arquitecto Orate', 'del Insomnio', 'de Corazones', 'del Caballo Blanco', 'del Por Qué', 'del Pez Rabioso', 'de la Caridad', 'de los Ancestros Mayas', 'de los Ídolos', 'de Ficachi', 'del Ojo Ciego', 'de Tripas Secas', 'de la Juventud', 'de Atacama', 'de la Gran Mentira', 'del Humo Negro', 'de San Quintín', 'del Pensamiento', 'de Babasónicos', 'de los Brazos Cruzados', 'de la Redención', 'de la Avaricia', 'de Changó', 'de Monegros', 'de la Amargura', 'del Veneno Vil', 'del Pesimismo', 'del Pacífico', 'del Delirium Tremens', 'del Calambre', 'del Sarcasmo', 'de la Razón', 'de San Pablo', 'de la Utopía', 'de la Lluvia Perpetua', 'del Amor Interesado', 'de Ensueño', 'del Loco', 'de la Cachetada', 'de Giar', 'de Binto', 'de la Injusticia', 'de la Santería', 'de Nadie', 'de Zombiepolares', 'del Guante Caído', 'de Kalahari', 'de la Tranquilidad', 'de Tauro', 'de Kraterfire', 'de la Risa Contagiante', 'del Colmo', 'de la Alegría', 'de Sechura', 'de Malkev', 'del Ojo Triste', 'de Capricornio', 'de Sabiduría', 'del Poeta Solitario', 'de Sonora', 'de la Epidemia', 'del Embrujo', 'de los Temblores', 'de H4RO', 'del Mal Menor', 'de Bossu', 'del Oeste', 'del Díscolo', 'del Terrible Susto', 'del Camino Sinuoso', 'de la Carcajada', 'del Gran Jefe', 'del Pasado Brillante', 'del Nunca Jamás', 'de la Gula', 'de los Últimos Héroes', 'del Futuro Incierto', 'de los Babasónicos', 'de AlancapoII', 'del Cada Vez Peor', 'del Profeta', 'de Trendy', 'de Znarf', 'de Badenas Reales', 'de los Condenados', 'Sin Mañana', 'de Don Ramón', 'de Judas', 'del Morro Solar', 'de Fuego', 'del Sentido Común', 'de Paracas', 'del Primer Instinto', 'de Carroña', 'del Apocalipsis', 'de los 80', 'de la Luz', 'del Fin del Mundo', 'de Snow', 'del Cielo Prometido', 'de Quiensabequé', 'de la Soberbia', 'de Nazca', 'de Aldesa', 'de Sangre Caliente', 'de Amasijador', 'de la Peste Negra', 'de Invierno', 'del Pudor', 'de Shini', 'de la Serpiente Sin Fondo', 'de Mentira', 'de 163gal', 'de Primavera']
            ],
            [
                // Masculino plural
                ['Aires', 'Barracones', 'Burgos', 'Cerros', 'Cuchitriles', 'Horizontes', 'Jardines', 'Jirones', 'Llanos', 'Límites', 'Muros', 'Rumores', 'Senderos', 'Suburbios'],
                ['Abandonados', 'Abatidos', 'Afligidos', 'Agrestes', 'Agrietados', 'Aislados', 'Alegres', 'Altos', 'Anticuados', 'Antiguos', 'Apagados', 'Arcaicos', 'Ardientes', 'Austeros', 'Bajos', 'Bárbaros', 'Caducos', 'Calientes', 'Celestiales', 'Chicos', 'Coléricos', 'Condenados', 'Contaminados', 'Corruptos', 'Críticos', 'Deplorables', 'Deprimentes', 'Desafortunados', 'Desagradables', 'Desamparados', 'Desaparecidos', 'Desastrosos', 'Desdichados', 'Desequilibrados', 'Desgraciados', 'Desiertos', 'Desolados', 'Despiadados', 'Despóticos', 'Detestables', 'Dichosos', 'Dirigistas', 'Duros', 'Enterrados', 'Escondidos', 'Extraños', 'Festivos', 'Fríos', 'Funestos', 'Fúnebres', 'Gangrenados', 'Grandes', 'Graves', 'Grotescos', 'Hediondos', 'Implacables', 'Infames', 'Infectados', 'Inhumanos', 'Inmundos', 'Insípidos', 'Iracundos', 'Jocosos', 'Lamentables', 'Locos', 'Lúgubres', 'Macabros', 'Malditos', 'Malignos', 'Malos', 'Mediocres', 'Melancólicos', 'Mohosos', 'Morados', 'Muertos', 'Mugrosos', 'Mustios', 'Nefastos', 'Negros', 'Nostálgicos', 'Nuevos', 'Oscuros', 'Paralizados', 'Patéticos', 'Perdidos', 'Perversos', 'Pesarosos', 'Protegidos', 'Prósperos', 'Psicóticos', 'Pálidos', 'Pútridos', 'Radiantes', 'Rancios', 'Reaccionarios', 'Redondos', 'Remotos', 'Repugnantes', 'Retrógrados', 'Ridículos', 'Risueños', 'Rojos', 'Rudos', 'Salvajes', 'Sangrientos', 'Sanguinarios', 'Secos', 'Sepultados', 'Siniestros', 'Sucios', 'Sórdidos', 'Taciturnos', 'Telúricos', 'Tenebrosos', 'Tensos', 'Tormentosos', 'Totalitarios', 'Tristes', 'Trágicos', 'Tétricos', 'Venturosos', 'Villanos', 'Violentos', 'Áridos', 'Ásperos'],
                ['del Mago', 'de los Pasos Perdidos', 'del Oriente', 'del Crimen Impune', 'de la Luna', 'del Gran Poder', 'del Chiste Malo', 'de la Paz', 'de Mil Lágrimas', 'de la Ira', 'del Río Seco', 'del Suspiro', 'de la Brisa Fétida', 'de los Lamentos', 'de la Hermandad', 'del Ecuador', 'del Optimismo', 'del Sadismo', 'de la Justicia', 'de los Descosidos', 'de los Guardianes', 'del Héroe Olvidado', 'de la Sonrisa Enigmática', 'de Virgo', 'del Ceño Fruncido', 'de Sagitario', 'de lo Perverso', 'de los Milagros', 'de Orión', 'de Angustia', 'de la Esperanza', 'de Traición', 'del Ave Mensajera', 'del Este', 'de Géminis', 'del Instinto Carnal', 'del Miedo', 'del Espectro Verde', 'de la Patagonia', 'del Rey Cuervo', 'de Carroñeros', 'del Hard Rock', 'del Buen Tiempo', 'del Nosequé', 'de los Eclipses', 'de Paita', 'del Quinto Elemento', 'del Cuervo Loco', 'de lo Desconocido', 'de Nosedonde', 'del Silencio', 'del Pequeño Saltamontes', 'de San Mateo', 'de Aries', 'de Tabernas', 'de Guajira', 'del Otoño', 'del Queseyó', 'del Sueño Inspirador', 'de los Descalzos', 'del Olvido', 'de Baja California', 'de los Bipolares', 'de los Tuertos', 'del Antídoto', 'de la Tierra del Fuego', 'del Gran Guía', 'de los Topos', 'de los Renegados', 'de la Esmeralda', 'del Deseo Inútil', 'de Teneré', 'de Hendrix', 'del Troll Fantasma', 'del Ingenio', 'de Lágrimas Ajenas', 'de Murakami', 'de la Astucia', 'del Maestro Shaolín', 'del Oro Azul', 'del Grito Ahogado', 'de la Piel Cobriza', 'del Cojo', 'de la Verdad Absoluta', 'del Sur', 'de los Mártires', 'del Alma Pura', 'del Punto Rojo', 'de la Legión Púrpura', 'del Hambre', 'de los Caporales', 'de la Independencia', 'del Único Camino', 'del Castigo', 'del Espasmo', 'Sin Fin', 'de la Nada', 'de Carne Débil', 'del Dolor', 'de los Desaparecidos', 'del Socorro', 'de la Envidia', 'de la Aurora', 'del Yonofuí', 'del Norte', 'de Deepnight', 'de San Pedro', 'del Arquitecto Orate', 'del Insomnio', 'de Corazones', 'del Caballo Blanco', 'del Por Qué', 'del Pez Rabioso', 'de la Caridad', 'de los Ancestros Mayas', 'de los Ídolos', 'de Ficachi', 'del Ojo Ciego', 'de Tripas Secas', 'de la Juventud', 'de Atacama', 'de la Gran Mentira', 'del Humo Negro', 'de San Quintín', 'del Pensamiento', 'de Babasónicos', 'de los Brazos Cruzados', 'de la Redención', 'de la Avaricia', 'de Changó', 'de Monegros', 'de la Amargura', 'del Veneno Vil', 'del Pesimismo', 'del Pacífico', 'del Delirium Tremens', 'del Calambre', 'del Sarcasmo', 'de la Razón', 'de San Pablo', 'de la Utopía', 'de la Lluvia Perpetua', 'del Amor Interesado', 'de Ensueño', 'del Loco', 'de la Cachetada', 'de Giar', 'de Binto', 'de la Injusticia', 'de la Santería', 'de Nadie', 'de Zombiepolares', 'del Guante Caído', 'de Kalahari', 'de la Tranquilidad', 'de Tauro', 'de Kraterfire', 'de la Risa Contagiante', 'del Colmo', 'de la Alegría', 'de Sechura', 'de Malkev', 'del Ojo Triste', 'de Capricornio', 'de Sabiduría', 'del Poeta Solitario', 'de Sonora', 'de la Epidemia', 'del Embrujo', 'de los Temblores', 'de H4RO', 'del Mal Menor', 'de Bossu', 'del Oeste', 'del Díscolo', 'del Terrible Susto', 'del Camino Sinuoso', 'de la Carcajada', 'del Gran Jefe', 'del Pasado Brillante', 'del Nunca Jamás', 'de la Gula', 'de los Últimos Héroes', 'del Futuro Incierto', 'de los Babasónicos', 'de AlancapoII', 'del Cada Vez Peor', 'del Profeta', 'de Trendy', 'de Znarf', 'de Badenas Reales', 'de los Condenados', 'Sin Mañana', 'de Don Ramón', 'de Judas', 'del Morro Solar', 'de Fuego', 'del Sentido Común', 'de Paracas', 'del Primer Instinto', 'de Carroña', 'del Apocalipsis', 'de los 80', 'de la Luz', 'del Fin del Mundo', 'de Snow', 'del Cielo Prometido', 'de Quiensabequé', 'de la Soberbia', 'de Nazca', 'de Aldesa', 'de Sangre Caliente', 'de Amasijador', 'de la Peste Negra', 'de Invierno', 'del Pudor', 'de Shini', 'de la Serpiente Sin Fondo', 'de Mentira', 'de 163gal', 'de Primavera']
            ],
            [
                // Femenino plural
                ['Arenas', 'Calles', 'Corrientes', 'Cruzadas', 'Cuadras', 'Estepas', 'Fuentes', 'Grietas', 'Llanuras', 'Pocilgas', 'Praderas', 'Tierras', 'Tumbas'],
                ['Abandonadas', 'Abatidas', 'Afligidas', 'Agrestes', 'Agrietadas', 'Aisladas', 'Alegres', 'Altas', 'Anticuadas', 'Antiguas', 'Apagadas', 'Arcaicas', 'Ardientes', 'Austeras', 'Bajas', 'Bárbaras', 'Caducas', 'Calientes', 'Celestiales', 'Chicas', 'Coléricas', 'Condenadas', 'Contaminadas', 'Corruptas', 'Críticas', 'Deplorables', 'Deprimentes', 'Desafortunadas', 'Desagradables', 'Desamparadas', 'Desaparecidas', 'Desastrosas', 'Desdichadas', 'Desequilibradas', 'Desgraciadas', 'Desiertas', 'Desoladas', 'Despiadadas', 'Despóticas', 'Detestables', 'Dichosas', 'Dirigistas', 'Duras', 'Enterradas', 'Escondidas', 'Extrañas', 'Festivas', 'Frías', 'Funestas', 'Fúnebres', 'Gangrenada', 'Grandes', 'Graves', 'Grotescas', 'Hediondas', 'Implacables', 'Infames', 'Infectadas', 'Inhumanas', 'Inmundas', 'Insípidas', 'Iracundas', 'Jocosas', 'Lamentables', 'Locas', 'Lúgubres', 'Macabras', 'Malas', 'Malditas', 'Malignos', 'Mediocre', 'Melancólicas', 'Mohosas', 'Moradas', 'Muertas', 'Mugrosas', 'Mustias', 'Nefastas', 'Negras', 'Nostálgicas', 'Nuevas', 'Oscuras', 'Paralizadas', 'Patéticas', 'Perdidas', 'Perversas', 'Pesarosas', 'Protegidas', 'Prósperas', 'Psicóticas', 'Pálidas', 'Pútridas', 'Radiantes', 'Rancias', 'Reaccionarias', 'Redondas', 'Remotas', 'Repugnantes', 'Retrógradas', 'Ridículas', 'Risueñas', 'Rojas', 'Rudas', 'Salvajes', 'Sangrientas', 'Sanguinarias', 'Secas', 'Sepultadas', 'Siniestras', 'Sucias', 'Sórdidas', 'Taciturnas', 'Telúricas', 'Tenebrosas', 'Tensas', 'Tormentosas', 'Totalitarias', 'Tristes', 'Trágicas', 'Tétricas', 'Venturosas', 'Villanas', 'Violentas', 'Áridas', 'Ásperas'],
                ['del Mago', 'de los Pasos Perdidos', 'del Oriente', 'del Crimen Impune', 'de la Luna', 'del Gran Poder', 'del Chiste Malo', 'de la Paz', 'de Mil Lágrimas', 'de la Ira', 'del Río Seco', 'del Suspiro', 'de la Brisa Fétida', 'de los Lamentos', 'de la Hermandad', 'del Ecuador', 'del Optimismo', 'del Sadismo', 'de la Justicia', 'de los Descosidos', 'de los Guardianes', 'del Héroe Olvidado', 'de la Sonrisa Enigmática', 'de Virgo', 'del Ceño Fruncido', 'de Sagitario', 'de lo Perverso', 'de los Milagros', 'de Orión', 'de Angustia', 'de la Esperanza', 'de Traición', 'del Ave Mensajera', 'del Este', 'de Géminis', 'del Instinto Carnal', 'del Miedo', 'del Espectro Verde', 'de la Patagonia', 'del Rey Cuervo', 'de Carroñeros', 'del Hard Rock', 'del Buen Tiempo', 'del Nosequé', 'de los Eclipses', 'de Paita', 'del Quinto Elemento', 'del Cuervo Loco', 'de lo Desconocido', 'de Nosedonde', 'del Silencio', 'del Pequeño Saltamontes', 'de San Mateo', 'de Aries', 'de Tabernas', 'de Guajira', 'del Otoño', 'del Queseyó', 'del Sueño Inspirador', 'de los Descalzos', 'del Olvido', 'de Baja California', 'de los Bipolares', 'de los Tuertos', 'del Antídoto', 'de la Tierra del Fuego', 'del Gran Guía', 'de los Topos', 'de los Renegados', 'de la Esmeralda', 'del Deseo Inútil', 'de Teneré', 'de Hendrix', 'del Troll Fantasma', 'del Ingenio', 'de Lágrimas Ajenas', 'de Murakami', 'de la Astucia', 'del Maestro Shaolín', 'del Oro Azul', 'del Grito Ahogado', 'de la Piel Cobriza', 'del Cojo', 'de la Verdad Absoluta', 'del Sur', 'de los Mártires', 'del Alma Pura', 'del Punto Rojo', 'de la Legión Púrpura', 'del Hambre', 'de los Caporales', 'de la Independencia', 'del Único Camino', 'del Castigo', 'del Espasmo', 'Sin Fin', 'de la Nada', 'de Carne Débil', 'del Dolor', 'de los Desaparecidos', 'del Socorro', 'de la Envidia', 'de la Aurora', 'del Yonofuí', 'del Norte', 'de Deepnight', 'de San Pedro', 'del Arquitecto Orate', 'del Insomnio', 'de Corazones', 'del Caballo Blanco', 'del Por Qué', 'del Pez Rabioso', 'de la Caridad', 'de los Ancestros Mayas', 'de los Ídolos', 'de Ficachi', 'del Ojo Ciego', 'de Tripas Secas', 'de la Juventud', 'de Atacama', 'de la Gran Mentira', 'del Humo Negro', 'de San Quintín', 'del Pensamiento', 'de Babasónicos', 'de los Brazos Cruzados', 'de la Redención', 'de la Avaricia', 'de Changó', 'de Monegros', 'de la Amargura', 'del Veneno Vil', 'del Pesimismo', 'del Pacífico', 'del Delirium Tremens', 'del Calambre', 'del Sarcasmo', 'de la Razón', 'de San Pablo', 'de la Utopía', 'de la Lluvia Perpetua', 'del Amor Interesado', 'de Ensueño', 'del Loco', 'de la Cachetada', 'de Giar', 'de Binto', 'de la Injusticia', 'de la Santería', 'de Nadie', 'de Zombiepolares', 'del Guante Caído', 'de Kalahari', 'de la Tranquilidad', 'de Tauro', 'de Kraterfire', 'de la Risa Contagiante', 'del Colmo', 'de la Alegría', 'de Sechura', 'de Malkev', 'del Ojo Triste', 'de Capricornio', 'de Sabiduría', 'del Poeta Solitario', 'de Sonora', 'de la Epidemia', 'del Embrujo', 'de los Temblores', 'de H4RO', 'del Mal Menor', 'de Bossu', 'del Oeste', 'del Díscolo', 'del Terrible Susto', 'del Camino Sinuoso', 'de la Carcajada', 'del Gran Jefe', 'del Pasado Brillante', 'del Nunca Jamás', 'de la Gula', 'de los Últimos Héroes', 'del Futuro Incierto', 'de los Babasónicos', 'de AlancapoII', 'del Cada Vez Peor', 'del Profeta', 'de Trendy', 'de Znarf', 'de Badenas Reales', 'de los Condenados', 'Sin Mañana', 'de Don Ramón', 'de Judas', 'del Morro Solar', 'de Fuego', 'del Sentido Común', 'de Paracas', 'del Primer Instinto', 'de Carroña', 'del Apocalipsis', 'de los 80', 'de la Luz', 'del Fin del Mundo', 'de Snow', 'del Cielo Prometido', 'de Quiensabequé', 'de la Soberbia', 'de Nazca', 'de Aldesa', 'de Sangre Caliente', 'de Amasijador', 'de la Peste Negra', 'de Invierno', 'del Pudor', 'de Shini', 'de la Serpiente Sin Fondo', 'de Mentira', 'de 163gal', 'de Primavera']
            ]
        ],
    ];

    public function createTownName($language): string {
        $langList = array_keys(static::$town_name_snippets);
        if($language == 'multi') {
            $key = array_rand($langList);
            $language = $langList[$key];
        }

        if(!isset(static::$town_name_snippets[$language]))
            $language = 'de';

        if ($language === 'es' && $this->random_generator->chance(0.8)) {
            // For spanish names, we don't want to use all 3 components in 80% of the cases
            // This handler will build A + B and A + C instead of A + B + C
            $base =  static::$town_name_snippets[$language][array_rand( static::$town_name_snippets[$language] )];
            $name = $this->random_generator->chance(0.5)
                ? $this->random_generator->pick( $base[0] ) . ' ' . $this->random_generator->pick( $base[1] )
                : $this->random_generator->pick( $base[0] ) . ' ' . $this->random_generator->pick( $base[2] )
            ;
        } else
            $name = implode(' ', array_map(function(array $list): string {
                return $list[ array_rand( $list ) ];
            }, static::$town_name_snippets[$language][array_rand( static::$town_name_snippets[$language] )]));
        return $name;
    }

    private function getDefaultZoneResolution( TownConf $conf, ?int &$offset_x, ?int &$offset_y ): int {
        $resolution = mt_rand( $conf->get(TownConf::CONF_MAP_MIN, 0), $conf->get(TownConf::CONF_MAP_MAX, 0) );
        $safe_border = ceil($resolution * $conf->get(TownConf::CONF_MAP_MARGIN, 0.25));

        if ($safe_border >= $resolution/2) {
            $offset_x = mt_rand(floor(($resolution-1)/2), ceil(($resolution-1)/2));
            $offset_y = mt_rand(floor(($resolution-1)/2), ceil(($resolution-1)/2));
        } else {
            $offset_x = $safe_border + mt_rand(0, max(0,$resolution - 2*$safe_border));
            $offset_y = $safe_border + mt_rand(0, max(0,$resolution - 2*$safe_border));
        }

        return $resolution;
    }

    public function createTown( ?string $name, ?string $language, ?int $population, string $type, $customConf = [], int $seed = -1 ): ?Town {
        if (!$this->validator->validateTownType($type))
            return null;

        if ($seed > 0)
            mt_srand($seed);

        $townClass = $this->entity_manager->getRepository(TownClass::class)->findOneBy([ 'name' => $type ]);

        // Initial: Create town
        $town = new Town();
        $town
            ->setType($townClass)
            ->setConf($customConf);

        $currentSeason = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);

        $town->setSeason($currentSeason);

        $conf = $this->conf->getTownConfiguration($town);

        if ($population === null) $population = mt_rand( $conf->get(TownConf::CONF_POPULATION_MIN, 0), $conf->get(TownConf::CONF_POPULATION_MAX, 0) );
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
            if (!in_array($prototype->getName(), $conf->get(TownConf::CONF_DISABLED_BUILDINGS))) {
                $this->town_handler->addBuilding($town, $prototype);
                $this->gps->recordBuildingDiscovered( $prototype, $town, null, 'always' );
            }

        $buildings_to_unlock = array_unique( array_merge( $conf->get(TownConf::CONF_BUILDINGS_UNLOCKED), $conf->get(TownConf::CONF_BUILDINGS_CONSTRUCTED) ) );
        $failed_unlocks = $last_failed_unlocks = 0;
        do {
            $last_failed_unlocks = $failed_unlocks;
            $failed_unlocks = 0;
            foreach ($buildings_to_unlock as $str_prototype)
                if (!in_array($str_prototype, $conf->get(TownConf::CONF_DISABLED_BUILDINGS))) {
                    $prototype = $this->entity_manager->getRepository(BuildingPrototype::class)->findOneBy(['name' => $str_prototype]);
                    if ($prototype) {
                        if ($this->town_handler->addBuilding($town, $prototype))
                            $this->gps->recordBuildingDiscovered( $prototype, $town, null, 'config' );
                        else $failed_unlocks++;
                    }
                }
        } while ($failed_unlocks > 0 && $failed_unlocks !== $last_failed_unlocks);


        foreach ($conf->get(TownConf::CONF_BUILDINGS_CONSTRUCTED) as $str_prototype) {
            if (in_array($str_prototype, $conf->get(TownConf::CONF_DISABLED_BUILDINGS)))
                continue;

            /** @var BuildingPrototype $proto */
            $proto = $this->entity_manager->getRepository(BuildingPrototype::class)->findOneBy( ['name' => $str_prototype] );
            $b = $this->town_handler->addBuilding( $town, $proto );
            $b->setAp( $proto->getAp() )->setComplete( true )->setHp($proto->getHp());
            $this->gps->recordBuildingConstructed( $proto, $town, null, 'config' );
        }

        $this->town_handler->calculate_zombie_attacks( $town, 3 );

        $defaultTag = $this->entity_manager->getRepository(ZoneTag::class)->findOneBy(['ref' => ZoneTag::TagNone]);

        $map_resolution = $this->getDefaultZoneResolution( $conf, $ox, $oy );
        for ($x = 0; $x < $map_resolution; $x++)
            for ($y = 0; $y < $map_resolution; $y++) {
                $zone = new Zone();
                $zone
                    ->setX( $x - $ox )
                    ->setY( $y - $oy )
                    ->setDigs( mt_rand( $conf->get(TownConf::CONF_ZONE_ITEMS_MIN, 4), $conf->get(TownConf::CONF_ZONE_ITEMS_MAX, 10) ) )
                    ->setFloor( new Inventory() )
                    ->setDiscoveryStatus( ($x - $ox == 0 && $y - $oy == 0) ? Zone::DiscoveryStateCurrent : Zone::DiscoveryStateNone )
                    ->setZombieStatus( ($x - $ox == 0 && $y - $oy == 0) ? Zone::ZombieStateExact : Zone::ZombieStateUnknown )
                    ->setZombies( 0 )
                    ->setInitialZombies( 0 )
                    ->setStartZombies( 0 )
                    ->setTag($defaultTag)
                ;
                $town->addZone( $zone );
            }

        $spawn_ruins = $conf->get(TownConf::CONF_NUM_RUINS, 0);

        $ruin_km_range = [
            $this->entity_manager->getRepository(ZonePrototype::class)->findMinRuinDistance(false),
            $this->entity_manager->getRepository(ZonePrototype::class)->findMaxRuinDistance(false),
        ];

        /** @var Zone[] $zone_list */
        $zone_list = array_filter($town->getZones()->getValues(), function(Zone $z) use ($ruin_km_range) {
            $km = round(sqrt( pow($z->getX(),2) + pow($z->getY(),2) ) );
            // $ap = abs($z->getX()) + abs($z->getY());
            return $km != 0 && $km >= $ruin_km_range[0] && $km <= $ruin_km_range[1];
        });
        shuffle($zone_list);

        $previous = [];

        $co_location_cache = [];
        $cl_get = function(int $x, int $y) use (&$co_location_cache): int {
            $m = 0;
            for ($xo = -1; $xo <= 1; $xo++) for ($yo = -1; $yo <= 1; $yo++)
                if (isset($co_location_cache[$id = (($x+$xo) . '.' . ($y+$yo))]))
                    $m = max($m, count($co_location_cache[$id]));
            return $m;
        };
        $cl_set = function(int $x, int $y) use (&$co_location_cache): void {
            $a = [$x . '.' . $y];
            for ($xo = -1; $xo <= 1; $xo++) for ($yo = -1; $yo <= 1; $yo++)
                if (isset($co_location_cache[$id = (($x+$xo) . '.' . ($y+$yo))]))
                    $a = array_merge($a,$co_location_cache[$id]);
            $a = array_unique($a);
            foreach ($a as $id) $co_location_cache[$id] = $a;
        };

        $o = 0;
        for ($i = 0; $i < $spawn_ruins+2; $i++) {

            $zombies_base = 0;
            do {
                if (($i+$o) >= count($zone_list)) continue 2;
                $b = $cl_get( $zone_list[$i+$o]->getX(), $zone_list[$i+$o]->getY() );
                if ($b <= 1) $keep_location = true;
                else if ($b === 2) $keep_location = $this->random_generator->chance(0.25);
                else $keep_location = false;

                if (!$keep_location) $o++;
            } while ( !$keep_location );

            $cl_set( $zone_list[$i+$o]->getX(), $zone_list[$i+$o]->getY() );

            if ($i < $spawn_ruins) {

                $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i+$o]->getX(),2) + pow($zone_list[$i+$o]->getY(),2) )/18) * 18);

                //$ruin_types = $this->entity_manager->getRepository(ZonePrototype::class)->findByDistance( abs($zone_list[$i]->getX()) + abs($zone_list[$i]->getY()) );
                $ruin_types = $this->entity_manager->getRepository(ZonePrototype::class)->findByDistance(round(sqrt( pow($zone_list[$i+$o]->getX(),2) + pow($zone_list[$i+$o]->getY(),2) )));
                if (empty($ruin_types)) continue;

                $iterations = 0;
                do {
                    $target_ruin = $this->random_generator->pickLocationFromList( $ruin_types );
                    $iterations++;
                } while ( isset( $previous[$target_ruin->getId()] ) && $iterations <= $previous[$target_ruin->getId()] );

                if (!isset( $previous[$target_ruin->getId()] )) $previous[$target_ruin->getId()] = 1;
                else $previous[$target_ruin->getId()]++;

                $zone_list[$i+$o]->setPrototype( $target_ruin );
                if ($conf->get(TownConf::CONF_FEATURE_CAMPING, false))
                    $zone_list[$i+$o]->setBlueprint(Zone::BlueprintAvailable);

                if ($this->random_generator->chance(0.4)) $zone_list[$i+$o]->setBuryCount( mt_rand(6, 20) );
            } else
                if ($this->random_generator->chance(0.1))
                    $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i+$o]->getX(),2) + pow($zone_list[$i+$o]->getY(),2) )/18) * 3);

            if ($zombies_base > 0) {
                $zombies_base = max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) );
                $zone_list[$i+$o]->setZombies( $zombies_base )->setInitialZombies( $zombies_base );
            }
        }

        $spawn_explorable_ruins = $conf->get(TownConf::CONF_NUM_EXPLORABLE_RUINS, 0);
        $all_explorable_ruins = $explorable_ruins = [];
        if ($spawn_explorable_ruins > 0)
            $all_explorable_ruins = $this->entity_manager->getRepository(ZonePrototype::class)->findBy( ['explorable' => true] );
            $zone_list = array_filter($town->getZones()->getValues(), function(Zone $z) {return $z->getPrototype() === null && ($z->getX() !== 0 || $z->getY() !== 0);});

        for ($i = 0; $i < $spawn_explorable_ruins; $i++) {
            if (empty($explorable_ruins)) {
                $explorable_ruins = $all_explorable_ruins;
                shuffle($explorable_ruins);
            }

            /** @var ZonePrototype $spawning_ruin */
            $spawning_ruin = array_pop($explorable_ruins);
            if (!$spawning_ruin) continue;

            $maxDistance = $conf->get(TownConf::CONF_EXPLORABLES_MAX_DISTANCE, 100);
            $spawn_zone = $this->random_generator->pickLocationBetweenFromList($zone_list, $spawning_ruin->getMinDistance(), $maxDistance, ['prototype_id' => null]);

            if ($spawn_zone) {
                $spawn_zone->setPrototype($spawning_ruin);
                $this->maze_maker->createField( $spawn_zone );
                $this->maze_maker->generateMaze( $spawn_zone );

                $zombies_base = 1 + floor(min(1,sqrt( pow($spawn_zone->getX(),2) + pow($spawn_zone->getY(),2) )/18) * 3);
                $zombies_base = max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) );
                $spawn_zone->setZombies( $zombies_base )->setInitialZombies( $zombies_base );
            }
        }

        $item_spawns = $conf->get(TownConf::CONF_DISTRIBUTED_ITEMS, []);
        $distribution = [];

        $zone_list = $town->getZones()->getValues();
        foreach ($conf->get(TownConf::CONF_DISTRIBUTION_DISTANCE, []) as $dd) {
            $distribution[$dd['item']] = ['min' => $dd['min'], 'max' => $dd['max']];
        }
        for ($i = 0; $i < count($item_spawns); $i++) {
            $item = $item_spawns[$i];
            if (isset($distribution[$item])) {
                $min_distance = $distribution[$item]['min'];
                $max_distance = $distribution[$item]['max'];
            }
            else {
                $min_distance = 1;
                $max_distance = 100;
            }

            $spawnZone = $this->random_generator->pickLocationBetweenFromList($zone_list, $min_distance, $max_distance);
            if ($spawnZone) {
                $this->inventory_handler->forceMoveItem($spawnZone->getFloor(), $this->item_factory->createItem($item_spawns[$i]));
                $zone_list = array_filter( $zone_list, fn(Zone $z) => $z !== $spawnZone );
            }
        }

        $this->zone_handler->dailyZombieSpawn( $town, 1, ZoneHandler::RespawnModeNone );
        foreach ($town->getZones() as $zone) $zone->setStartZombies( $zone->getZombies() );

        $town->setForum((new Forum())->setTitle($town->getName()));
        foreach ($this->entity_manager->getRepository(ThreadTag::class)->findBy(['name' => ['help','rp','event','dsc_disc','dsc_guide','dsc_orga']]) as $tag)
            $town->getForum()->addAllowedTag($tag);

        $this->crow->postToForum( $town->getForum(),
            [
                T::__('In diesem Thread dreht sich alles um die Bank.', 'game'),
                T::__('In diesem Thread dreht sich alles um die geplanten Verbesserungen des Tages.', 'game'),
                T::__('In diesem Thread dreht sich alles um die Werkstatt und um Ressourcen.', 'game'),
                T::__('In diesem Thread dreht sich alles um zukünftige Bauprojekte.', 'game'),
            ],
            true, true,
            [
                T::__('Bank', 'game'),
                T::__('Verbesserung des Tages', 'game'),
                T::__('Werkstatt', 'game'),
                T::__('Konstruktionen', 'game')
            ],
            [
                Thread::SEMANTIC_BANK,
                Thread::SEMANTIC_DAILYVOTE,
                Thread::SEMANTIC_WORKSHOP,
                Thread::SEMANTIC_CONSTRUCTIONS
            ]
        );

        /** @var Gazette $gazette */
        $gazette = new Gazette();
        $gazette->setTown($town)->setDay($town->getDay());
        $town->addGazette($gazette);
        $this->entity_manager->persist($gazette);

        return $town;
    }

    public function userCanEnterTown( Town &$town, User &$user, bool $whitelist_enabled = false, ?int &$error = null, bool $internal = false ): bool {
        if (!$town->isOpen()) {
            $error = self::ErrorTownClosed;
            return false;
        }

        if (!$internal && $this->user_handler->getConsecutiveDeathLock($user)) {
            $error = ErrorHelper::ErrorPermissionError;
            return false;
        }

        if (!$internal && !$this->conf->getTownConfiguration( $town )->get( TownConf::CONF_FEATURE_NO_SP_REQUIRED )) {
            $conf = $this->conf->getGlobalConf();
            $sp = $this->user_handler->fetchSoulPoints($user);
            $allowed = false;
            switch ($town->getType()->getName()) {
                case 'small':
                    $allowed = ($sp < $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 ) || $sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 ));
                    break;
                case 'remote':
                    $allowed = $sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 );
                    break;
                case 'panda':
                    $allowed = $sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 );
                    break;
                case 'custom':
                    $allowed = $sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 );
                    break;
            }

            if (!$allowed && !$this->user_handler->checkFeatureUnlock( $user, 'f_sptkt', true )) {
                $error = ErrorHelper::ErrorPermissionError;
                return false;
            }
        }

        $whitelist = $whitelist_enabled ? $this->entity_manager->getRepository(TownSlotReservation::class)->findOneBy(['town' => $town, 'user' => $user]) : null;
        if ($whitelist_enabled && $whitelist === null && $user !== $town->getCreator()) {
            $error = ErrorHelper::ErrorPermissionError;
            return false;
        }

        if ($this->entity_manager->getRepository(Citizen::class)->findActiveByUser( $user ) !== null) {
            $error = self::ErrorUserAlreadyInGame;
            return false;
        }

        foreach ($town->getCitizens() as $existing_citizen)
            if ($existing_citizen->getUser() === $user) {
                $error = self::ErrorUserAlreadyInTown;
                return false;
            }

        return true;
    }

    public function createCitizen( Town &$town, User &$user, ?int &$error, ?array &$all_citizens = null, bool $internal = false ): ?Citizen {
        $error = self::ErrorNone;
        $lock = $this->locksmith->waitForLock('join-town');

        $whitelist_enabled = $this->entity_manager->getRepository(TownSlotReservation::class)->count(['town' => $town]) > 0;

        $followers = ($internal || $town->getPassword() || $whitelist_enabled) ? [] : $this->user_handler->getAvailableCoalitionMembers( $user );

        if (!$this->userCanEnterTown($town,$user, $whitelist_enabled,$error,$internal))
            return null;

        $followers = array_filter($followers, function (User $follower) use ($town,$whitelist_enabled,$internal): bool {
            return $this->userCanEnterTown($town,$follower,$whitelist_enabled,$e, $internal);
        });

        if (($town->getCitizenCount() + count($followers) + 1) > $town->getPopulation()) {
            $error = self::ErrorTownNoCoaRoom;
            return null;
        }

        $base_profession = $this->entity_manager->getRepository(CitizenProfession::class)->findDefault();
        if ($base_profession === null) {
            $error = self::ErrorNoDefaultProfession;
            return null;
        }

        $followers[] = $user;
        $main_citizen = null;
        $all_citizens = [];

        $town_group = $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $town->getId()] );

        $cx_clean_shoutbox_state = [];
        foreach ($followers as $joining_user)
            if ($sb = $this->user_handler->getShoutbox($joining_user)) {
                $last_entry = $this->entity_manager->getRepository(ShoutboxEntry::class)->findOneBy(['shoutbox' => $sb], ['timestamp' => 'DESC', 'id' => 'DESC']);
                if ($last_entry) {
                    $marker = $this->entity_manager->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $joining_user]);
                    if ($marker && $last_entry === $marker->getEntry()) $cx_clean_shoutbox_state[] = $joining_user;
                }
            }

        $entry_cache = [];
        foreach ($followers as $joining_user) {

            $home = new CitizenHome();
            $home
                ->setChest( $chest = new Inventory() )
                ->setPrototype( $this->entity_manager->getRepository( CitizenHomePrototype::class )->findOneBy(['level' => 0]) )
            ;

            $joining_user->addCitizen( $citizen = new Citizen() );
            $citizen->setUser( $joining_user )
                ->setTown( $town )
                ->setInventory( new Inventory() )
                ->setHome( $home )
                ->setCauseOfDeath( $this->entity_manager->getRepository( CauseOfDeath::class )->findOneBy( ['ref' => CauseOfDeath::Unknown] ) )
                ->setHasSeenGazette( true );

            // Check for other coalition members
            foreach ($this->user_handler->getAllOtherCoalitionMembers( $joining_user ) as $coa_member) {
                $coa_citizen = $coa_member->getCitizenFor($town);
                if ($coa_citizen) {
                    $this->entity_manager->persist( $coa_citizen->setCoalized(true) );
                    $citizen->setCoalized( true );
                }
            }

            (new Inventory())->setCitizen( $citizen );
            $this->citizen_handler->inflictStatus( $citizen, 'clean' );

            $this->citizen_handler->applyProfession( $citizen, $base_profession );

            $this->inventory_handler->forceMoveItem( $chest, $this->item_factory->createItem( 'chest_citizen_#00' ) );
            $this->inventory_handler->forceMoveItem( $chest, $this->item_factory->createItem( 'food_bag_#00' ) );

            // Adding default heroic action
            $heroic_actions = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findBy(['unlockable' => false]);
            foreach ($heroic_actions as $heroic_action)
                /** @var $heroic_action HeroicActionPrototype */
                $citizen->addHeroicAction( $heroic_action );

            if ($town_group) $this->perm->associate( $joining_user, $town_group );

            if ($joining_user === $user) $main_citizen = $citizen;
            $all_citizens[] = $citizen;
            $this->entity_manager->persist($citizen);

            /** @var Shoutbox|null $shoutbox */
            if ($shoutbox = $this->user_handler->getShoutbox($joining_user)) {
                $shoutbox->addEntry(
                    $entry_cache[$shoutbox->getId()] = (new ShoutboxEntry())
                        ->setType( ShoutboxEntry::SBEntryTypeTown )
                        ->setTimestamp( new \DateTime() )
                        ->setUser1( $joining_user )
                        ->setText( $town->getName() )
                );
                $this->entity_manager->persist($shoutbox);
            }
        }

        foreach ($cx_clean_shoutbox_state as $sb_clean_user)
            if ($sb = $this->user_handler->getShoutbox($sb_clean_user)) {

                if (isset($entry_cache[$sb->getId()])) {
                    /** @var ShoutboxReadMarker $marker */
                    $marker = $this->entity_manager->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $sb_clean_user]);
                    if ($marker)
                        $this->entity_manager->persist($marker->setEntry( $entry_cache[$sb->getId()] ));
                }
            }

        $whitelist = $whitelist_enabled ? $this->entity_manager->getRepository(TownSlotReservation::class)->findOneBy(['town' => $town, 'user' => $user]) : null;
        if ($whitelist !== null) $this->entity_manager->remove($whitelist);

        return $main_citizen;
    }

    /**
     * @param Town|TownRankingProxy $town
     * @return void
     */
    public function updateTownScore(TownRankingProxy|Town $town): void {
        $score = 0;
        $lastDay = 0;

        $tr = null;
        if (is_a( $town, Town::class )) $tr = $town->getRankingEntry();
        elseif (is_a( $town, TownRankingProxy::class )) $tr = $town;

        foreach ($tr->getCitizens() as $r_citizen) {
            /* @var CitizenRankingProxy $citizen */
            $score += $r_citizen->getDay();
            $lastDay = max( $lastDay, $r_citizen->getDay());
        }

        if (is_a( $town, Town::class )) $town->setDay( $lastDay );
        $this->entity_manager->persist( $tr->setDays($lastDay)->setScore($score) );
    }

    public function compactTown(Town $town): bool {

        foreach ($town->getCitizens() as $citizen) if ($citizen->getAlive()) return false;
        if ($town->isOpen() && !$town->getCitizens()->isEmpty()) return false;

        $this->updateTownScore($town);
        $this->gps->recordTownEnded($town);
        $this->entity_manager->remove($town);
        return true;
    }

    public function nullifyTown(Town $town, bool $force = false): bool {
        if ($town->isOpen() && !$force) return false;

        if ($town->getRankingEntry()) $this->entity_manager->remove($town->getRankingEntry());
        $this->entity_manager->remove($town);
        return true;
    }

    public function enableStranger(Town $town): bool {
        if (!$town->isOpen()) return false;

        $town->setStrangerPower( $town->getPopulation() - $town->getCitizenCount() );
        $town->setPopulation( $town->getCitizenCount() );
        $this->entity_manager->persist( $town );
        $this->entity_manager->persist( $this->log->strangerJoinProfession( $town, $this->timeKeeper->getCurrentAttackTime()->sub(DateInterval::createFromDateString('2min'))));
        return true;
    }
}
