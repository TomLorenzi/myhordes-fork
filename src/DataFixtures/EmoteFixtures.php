<?php


namespace App\DataFixtures;


use App\Entity\Emotes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class EmoteFixtures extends Fixture {

    private $entityManager;

    public function __construct(EntityManagerInterface $em) {
        $this->entityManager = $em;
    }

    protected static $emote_data = [
        ['tag'=>':mh:', 'path'=>'build/images/emotes/favicon.ico', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 0],
        ['tag'=>':smile:', 'path'=>'build/images/emotes/smile.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 1],
        ['tag'=>':sad:', 'path'=>'build/images/emotes/sad.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 2],
        ['tag'=>':blink:', 'path'=>'build/images/emotes/blink.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 3],
        ['tag'=>':surprise:', 'path'=>'build/images/emotes/surprise.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 4],
        ['tag'=>':lol:', 'path'=>'build/images/emotes/lol.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 5],
        ['tag'=>':thinking:', 'path'=>'build/images/emotes/thinking.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 6],
        ['tag'=>':neutral:', 'path'=>'build/images/emotes/neutral.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 7],
        ['tag'=>':rage:', 'path'=>'build/images/emotes/rage.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 8],
        ['tag'=>':angry:', 'path'=>'build/images/emotes/angry.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 9],
        ['tag'=>':sleep:', 'path'=>'build/images/emotes/sleep.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 10],
        ['tag'=>':wink:', 'path'=>'build/images/emotes/wink.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 11],
        ['tag'=>':horror:', 'path'=>'build/images/emotes/horror.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 12],
        ['tag'=>':zhead:', 'path'=>'build/images/emotes/zhead.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 13],
        ['tag'=>':sick:', 'path'=>'build/images/emotes/sick.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 14],
        ['tag'=>':home:', 'path'=>'build/images/emotes/home.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 15],
        ['tag'=>':gate:', 'path'=>'build/images/emotes/gate.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 16],
        ['tag'=>':water:', 'path'=>'build/images/emotes/water.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 17],
        ['tag'=>':human:', 'path'=>'build/images/emotes/human.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 18],
        ['tag'=>':heal:', 'path'=>'build/images/emotes/heal.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 19],
        ['tag'=>':drug:', 'path'=>'build/images/emotes/drug.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 20],
        ['tag'=>':death:', 'path'=>'build/images/emotes/death.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 21],
        ['tag'=>':bone:', 'path'=>'build/images/emotes/bone.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 22],
        ['tag'=>':bag:', 'path'=>'build/images/emotes/bag.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 23],
        ['tag'=>':soul:', 'path'=>'build/images/emotes/soul.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 24],
        ['tag'=>':refine:', 'path'=>'build/images/emotes/refine.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 25],
        ['tag'=>':warning:', 'path'=>'build/images/emotes/warning.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 26],
        ['tag'=>':bp:', 'path'=>'build/images/emotes/bp.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 27],
        ['tag'=>':fortify:', 'path'=>'build/images/emotes/fortify.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 28],
        ['tag'=>':def:', 'path'=>'build/images/emotes/def.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 29],
        ['tag'=>':camp:', 'path'=>'build/images/emotes/camp.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 30],
        ['tag'=>':sites:', 'path'=>'build/images/emotes/sites.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 31],
        ['tag'=>':arrowleft:', 'path'=>'build/images/emotes/arrowleft.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 32],
        ['tag'=>':middot:', 'path'=>'build/images/emotes/middot.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 33],
        ['tag'=>':arrowright:', 'path'=>'build/images/emotes/arrowright.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 34],
        ['tag'=>':arma:', 'path'=>'build/images/emotes/arma.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 35],
        ['tag'=>':chat:', 'path'=>'build/images/emotes/chat.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 36],
        ['tag'=>':calim:', 'path'=>'build/images/emotes/calim.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 37],
        ['tag'=>':xmas:', 'path'=>'build/images/emotes/xmas.gif', 'isactive'=> false, 'requiresunlock'=> false, 'index'=> 38],
        ['tag'=>':scout:', 'path'=>'build/images/emotes/scout.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 39],
        ['tag'=>':scav:', 'path'=>'build/images/emotes/scav.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 40],
        ['tag'=>':surv:', 'path'=>'build/images/emotes/surv.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 41],
        ['tag'=>':guard:', 'path'=>'build/images/emotes/guard.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 42],
        ['tag'=>':tech:', 'path'=>'build/images/emotes/tech.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 43],
        ['tag'=>':tamer:', 'path'=>'build/images/emotes/tamer.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 44],
        ['tag'=>':basic:', 'path'=>'build/images/emotes/basic.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 45],
        ['tag'=>':sham:', 'path'=>'build/images/emotes/sham.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 46],
        ['tag'=>':guide:', 'path'=>'build/images/emotes/guide.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 47],
        ['tag'=>':ghoul:', 'path'=>'build/images/emotes/ghoul.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 48],
        ['tag'=>':ap:', 'path'=>'build/images/emotes/ap.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 49],
        ['tag'=>':pc:', 'path'=>'build/images/emotes/pc.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 50],
        ['tag'=>':pm:', 'path'=>'build/images/emotes/pm.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 51],
        ['tag'=>':iloveu:', 'path'=>'build/images/emotes/iloveu.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 52],
        ['tag'=>':sock:', 'path'=>'build/images/emotes/socks.gif', 'isactive'=> true, 'requiresunlock'=> false, 'index'=> 53],
        ['tag'=>':build:', 'path'=>'build/images/emotes/build.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 54],
        ['tag'=>':clean:', 'path'=>'build/images/emotes/clean.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 55],
        ['tag'=>':repair:', 'path'=>'build/images/emotes/repair.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 56],
        ['tag'=>':wonder:', 'path'=>'build/images/emotes/wonder.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 57],
        ['tag'=>':tasty:', 'path'=>'build/images/emotes/tasty.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 58],
        ['tag'=>':deco:', 'path'=>'build/images/emotes/deco.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 59],
        ['tag'=>':buried:', 'path'=>'build/images/emotes/buried.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 60],
        ['tag'=>':rptext:', 'path'=>'build/images/emotes/rptext.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 61],
        ['tag'=>':ban:', 'path'=>'build/images/emotes/ban.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 62],
        ['tag'=>':extreme:', 'path'=>'build/images/emotes/extreme.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 63],
        ['tag'=>':proscout:', 'path'=>'build/images/emotes/proscout.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 64],
        ['tag'=>':proguard:', 'path'=>'build/images/emotes/proguard.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 65],
        ['tag'=>':proscav:', 'path'=>'build/images/emotes/proscav.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 66],
        ['tag'=>':prosurv:', 'path'=>'build/images/emotes/prosurv.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 67],
        ['tag'=>':protamer:', 'path'=>'build/images/emotes/protamer.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 68],
        ['tag'=>':protech:', 'path'=>'build/images/emotes/protech.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 69],
        ['tag'=>':prosham:', 'path'=>'build/images/emotes/prosham.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 70],
        ['tag'=>':psoul:', 'path'=>'build/images/emotes/psoul.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 71],
        ['tag'=>':alc:', 'path'=>'build/images/emotes/alc.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 72],
        ['tag'=>':chain:', 'path'=>'build/images/emotes/chain.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 73],
        ['tag'=>':batgun:', 'path'=>'build/images/emotes/batgun.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 74],
        ['tag'=>':watergun:', 'path'=>'build/images/emotes/watergun.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 75],
        ['tag'=>':brd:', 'path'=>'build/images/emotes/brd.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 76],
        ['tag'=>':castle:', 'path'=>'build/images/emotes/castle.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 77],
        ['tag'=>':crow:', 'path'=>'build/images/emotes/crow.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 78],
        ['tag'=>':wheel:', 'path'=>'build/images/emotes/wheel.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 79],
        ['tag'=>':butcher:', 'path'=>'build/images/emotes/butcher.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 80],
        ['tag'=>':zombie:', 'path'=>'build/images/emotes/zombie.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 81],
        ['tag'=>':camper:', 'path'=>'build/images/emotes/camper.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 82],
        ['tag'=>':cannibal:', 'path'=>'build/images/emotes/cannibal.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 83],
        ['tag'=>':chest:', 'path'=>'build/images/emotes/chest.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 84],
        ['tag'=>':collect:', 'path'=>'build/images/emotes/collect.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=>85],
        ['tag'=>':night:', 'path'=>'build/images/emotes/night.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 86],
        ['tag'=>':ruin:', 'path'=>'build/images/emotes/ruin.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 87],
        ['tag'=>':explo:', 'path'=>'build/images/emotes/explo.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 88],
        ['tag'=>':dexplo:', 'path'=>'build/images/emotes/dexplo.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 89],
        ['tag'=>':drag:', 'path'=>'build/images/emotes/drag.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 90],
        ['tag'=>':shower:', 'path'=>'build/images/emotes/shower.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 91],
        ['tag'=>':experimental:', 'path'=>'build/images/emotes/experimental.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 92],
        ['tag'=>':fight:', 'path'=>'build/images/emotes/fight.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 93],
        ['tag'=>':hero:', 'path'=>'build/images/emotes/hero.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 94],
        ['tag'=>':lms:', 'path'=>'build/images/emotes/lms.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 95],
        ['tag'=>':hclms:', 'path'=>'build/images/emotes/hclms.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 96],
        ['tag'=>':zen:', 'path'=>'build/images/emotes/zen.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 97],
        ['tag'=>':infect:', 'path'=>'build/images/emotes/infect.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 98],
        ['tag'=>':lab:', 'path'=>'build/images/emotes/lab.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 99],
        ['tag'=>':lock:', 'path'=>'build/images/emotes/lock.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 100],
        ['tag'=>':last:', 'path'=>'build/images/emotes/last.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 101],
        ['tag'=>':mystic:', 'path'=>'build/images/emotes/mystic.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 102],
        ['tag'=>':maso:', 'path'=>'build/images/emotes/maso.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 103],
        ['tag'=>':nuke:', 'path'=>'build/images/emotes/nuke.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 104],
        ['tag'=>':thief:', 'path'=>'build/images/emotes/thief.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 105],
        ['tag'=>':pillage:', 'path'=>'build/images/emotes/pillage.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 106],
        ['tag'=>':ranked:', 'path'=>'build/images/emotes/ranked.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 107],
        ['tag'=>':legend:', 'path'=>'build/images/emotes/legend.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 108],
        ['tag'=>':rep:', 'path'=>'build/images/emotes/rep.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 109],
        ['tag'=>':santa:', 'path'=>'build/images/emotes/santa.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 110],
        ['tag'=>':trash:', 'path'=>'build/images/emotes/trash.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 111],
        ['tag'=>':watch:', 'path'=>'build/images/emotes/watch.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 112],
        ['tag'=>':goodg:', 'path'=>'build/images/emotes/goodg.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 113],
        ['tag'=>':ginfec:', 'path'=>'build/images/emotes/ginfec.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 114],
        ['tag'=>':share:', 'path'=>'build/images/emotes/share.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 115],
        ['tag'=>':bgum:', 'path'=>'build/images/emotes/bgum.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 116],
        ['tag'=>':ptame:', 'path'=>'build/images/emotes/ptame.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 117],
        ['tag'=>':ermwin:', 'path'=>'build/images/emotes/ermwin.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 118],
        ['tag'=>':cdhwin:', 'path'=>'build/images/emotes/cdhwin.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 119],
        ['tag'=>':defwin:', 'path'=>'build/images/emotes/defwin.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 120],
        ['tag'=>':cott:', 'path'=>'build/images/emotes/cott.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 121],
        ['tag'=>':pande:', 'path'=>'build/images/emotes/pande.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 122],
        ['tag'=>':lepre:', 'path'=>'build/images/emotes/lepre.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 123],
        ['tag'=>':gsp:', 'path'=>'build/images/emotes/gsp.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 124],
        ['tag'=>':ufc:', 'path'=>'build/images/emotes/ufc.gif', 'isactive'=> true, 'requiresunlock'=> true, 'index'=> 125],
    ];

    private function insertEmotes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Emotes: ' . count(static::$emote_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$emote_data) );

        foreach (static::$emote_data as $entry) {
            $entity = $this->entityManager->getRepository(Emotes::class)->findByTag($entry['tag']);
            if($entity === null) {
                $entity = new Emotes();
            }

            $entity->setTag($entry['tag']);
            $entity->setPath($entry['path']);
            $entity->setIsActive($entry['isactive']);
            $entity->setRequiresUnlock($entry['requiresunlock']);
            $entity->setOrderIndex($entry['index']);

            $manager->persist($entity);
            $progress->advance();
        }
        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Emotes Database</info>' );
        $output->writeln("");

        $this->insertEmotes($manager, $output);
        $output->writeln("");
    }
}