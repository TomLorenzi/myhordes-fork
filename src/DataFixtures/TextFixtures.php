<?php

namespace App\DataFixtures;

use App\Entity\RolePlayText;
use App\Entity\RolePlayTextPage;
use App\Entity\TownClass;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class TextFixtures extends Fixture
{
    public static $texts = [
        'dv_000' => [
            'title' => 'Arztbescheinigung',
            'author' => 'Waganga',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_001' => [
            'title' => 'Auslosung',
            'author' => 'Stravingo',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_002' => [
            'title' => 'Befehl',
            'author' => 'Nobbz',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_003' => [
            'title' => 'Befehl',
            'author' => 'Nobbz',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_004' => [
            'title' => 'Bekanntmachung: Abtrünnige',
            'author' => 'Sigma',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_005' => [
            'title' => 'Bekanntmachung: Wasser',
            'author' => 'Fyodor',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_006' => [
            'title' => 'Brief an den Weihnachtsmann',
            'author' => 'zhack',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_007' => [
            'title' => 'Brief an Emily',
            'author' => 'Ralain',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_008' => [
            'title' => 'Brief an Nancy',
            'author' => 'Zekjostov',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_009' => [
            'title' => 'Brief an Nelly',
            'author' => 'aera10',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_010' => [
            'title' => 'Brief einer Mutter',
            'author' => 'MonochromeEmpress',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_011' => [
            'title' => 'Christin',
            'author' => 'Fexon',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_012' => [
            'title' => 'Coctails Tagebuch Teil 2',
            'author' => 'coctail',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_013' => [
            'title' => 'Coctails Tagebuch Teil 3',
            'author' => 'coctail',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_014' => [
            'title' => 'Der Verrat',
            'author' => 'Liior',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_015' => [
            'title' => 'Ein Briefbündel',
            'author' => 'Ferra',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_016' => [
            'title' => 'Ein seltsamer Brief',
            'author' => null,
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_017' => [
            'title' => 'Frys Erlebnis',
            'author' => 'Sardock4r',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_018' => [
            'title' => 'Gewinnlos',
            'author' => null,
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_019' => [
            'title' => 'Ich liebe sie',
            'author' => 'Kouta',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_020' => [
            'title' => 'In Bier geschmorte Ratte',
            'author' => 'Akasha',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_021' => [
            'title' => 'Kettensäge & Kater',
            'author' => 'TuraSatana',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_022' => [
            'title' => 'Mein bester Freund KevKev',
            'author' => 'Rayalistic',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_023' => [
            'title' => 'Merkwürdiger Text',
            'author' => 'Moyen',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_024' => [
            'title' => 'Mitteilung',
            'author' => 'DBDevil',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_025' => [
            'title' => 'Morsecode (21.Juni)',
            'author' => 'zhack',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_026' => [
            'title' => 'Mysteriöse Befunde - Tote weisen menschliche Bissspuren auf',
            'author' => null,
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_027' => [
            'title' => 'Papierfetzen',
            'author' => 'gangster',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_028' => [
            'title' => 'Post-It',
            'author' => 'Sunsky',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_029' => [
            'title' => 'Rabe, schwarz',
            'author' => 'accorexel',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_030' => [
            'title' => 'Richards Tagebuch',
            'author' => 'Cronos',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_031' => [
            'title' => 'Schmerzengels Überlebensregeln',
            'author' => 'Schmerzengel',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_032' => [
            'title' => 'Sprinkleranlage im Eigenbau',
            'author' => 'Tycho',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_033' => [
            'title' => 'Seite 62 eines Buches',
            'author' => 'kozi',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_034' => [
            'title' => 'Sicherer Unterschlupf',
            'author' => 'Loadim',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_035' => [
            'title' => 'Sie nennen sie Zombies - Politisch korrekter Umgang mit Vermindert Lebenden',
            'author' => 'accorexel',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_036' => [
            'title' => 'Twinoidetikett',
            'author' => null,
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_037' => [
            'title' => 'Warnhinweis an zukünftige Wanderer',
            'author' => 'coctail',
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_038' => [
            'title' => 'WG',
            'author' => null,
            'content' => [''],
            'lang' => 'de'
        ],
        'dv_039' => [
            'title' => 'Zahlen',
            'author' => 'Nomad',
            'content' => [''],
            'lang' => 'de'
        ],
        /**
         * FRENCH ROLE PLAY TEXTS
         */
        "oldboy" => [
	        "title" => "A toi, vieux compagnon...",
	        "author" => "Sekhmet",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "alime" => [
	        "title" => "Alimentation saine",
	        "author" => "Darkhan",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => "white"
	    ],
	    "citya1" => [
	        "title" => "Annonce : astrologie",
	        "author" => "Sigma",
	        "content" => [
	        	'<div class="content">
				<div class="hr"></div>
				<h1>Annonce publique</h1>
				<p>Suite aux attaques récentes, l\'horoscope matinal de Radio Survivant ne concernera que 7 signes astrologiques au lieu des 9 habituels. De plus, Natacha sera remplacé par Roger. Adieu Natacha.</p>
				</div>'
			],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "citya3" => [
	        "title" => "Annonce : banquier",
	        "author" => "Sigma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "citya5" => [
	        "title" => "Annonce : catapulte",
	        "author" => "Sigma",
	        "content" => [
	        	'<div class="hr"></div>
				<h1>Annonce publique</h1>
				<p>Les ouvriers du secteur 2 ont tentés de créer une catapulte pour expulser les cadavres. Un chat errant "volontaire" a participé aux tests. Malheureusement, il a "atterrit" contre le mur d\'enceinte.</p>'
			],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "citya4" => [
	        "title" => "Annonce : concert",
	        "author" => "Sigma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "citya2" => [
	        "title" => "Annonce : puits",
	        "author" => "Sigma",
	        "content" => [
	        	'<div class="hr"></div>
				<h1>Annonce publique</h1>
				<p>Nous rappelons aux plaisantins du Bloc E qu\'il est formellement interdit de jeter des zombies dans le puits. La fumée en résultant est trop proche du signal annonçant l\'évacuation d\'urgence.</p>'
			],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "cityb1" => [
	        "title" => "Annonce : séparatistes",
	        "author" => "Sigma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "news2" => [
	        "title" => "Article - Meurtre sauvage",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "news",
	        "design" => "news"
	    ],
	    "news3" => [
	        "title" => "Article - Nouveau cas de cannibalisme",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "news",
	        "design" => "news"
	    ],
	    "autop1" => [
	        "title" => "Autopsie d'un rat (partie 1 sur 3)",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "autop2" => [
	        "title" => "Autopsie d'un rat (partie 2 sur 3)",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "autop3" => [
	        "title" => "Autopsie d'un rat (partie 3 sur 3)",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "delir1" => [
	        "title" => "Avertissement macabre",
	        "author" => "coctail",
	        "content" => [
	        	'<div class="hr"></div>
				<p>Ils sont partout, je vous dis, partout ! Ils sont là avec leurs griffes et leur faim. Leur faim insatiable de viande fraiche, de viande fraiche. Mais ce n\'est pas ça le pire. Oh, non, ce n\'est pas ça le pire&nbsp;! Le pire, c\'est quand vous avez été grignoté, vous n\'êtes pas encore mort&nbsp;! Et ils vous laissent comme ça jusqu\'à ce que vous deveniez l\'un des leurs...</p>'
			],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "sign1" => [
	        "title" => "Avis aux citoyens",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "bilan" => [
	        "title" => "Bilan de la réunion du 7 novembre",
	        "author" => "Liior",
	        "content" => [
	        	'<h1>Réunion du village du 7 novembre :<small>(Retranscrit par le citoyen Liior, en charge de la Gazette)</small></h1>
				<p>Le chef explique que nous avons entamé une construction énorme, qui peut-être nous "sauvera la vie" :</p>
				<quote>"C\'est un projet totalement insensé ! Mais cela pourrait marcher. Nous avons déjà mis beaucoup d\'énergie à ranger le village d\'une manière plus efficace pour lutter contre ces créatures, mais nous avons encore un effort à faire. J\'ai pensé que peut-être, si on créait un leurre gigantesque, les zombies ne viendraient plus.. Il faut que nous construisions une fausse ville.. Cela peut paraitre bizarre, mais je pense que les zombies sont incapables de faire la différence entre notre village, et un autre.."</quote>',
				'<p>L\'assemblée semble dubitative : </p>
				<quote>"Une fausse ville ? Et ça duperait les zombie&nbsp;?", semblent se demander les autres citoyens dans un brouhaha incompréhensible.</quote>
				<p>L\'assemblée a pourtant voté pour ce projet.. Il faut croire qu\'il ne reste pas beaucoup d\'espoirs..</p>'
			],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => "white"
	    ],
	    "xmasad" => [
	        "title" => "Buffet de Noël",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "carmae" => [
	        "title" => "Carnet de Maeve",
	        "author" => "Maeve",
	        "content" => [
	        	'<p>Tout le monde est rentré. Nos quelques 30 citoyens survivants sont (provisoirement) en sûreté derrière les murailles. Elles branlent un peu, quand on cogne dans le mur, on se prend une avalanche de cailloux sur la tête, mais pour ce soir ça sera suffisant. Si on a de la chance...</p>
				<p>Portes fermées, tous s\'enferment. Chacun marmonne dans son coin, attendant  dans l\'angoisse l\'attaque de la nuit. Dans un coin, une fille pleure. Encore une qui cède à la panique. Pas assez d\'énergie pour se soucier des autres, personne n\'ira l\'aider.</p>
				<p>Pour fuir cette ambiance lourde, je grimpe en haut d\'un toit, guette l\'avancée de la horde, plongée dans mes pensées. Et puis, soudain, venu du désert, un son insolite me tire de ma rêverie morbide. Quelqu\'un chante dehors. Pourtant... tout le monde est en ville.</p>',
				'<p>Plissant les yeux, je distingue une forme. Puis des couleurs. Quelques lambeaux de vêtements, des traces de morsures, des croûtes, un regard vide. Non, c\'est pas un des nôtres. Un mort-vivant. Je ne savais pas qu\'ils pouvaient chanter... Je me laisse prendre au piège de la chanson. Et si...</p>
				<p>Les zombies ne sont peut-être pas si mauvais. D\'accord, ils mangent de la chair humaine. Mais qu\'est-ce que j\'ai fait moi ce matin ? C\'est bien Survivor que j\'ai découpé ? La chanson me supplie. Tout le monde est bien chez soi ? Personne ne me verra descendre et ouvrir les portes...</p>'
			],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "written"
	    ],
	    "macsng" => [
	        "title" => "Chansons macabres",
	        "author" => "Sekhmet",
	        "content" => [
	        	'<p>Combien de temps survivrons nous ? Je l\'ignore. J\'évite d\'être pessimiste devant mes compagnons d\'infortune. Je pense qu\'eux aussi.</p>
				<p>Mais pour moi, ce soir est la fin. Je suis gravement blessé, je ne passerai pas la nuit.</p>
				<p>Je pense que je délire déjà. Au moment de rentrer dans ce qui me sert de maison, j\'ai assisté à la plus étrange des scènes. Une silhouette était assise, sur le toit de sa cabane. M\'approchant, j\'ai reconnu une de mes camarades de chantier. Une femme plutôt taciturne, renfermée.</p>
				<p>Elle chantait.</p>',
				'<p>Je suis resté là, fasciné, à écouter ce chant doux et mélancolique alors que le soir tombait. Chant qui sonnait d\'autant plus doux qu\'il contrastait avec la pâleur de son teint, les cernes sous ses yeux et les traces de boue et de sang sur ses vêtements.</p>
				<p>Je suis resté là, à regarder cette frêle silhouette, cette jeune femme qui, dans d\'autre circonstances, dans une autre vie, aurait pu être belle.</p>
				<p>Son fredonnement, presque envoûtant, semblait appartenir à un autre monde, venu pour apporter un peu de paix dans ce monde sans pitié.</p>
				<p>Comme un minuscule et fragile ilôt d\'apaisement au milieu de la tourmente.</p>',
				'<p>Le chant s\'est arrêté en même temps que le dernier rayon de soleil éclairait la ville. Comme si la mort reprenait ses droits. Mon coeur s\'est serré et j\'ai laissé échapper une larme.</p>
				<p>Je vais mourir ce soir, je le sais. Mais peu m\'importe. </p>'
	        ],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "coloc" => [
	        "title" => "Colocation",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "denonc" => [
	        "title" => "Conspiration",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "contine" => [
	        "title" => "Contine : SilverTub",
	        "author" => "TubuBlobz",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "sngsek" => [
	        "title" => "Contine des jours sans lendemain",
	        "author" => "Sekhmet",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "lettre" => [
	        "title" => "Correspondance",
	        "author" => "Teia",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "alone1" => [
	        "title" => "Coupés du reste du monde",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "dfive1" => [
	        "title" => "Courrier d'un citoyen 1",
	        "author" => "dragonfive",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "lettr1" => [
	        "title" => "Courrier d'un citoyen 2",
	        "author" => "Nikka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "water" => [
	        "title" => "De l'eau pour tous",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "noteup"
	    ],
	    "last1" => [
	        "title" => "Dernier survivant",
	        "author" => "Arma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "short1" => [
	        "title" => "Derniers mots",
	        "author" => "Exdanrale",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "notes1" => [
	        "title" => "Des notes griffonnées",
	        "author" => "Melie",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "noteup"
	    ],
	    "poem2" => [
	        "title" => "Deux vies et un festin",
	        "author" => "SeigneurAo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "degen" => [
	        "title" => "Dégénérescence",
	        "author" => "Fabien08",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "leavng" => [
	        "title" => "Départ",
	        "author" => "1984",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "scient" => [
	        "title" => "Emission radiophonique d'origine inconnue",
	        "author" => "pepitou",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => "white"
	    ],
	    "dead1" => [
	        "title" => "Épitaphe pour Alfred",
	        "author" => "Planeshift",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "errnce" => [
	        "title" => "Errance",
	        "author" => "Crow",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "ster" => [
	        "title" => "Etiquette de stéroïdes pour chevaux",
	        "author" => "dragonfive",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => null,
	        "design" => null
	    ],
	    "study1" => [
	        "title" => "Etude médicale 1 : morphologie comparative",
	        "author" => "ChrisCool",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "study3" => [
	        "title" => "Etude médicale 3 : reproduction",
	        "author" => "ChrisCool",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "study4" => [
	        "title" => "Etude médicale 4 : alimentation",
	        "author" => "ChrisCool",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "study5" => [
	        "title" => "Étude médicale 5 : décès",
	        "author" => "ChrisCool",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "pehach" => [
	        "title" => "Exil",
	        "author" => "Pehache",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "secret",
	        "design" => "written"
	    ],
	    "expalp" => [
	        "title" => "Expédition alpha",
	        "author" => "Shogoki",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "written"
	    ],
	    "eclr1" => [
	        "title" => "Explorations",
	        "author" => "elyria",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => "white"
	    ],
	    "dodod" => [
	        "title" => "Fais dodo",
	        "author" => "NabOleon",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "crazy" => [
	        "title" => "Folie",
	        "author" => "Arco",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "poem1" => [
	        "title" => "Gazette du Gouffre du Néant, décembre",
	        "author" => "lordsolvine",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "news",
	        "design" => "news"
	    ],
	    "gcm" => [
	        "title" => "Gros Chat Mignon",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "haiku1" => [
	        "title" => "Haiku I",
	        "author" => "stravingo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "money",
	        "design" => "written"
	    ],
	    "infect" => [
	        "title" => "Infection",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "alone2" => [
	        "title" => "Isolement",
	        "author" => "Arco",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "coward" => [
	        "title" => "Je suis un trouillard",
	        "author" => "Sengriff",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => null,
	        "design" => null
	    ],
	    "alan" => [
	        "title" => "Journal d’Alan Morlante",
	        "author" => "lycanus",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => ""
	    ],
	    "slept" => [
	        "title" => "Journal d'un citoyen : Doriss",
	        "author" => "Arma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "log2" => [
	        "title" => "Journal d'un citoyen inconnu 1",
	        "author" => "Muahahah",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "noteup"
	    ],
	    "coctl1" => [
	        "title" => "Journal de Coctail, partie 1",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "coctl2" => [
	        "title" => "Journal de Coctail, partie 2",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "coctl3" => [
	        "title" => "Journal de Coctail, partie 3",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "coctl4" => [
	        "title" => "Journal de Coctail, partie 4",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "logpin" => [
	        "title" => "Journal de Pierre Ignacio Tavarez",
	        "author" => "ChrisCool",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "intime" => [
	        "title" => "Journal intime",
	        "author" => "Homerzombi",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => "white"
	    ],
	    "nowatr" => [
	        "title" => "Joyeux réveillon",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "thief" => [
	        "title" => "Jugement",
	        "author" => "stravingo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "cenhyd" => [
	        "title" => "La centrale hydraulique",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "money",
	        "design" => "written"
	    ],
	    "cigs1" => [
	        "title" => "La clope du condamné",
	        "author" => "Amnesia",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "tinystamp",
	        "design" => "small"
	    ],
	    "logsur" => [
	        "title" => "La colline aux survivants",
	        "author" => "Darkhan27",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "hang" => [
	        "title" => "La potence",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "chief" => [
	        "title" => "La trahison",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "cidcor" => [
	        "title" => "Le CID de Pierre Corbeau",
	        "author" => "bartock",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "aohero" => [
	        "title" => "Le Héros",
	        "author" => "SeigneurAo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "postit",
	        "design" => "postit"
	    ],
	    "mixer" => [
	        "title" => "Le batteur électrique",
	        "author" => "Esuna114",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "chaos" => [
	        "title" => "Le chaos",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "crema2" => [
	        "title" => "Le crémato-cue",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "noel" => [
	        "title" => "Lettre au Père Noël",
	        "author" => "zhack",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "anarch" => [
	        "title" => "Lettre d'Anarchipel",
	        "author" => "Sigma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "nelly" => [
	        "title" => "Lettre pour Nelly",
	        "author" => "aera10",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "lettr2" => [
	        "title" => "Lettre à Émilie",
	        "author" => "Ralain",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "written"
	    ],
	    "letann" => [
	        "title" => "Lettres d'un milicien",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "offrex" => [
	        "title" => "Maison à vendre",
	        "author" => "Pyrolis",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "mascar" => [
	        "title" => "Mascarade",
	        "author" => "irewiss",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "noteup"
	    ],
	    "messagecommiss" => [
	        "title" => "Message à la commission",
	        "author" => "Gizmonster",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "csaatk" => [
	        "title" => "Menaces du CSA",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "warnad" => [
	        "title" => "Mise en garde",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "letwar" => [
	        "title" => "Mort d'un baroudeur",
	        "author" => "Planeshift",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "tinystamp",
	        "design" => "small"
	    ],
	    "ultim1" => [
	        "title" => "Mort ultime",
	        "author" => "IIdrill",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "cave1" => [
	        "title" => "Mot déchiré",
	        "author" => "gangster",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "ie" => [
	        "title" => "Note pour les prochains promeneurs",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "money",
	        "design" => "written"
	    ],
	    "thonix" => [
	        "title" => "Notes de Thonix",
	        "author" => "Thonix",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "nightm" => [
	        "title" => "Nuit courte",
	        "author" => "elfique20",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "cold1" => [
	        "title" => "Obscurité glaciale",
	        "author" => "Planeshift",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "ode" => [
	        "title" => "Ode aux corbeaux",
	        "author" => "Firenz",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "page51" => [
	        "title" => "Page 51 d'un roman",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "rednck" => [
	        "title" => "Page de carnet déchirée",
	        "author" => "Savignac",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "citsig" => [
	        "title" => "Panneau de ville",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "condm" => [
	        "title" => "Paroles d'un condamné",
	        "author" => "Arma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "hangng" => [
	        "title" => "Pendaison",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "old",
	        "design" => "small"
	    ],
	    "alcthe" => [
	        "title" => "Pensées sur Post-its",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "postit",
	        "design" => "postit"
	    ],
	    "bricot" => [
	        "title" => "Prospectus Brico-Tout",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "manual",
	        "design" => "modern"
	    ],
	    "adbnkr" => [
	        "title" => "Publicité Bunker-4-Life",
	        "author" => "zhack",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => null,
	        "design" => null
	    ],
	    "fishes" => [
	        "title" => "Pêche",
	        "author" => "Irmins",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "secret",
	        "design" => "written"
	    ],
	    "army1" => [
	        "title" => "Rapport d'opération Nov-46857-A",
	        "author" => "zhack",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "heli1" => [
	        "title" => "Rapport d'une unité de soutien",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "truck1" => [
	        "title" => "Rapport de combat 1",
	        "author" => "sanka",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "printer",
	        "design" => "poem"
	    ],
	    "repor5" => [
	        "title" => "Rapport de ville 1",
	        "author" => "Arma",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "refabr" => [
	        "title" => "Refuge Abrité",
	        "author" => "Loadim",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "outrat" => [
	        "title" => "Rongeur reconnaissant",
	        "author" => "lerat",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "necro" => [
	        "title" => "Rubrique nécrologique",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
	    ],
	    "cutleg" => [
	        "title" => "Récit d'un habitant",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "lords1" => [
	        "title" => "Récits de LordSolvine, partie 1",
	        "author" => "lordsolvine",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "lords2" => [
	        "title" => "Récits de LordSolvine, partie 2",
	        "author" => "lordsolvine",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "adaper" => [
	        "title" => "Réclame Overture technology",
	        "author" => "Sengriff",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => null,
	        "design" => null
	    ],
	    "sadism" => [
	        "title" => "Sadique",
	        "author" => "esuna114",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "nohope" => [
	        "title" => "Sans espoir",
	        "author" => "Boulay",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "white",
	        "design" => "white"
	    ],
	    "shun1" => [
	        "title" => "Shuny : Témoignage des derniers jours",
	        "author" => "Shuny",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "surviv" => [
	        "title" => "Survivre",
	        "author" => "Liior",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "noteup",
	        "design" => "written"
	    ],
	    "nice" => [
	        "title" => "Sélection naturelle",
	        "author" => "stravingo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "theor1" => [
	        "title" => "Théories nocturnes 1",
	        "author" => "Planeshift",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "theor2" => [
	        "title" => "Théories nocturnes 2",
	        "author" => "Bigmorty",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "grid",
	        "design" => "typed"
	    ],
	    "wintck" => [
	        "title" => "Ticket gagnant",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "tinystamp",
	        "design" => "small"
	    ],
	    "lostck" => [
	        "title" => "Ticket perdant",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "tinystamp",
	        "design" => "small"
	    ],
	    "crema1" => [
	        "title" => "Tirage au sort",
	        "author" => "stravingo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "theend" => [
	        "title" => "Tout est donc fini.",
	        "author" => "CeluiQuOnNeNommePas",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "cult1" => [
	        "title" => "Tract du culte de la morte-vie",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "letter",
	        "design" => "small"
	    ],
	    "utpia1" => [
	        "title" => "Un brouillon",
	        "author" => "anonyme",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "secret",
	        "design" => "written"
	    ],
	    "loginc" => [
	        "title" => "Un journal incomplet",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "granma" => [
	        "title" => "Un post-it",
	        "author" => "sunsky",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "postit",
	        "design" => "postit"
	    ],
	    "night1" => [
	        "title" => "Une nuit comme les autres...",
	        "author" => "Ahmen",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "notepad",
	        "design" => "written"
	    ],
	    "night2" => [
	        "title" => "Une nuit dehors",
	        "author" => "mrtee50",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "blood",
	        "design" => "blood"
	    ],
	    "jay" => [
	        "title" => "Une pile de post-its",
	        "author" => "todo",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "postit",
	        "design" => "postit"
	    ],
	    "revnge" => [
	        "title" => "Vengeance",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "tinystamp",
	        "design" => "small"
	    ],
	    "nails" => [
	        "title" => "Vis et écrous",
	        "author" => "totokogure",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "secret",
	        "design" => "written"
	    ],
	    "contam" => [
	        "title" => "Zone contaminée",
	        "author" => "coctail",
	        "content" => [''],
	        "lang" => "fr",
	        "background" => "carton",
	        "design" => "typed"
        ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_rp_texts(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>RP texts: ' . count(static::$texts) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$texts) );

        // Iterate over all entries
        foreach (static::$texts as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(RolePlayText::class)->findOneByName($name);
            if ($entity === null){
            	$entity = new RolePlayText();	
            } else {
            	if (!empty($entity->getPages())){
            		foreach ($entity->getPages() as $page) {
        				$manager->remove($page);
        			}	
        			$manager->flush();
            	} 
            }

            // Set property
            $entity
                ->setName( $name )
                ->setAuthor( $entry['author'] )
                ->setTitle( $entry['title'] )
                ->setLanguage($entry['lang'])
            ;

            if(isset($entry['background']))
                $entity->setBackground($entry['background']);

            if(isset($entry['design']))
                $entity->setDesign($entry['design']);

            for($i = 0; $i < count($entry['content']); $i++){
            	$page = new RolePlayTextPage();
            	$page->setPageNumber($i + 1);
            	$page->setRolePlayText($entity);
            	$page->setContent($entry['content'][$i]);
            	$entity->addPage($page);
            	$manager->persist($page);
            }

            $manager->persist($entity);
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Texts</info>' );
        $output->writeln("");

        $this->insert_rp_texts( $manager, $output );
        $output->writeln("");
    }
}
