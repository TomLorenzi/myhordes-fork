<?php

namespace App\DataFixtures;

use App\Entity\Quote;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class QuoteFixtures extends Fixture implements DependentFixtureInterface {

    private $entityManager;

    protected static $quotes_data = [
        // French Quotes
        ['name' => 'fr_001', 'content' => '... Carnage... Défaite... Trahison... Lacheté... Voilà le vocabulaire à employer.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_002', 'content' => 'Allez quoi, ouvrez ! Promis je le referai plus... Les gars ?...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_003', 'content' => 'Aujourd\'hui enfin, j\'ai compris...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_004', 'content' => 'Avant de bannir, apprends donc à courir...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_005', 'content' => 'Avez-vous déjà vu du cartilage se liquéfier ? On s\'en souvient longtemps.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_006', 'content' => 'Avoir un taudis, c\'est perdre ses amis', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_007', 'content' => 'Boire ou mourir ? Pourquoi choisir ?', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_008', 'content' => 'Ca fait déjà trois jours que j\'ai très mal au ventre... Je me gave de bicarbonate de soude, mais si c\'est une de ces saletés de parasites qui vous dévorent de l\'intérieur...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_009', 'content' => 'Ce que j\'aime avec les expéditions en équipe c\'est quand ils creusent et que je ramasse !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_010', 'content' => 'Chaos du soir bien peu d\'espoir ?', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_011', 'content' => 'Chaos le matin, favorise le larcin !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_012', 'content' => 'Contre cent zombies, la meilleure technique c\'est encore la fuite !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_013', 'content' => 'Cultivez la pensée positive. Vous allez mourir. Dans tous les cas.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_014', 'content' => 'Dans les territoires extérieurs, il y a des ombres qui passent parfois à deux pas de nous. Des ombres colossales ! Et on ne les entend pas.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_015', 'content' => 'Depuis que cette vieille Rosie s\'est fait bouffer, plus besoin de faire le ménage ! ....Aaaaaahh ! Tante Rose ! ! ...Nooooon ! !... arrggll burrrrgl...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_016', 'content' => 'Des fois derrière le Héros se cache le crapaud', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_017', 'content' => 'Des fois, aux abords des relais, on trouve des ossements en remuant la terre. Mais on n\'arrive jamais à les identifier', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_018', 'content' => 'En expédition, quand tu sens le danger, attends et laisse-les tourner...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_019', 'content' => 'En ville, tout le monde l\'aimait, la doyenne. C\'est bien dommage qu\'on ait fini de la manger hier.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_020', 'content' => 'Garde toujours DEUX balles dans ton flingue. Une pour dégommer un macchabée, une pour toi si tu rates..', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_021', 'content' => 'Gifler son voisin, c\'est comme gifler un parpaing.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_022', 'content' => 'Hier avec Jessica on a décidé de tout partager. Aujourd\'hui je suis héros...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_023', 'content' => 'Hier, on était trois. Joe nous a dit : “plus on est nombreux, plus on est fort ! On va à gauche !”. On l\'a laissé tourner. Le lendemain, on a tout récupéré...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_024', 'content' => 'Il est dit : “Les Chroniques contiennent l\'essence de ces Terres, la Mémoire des oubliés”', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_025', 'content' => 'Il existe un seul moyen d\'oublier : ai-je besoin de vous en parler ?', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_026', 'content' => 'Il n\'y a pas de guerres de religion. Il n\'y a pas de guerre. Il n\'y a pas le temps.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_027', 'content' => 'Il s\'agissait juste de finir le travail... Vous vous posez sans doute la question... On l\'a effectivement fini... Mais à quel prix ?', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_028', 'content' => 'J\'ai pas fermé l\'oeil depuis trois jours, depuis que Carla est morte. Hein ? Ouais, je sais, c\'était la semaine dernière. Mais maintenant, elle cogne dans la cave.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_029', 'content' => 'J\'ai tellement faim que je boufferais mon voisin. Vu la vigueur des zombies d\'hier, ça peut pas être mauvais pour la santé.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_030', 'content' => 'J\'ai toujours eu une pensée émue pour les héros. Ils ont leur nom gravé à jamais sur la pierre.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_031', 'content' => 'J\'aime bien m\'évader, changer de peau, rêver. En général je fais tout ça une fois décédé...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_032', 'content' => 'Je sais, je sais. La bouffe est pas terrible mais au moins elle bouge pas quand tu croques dedans.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_033', 'content' => 'Jeremy m\'a dit : tu vas voir on va bien les avoir. Ils ont bien rigolé quand ils l\'ont pendu par les pieds...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_034', 'content' => 'Jerrycan quand j\'arrose les zombie.⇒ je ricane quand j\'arrose les zombie', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_035', 'content' => 'L\'âme se souvient. Souviens-t\'en !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_036', 'content' => 'La liberté se trouve dans la pastille blanche...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_037', 'content' => 'Le mode chaos, c\'est la cerise sur le tombeau...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_038', 'content' => 'Le veilleur songe à l\'oeil qui lui manque.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_039', 'content' => 'Le vieux Chuck a croisé la Horde, une nuit, il y a longtemps. Et il en est revenu. Sur le coup il a serré les fesses. Depuis, il ne les a toujours pas desserrées.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_040', 'content' => 'Mon chien, y a pas à dire, je l\'adore ! Il me défend et me distrait. Et puis si j\'ai faim...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_041', 'content' => 'N\'oublie jamais le nom de celui qui a volé', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_042', 'content' => 'N\'oubliez pas : les amis ne le restent pas pour l\'éternité.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_043', 'content' => 'Ne vous engagez pas ! Ne venez jamais !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_044', 'content' => 'On avait le choix entre lui pardonner ou la punir. On lui a finalement construit un cremato-cue. Pour ne plus avoir faim.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_045', 'content' => 'On essaie tous d\'imiter le grognement d\'un Zombie. Mais qui essaie d\'imiter le cri d\'un humain dont la chair, encore palpitante de vie, est dévorée ?', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_046', 'content' => 'On raconte beaucoup de choses sur les Hordes. Mais ce n\'est tout au plus qu\'une image imparfaite de la peur brutale qui vous assaille quand leurs assauts commencent.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_047', 'content' => 'Petite coalition entre amis, encore des soucis', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_048', 'content' => 'Petite pastille de cyanure... si petite et pourtant... une belle raclure...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_049', 'content' => 'Pourquoi hésiter, vendez votre âme !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_050', 'content' => 'Qu\'est ce qui est plus gênant qu\'une tique ? Une tique de 3kg. On les rencontre une seule fois...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_051', 'content' => 'Qu\'est-ce que j\'aurais donné pour mourir une bonne fois pour toutes ce jour là et ne jamais avoir à revivre ça.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_052', 'content' => 'Qu\'est-ce qui pend, hirsute et malodorant ? Mon voisin, pendu, forcément...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_053', 'content' => 'Quand c\'est le chaos, fiez-vous à votre instinct.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_054', 'content' => 'Quand je l\'entends rire, j\'attends un instant puis je le pends.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_055', 'content' => 'Quand on vous dit blanc, pensez noir. Quand on vous dit noir, pensez blanc. Quand ça dure trop longtemps arrêtez de prendre des gants.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_056', 'content' => 'Quand tu prends dans la banque, n\'oublie pas le cyanure...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_057', 'content' => 'Qui a volé doit être pendu ! Qui a pendu doit être banni ! Qui est banni doit être dévoré ! Voilà ! Telle devrait être la loi !', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_058', 'content' => 'Qui craint le grand méchant loup ? Certainement pas le grand zombie...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_059', 'content' => 'Qui trahit est banni.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_060', 'content' => 'Qui veille bien ira loin...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_061', 'content' => 'Rira bien qui sera pendu le dernier.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_062', 'content' => 'Sept minutes. C\'est le temps qu\'il faut à un de ces zombies pour te bouffer et ne laisser que quelques osselets derrière lui. Neuf à la rigueur si tu gigotes.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_063', 'content' => 'Si on m\'avait dit que la pisse d\'un chat liquéfiait un zombie...', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_064', 'content' => 'Un jour, en creusant un peu dans ma cabane, j\'ai trouvé des bouts de gencives. Putréfiés.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_065', 'content' => 'Un jour, un pasteur est parti en croisade contre le Malin. Apparemment, ce dernier l\'était plus que lui. Il n\'est jamais revenu.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_066', 'content' => 'Un os par terre = un ami à terre', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_067', 'content' => 'Un seul moyen de se souvenir : lire une Chronique Citoyenne', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_068', 'content' => 'Une bonne goule est une... (propos inachevé pour cause de perte de langue inopinée)', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_069', 'content' => 'Vous avez assez réfléchi : fermez la porte. Vous n\'entendrez pas leurs cris depuis l\'intérieur.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_070', 'content' => 'Vous pensez que les autres pensent à vous ? Non, ils pensent : “OSEF !”. ', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_071', 'content' => 'Ça fait des heures qu\'ils hurlent derrière la porte, dehors. Mais maintenant qu\'il fait nuit, les insultes ont laissé place aux supplications.', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_072', 'content' => 'Être brave ou être mort. Quelle différence ?', 'author' => '', 'lang' => 'fr'],
        ['name' => 'fr_073', 'content' => '... Et elle cria toute la nuit, elle hurla même... Mais vous savez : on n\'oublie jamais.', 'author' => '', 'lang' => 'fr'],
    ];

    private function insertAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Quotes: ' . count(static::$quotes_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$quotes_data) );

       foreach(static::$quotes_data as $entry) {
            $entity = $this->entityManager->getRepository(Quote::class)->findOneBy(['name' => $entry['name']]);

            if($entity === null) {
                $entity = new Quote();
                $entity->setName($entry['name']);
            }

            $entity
                ->setAuthor($entry['author'])
                ->setContent($entry['content'])
                ->setLang($entry['lang']);

            $manager->persist($entity);
            $progress->advance();
        }
        $manager->flush();
        $progress->finish();
    }

    public function __construct(EntityManagerInterface $em) {
        $this->entityManager = $em;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Quotes Database</info>' );
        $output->writeln("");

        $this->insertAwards($manager, $output);
        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [ PictoFixtures::class ];
    }
}
