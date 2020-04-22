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
        'hordes_001' => [
            'title' => 'Annonce : astrologie',
            'author' => 'Sigma',
            'content' => [
            	'<div class="hr"></div>
            	<h1>Annonce publique</h1>
            	<p>Suite aux attaques récentes, l\'horoscope matinal de Radio Survivant ne concernera que 7 signes astrologiques au lieu des 9 habituels. De plus, Natacha sera remplacé par Roger. Adieu Natacha.</p>'
            ],
            'lang' => 'fr',
            'background' => 'carton',
            'design' => 'typed'
        ],
        'hordes_002' => [
            'title' => 'Annonce : catapulte',
            'author' => 'Sigma',
            'content' => [
            	'<div class="hr"></div>
            	<h1>Annonce publique</h1>
            	<p>Les ouvriers du secteur 2 ont tentés de créer une catapulte pour expulser les cadavres. Un chat errant "volontaire" a participé aux tests. Malheureusement, il a "atterrit" contre le mur d\'enceinte.</p>'
            ],
            'lang' => 'fr',
            'background' => 'carton',
            'design' => 'typed'
        ],
        'hordes_003' => [
            'title' => 'Annonce : Puits',
            'author' => 'Sigma',
            'content' => [
            	'<div class="hr"></div>
	            <h1>Annonce publique</h1>
	            <p>Nous rappelons aux plaisantins du Bloc E qu\'il est formellement interdit de jeter des zombies dans le puits. La fumée en résultant est trop proche du signal annonçant l\'évacuation d\'urgence.</p>'
	        ],
            'lang' => 'fr',
            'background' => 'carton',
            'design' => 'typed'
        ],
        'hordes_004' => [
            'title' => 'Avertissement macabre',
            'author' => 'Coctail',
            'content' => [
            	'<div class="hr"></div>
            	<p>Ils sont partout, je vous dis, partout ! Ils sont là avec leurs griffes et leur faim. Leur faim insatiable de viande fraiche, de viande fraiche. Mais ce n\'est pas ça le pire. Oh, non, ce n\'est pas ça le pire&nbsp;! Le pire, c\'est quand vous avez été grignoté, vous n\'êtes pas encore mort&nbsp;! Et ils vous laissent comme ça jusqu\'à ce que vous deveniez l\'un des leurs...</p>'
            ],
            'lang' => 'fr',
            'background' => 'blood',
            'design' => 'written'
        ],
        'hordes_005' => [
            'title' => 'Bilan de la réunion du 7 novembre',
            'author' => 'Liior',
            'content' => [
            	'<h1>Réunion du village du 7 novembre :<small>(Retranscrit par le citoyen Liior, en charge de la Gazette)</small></h1>
	            <p>Le chef explique que nous avons entamé une construction énorme, qui peut-être nous "sauvera la vie" :</p>
	            <quote>"C\'est un projet totalement insensé ! Mais cela pourrait marcher. Nous avons déjà mis beaucoup d\'énergie à ranger le village d\'une manière plus efficace pour lutter contre ces créatures, mais nous avons encore un effort à faire. J\'ai pensé que peut-être, si on créait un leurre gigantesque, les zombies ne viendraient plus.. Il faut que nous construisions une fausse ville.. Cela peut paraitre bizarre, mais je pense que les zombies sont incapables de faire la différence entre notre village, et un autre.."</quote>',
	            '<p>L\'assemblée semble dubitative : </p>
				<quote>"Une fausse ville ? Et ça duperait les zombie&nbsp;?", semblent se demander les autres citoyens dans un brouhaha incompréhensible.</quote>
				<p>L\'assemblée a pourtant voté pour ce projet.. Il faut croire qu\'il ne reste pas beaucoup d\'espoirs..</p>'
	        ],
            'lang' => 'fr',
            'background' => 'white',
            'design' => 'typed'
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
