<?php

namespace App\DataFixtures;

use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Entity\RolePlayTextPage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use MyHordes\Plugins\Fixtures\RolePlayText as RolePlayTextFixtures;

class TextFixtures extends Fixture
{
    private RolePlayTextFixtures $rp_text_data;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em, RolePlayTextFixtures $tx)
    {
        $this->entityManager = $em;
        $this->rp_text_data = $tx;
    }

    protected function insert_rp_texts(ObjectManager $manager, ConsoleOutputInterface $out) {
        $texts = $this->rp_text_data->data();
        $out->writeln( '<comment>RP texts: ' . count($texts) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($texts) );

        $id_cache = [];

        // Iterate over all entries
        foreach ($texts as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(RolePlayText::class)->findOneBy(['name' => $name]);
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

            if (isset($id_cache[$name])) throw new Exception("Duplicate text fixture: '{$name}'");
            $id_cache[$name] = true;

            // Set property
            $entity
                ->setName( $name )
                ->setAuthor( $entry['author'] )
                ->setTitle( $entry['title'] )
                ->setLanguage($entry['lang'])
                ->setUnlockable($id_cache[$name] = ($entry['unlockable'] ?? true));

            if ($entity->getUnlockable())

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

        $deleted_rps = $this->entityManager->getRepository( RolePlayText::class )->findAllByLangExcept(null, array_keys($id_cache), true);
        if (count($deleted_rps) > 0) {
            $out->writeln('');
            $out->writeln('There are <info>' . count($deleted_rps) . '</info> deleted RP texts!');

            $invalid_assignments = $this->entityManager->getRepository( FoundRolePlayText::class )->findBy(['text' => $deleted_rps]);
            $out->writeln('There are <info>' . count($invalid_assignments) . '</info> invalid assignments!');

            if ( count($invalid_assignments) > 0 ) {
                $assignment_users_list = [];
                foreach ($invalid_assignments as $ass) {
                    $key = "{$ass->getText()->getLanguage()}:{$ass->getUser()->getId()}";
                    if (!isset($assignment_users_list[$key]))
                        $assignment_users_list[$key] = [$ass];
                    else $assignment_users_list[$key][] = $ass;
                }

                foreach ($assignment_users_list as $assignment_list) {
                    $possibles = $this->entityManager->getRepository(RolePlayText::class)->findAllByLangExcept( $assignment_list[0]->getText()->getLanguage(), $assignment_list[0]->getUser()->getFoundTexts()->getValues() );
                    shuffle($possibles);
                    foreach ($assignment_list as $ass)
                        if (!empty($possibles)) {
                            $ass->setText( array_pop($possibles) );
                            $this->entityManager->persist($ass);
                            $out->writeln("Updated assignment <info>{$ass->getId()}</info>.");
                        } else $out->writeln("No potential to update assignment <info>{$ass->getId()}</info>.");
                }

                $this->entityManager->flush();
            }


        }
    }

    /** @noinspection PhpHierarchyChecksInspection */
    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Texts</info>' );
        $output->writeln("");

        $this->insert_rp_texts( $manager, $output );
        $output->writeln("");
    }
}
