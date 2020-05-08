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
        ['tag'=>':smile:', 'path'=>'build/images/emotes/smile.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':sad:', 'path'=>'build/images/emotes/sad.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':blink:', 'path'=>'build/images/emotes/blink.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':surprise:', 'path'=>'build/images/emotes/surprise.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':lol:', 'path'=>'build/images/emotes/lol.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':thinking:', 'path'=>'build/images/emotes/thinking.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':neutral:', 'path'=>'build/images/emotes/neutral.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':rage:', 'path'=>'build/images/emotes/rage.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':angry:', 'path'=>'build/images/emotes/angry.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':sleep:', 'path'=>'build/images/emotes/sleep.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':wink:', 'path'=>'build/images/emotes/wink.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':horror:', 'path'=>'build/images/emotes/horror.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':zhead:', 'path'=>'build/images/emotes/zhead.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':sick:', 'path'=>'build/images/emotes/sick.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':home:', 'path'=>'build/images/emotes/home.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':gate:', 'path'=>'build/images/emotes/gate.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':water:', 'path'=>'build/images/emotes/water.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':zombie:', 'path'=>'build/images/emotes/zombie.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':human:', 'path'=>'build/images/emotes/human.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':heal:', 'path'=>'build/images/emotes/heal.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':drug:', 'path'=>'build/images/emotes/drug.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':buried:', 'path'=>'build/images/emotes/buried.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':death:', 'path'=>'build/images/emotes/death.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':bone:', 'path'=>'build/images/emotes/bone.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':bag:', 'path'=>'build/images/emotes/bag.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':soul:', 'path'=>'build/images/emotes/soul.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':refine:', 'path'=>'build/images/emotes/refine.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':warning:', 'path'=>'build/images/emotes/warning.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':bp:', 'path'=>'build/images/emotes/bp.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':fortify:', 'path'=>'build/images/emotes/fortify.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':def:', 'path'=>'build/images/emotes/def.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':camp:', 'path'=>'build/images/emotes/camp.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':sites:', 'path'=>'build/images/emotes/sites.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':arrowleft:', 'path'=>'build/images/emotes/arrowleft.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':middot:', 'path'=>'build/images/emotes/middot.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':arrowright:', 'path'=>'build/images/emotes/arrowright.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':arma:', 'path'=>'build/images/emotes/arma.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':ban:', 'path'=>'build/images/emotes/ban.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':chat:', 'path'=>'build/images/emotes/chat.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':deco:', 'path'=>'build/images/emotes/deco.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':scout:', 'path'=>'build/images/emotes/scout.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':scav:', 'path'=>'build/images/emotes/scav.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':surv:', 'path'=>'build/images/emotes/surv.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':sham:', 'path'=>'build/images/emotes/sham.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':guard:', 'path'=>'build/images/emotes/guard.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':tech:', 'path'=>'build/images/emotes/tech.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':tamer:', 'path'=>'build/images/emotes/tamer.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':ghoul:', 'path'=>'build/images/emotes/ghoul.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':basic:', 'path'=>'build/images/emotes/basic.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':ap:', 'path'=>'build/images/emotes/ap.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':pc:', 'path'=>'build/images/emotes/pc.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':pm:', 'path'=>'build/images/emotes/pm.gif', 'isactive'=> true, 'requiresunlock'=> false],
        ['tag'=>':build:', 'path'=>'build/images/emotes/build.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':clean:', 'path'=>'build/images/emotes/clean.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':repair:', 'path'=>'build/images/emotes/repair.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':wonder:', 'path'=>'build/images/emotes/wonder.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':tasty:', 'path'=>'build/images/emotes/tasty.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':rptext:', 'path'=>'build/images/emotes/rptext.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':extreme:', 'path'=>'build/images/emotes/extreme.gif', 'isactive'=> true, 'requiresunlock'=> true],
        ['tag'=>':proscout:', 'path'=>'build/images/emotes/proscout.gif', 'isactive'=> true, 'requiresunlock'=> true]
    ];

    private function insertEmotes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Item categories: ' . count(static::$emote_data) . ' fixture entries available.</comment>');

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