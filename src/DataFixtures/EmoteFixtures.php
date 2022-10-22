<?php


namespace App\DataFixtures;


use App\Entity\Emotes;
use App\Entity\ForumUsagePermissions;
use App\Entity\ThreadTag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Plugins\Fixtures\Emote;
use MyHordes\Plugins\Fixtures\ForumThreadTag;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class EmoteFixtures extends Fixture {

    private EntityManagerInterface $entityManager;

    private Emote $emote_data;
    private ForumThreadTag $tag_data;

    public function __construct(EntityManagerInterface $em, Emote $emote_data, ForumThreadTag $tag_data) {
        $this->entityManager = $em;
        $this->emote_data = $emote_data;
        $this->tag_data = $tag_data;
    }

    private function insertEmotes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $emote_data = $this->emote_data->data();
        $out->writeln('<comment>Emotes: ' . count($emote_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($emote_data) );

        foreach ($emote_data as $entry) {
            $entity = $this->entityManager->getRepository(Emotes::class)->findByTag($entry['tag']);
            if($entity === null) {
                $entity = new Emotes();
            }

            $entity->setTag($entry['tag'])
                ->setPath($entry['path'])
                ->setIsActive($entry['isactive'])
                ->setRequiresUnlock($entry['requiresunlock'])
                ->setOrderIndex($entry['index'])
                ->setI18n($entry['i18n'] ?? false);

            $manager->persist($entity);
            $progress->advance();
        }
        $manager->flush();
        $progress->finish();
    }

    private function insertForumTags(ObjectManager $manager, ConsoleOutputInterface $out) {
        $tag_data = $this->tag_data->data();
        $out->writeln('<comment>Forum Tags: ' . count($tag_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($tag_data) );

        foreach ($tag_data as $name => $entry) {
            $entity = $this->entityManager->getRepository(ThreadTag::class)->findOneBy(['name' => $name]);
            if ($entity === null) {
                $entity = new ThreadTag();
            }

            $entity->setName($name)->setLabel($entry['label'])->setColor( hex2bin($entry['color'] ?? '00000030') )
                ->setPermissionMap($entry['mask'] ?? null);

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
        $this->insertForumTags($manager, $output);
        $output->writeln("");
    }
}