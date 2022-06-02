<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

class RankingCommand extends Command
{
    protected static $defaultName = 'app:ranking';

    private EntityManagerInterface $entityManager;
    private CommandHelper $commandHelper;
    private UserHandler $userHandler;
    private GameFactory $gameFactory;

    public function __construct(EntityManagerInterface $em, CommandHelper $com, UserHandler $uh, GameFactory $gf)
    {
        $this->entityManager = $em;
        $this->commandHelper = $com;
        $this->userHandler = $uh;
        $this->gameFactory = $gf;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculates ranking rewards at the end of a season.')
            ->addArgument('SeasonNumber', InputArgument::OPTIONAL, 'The season number. Enter numeric value or c for current, l for latest.', 'c')
            ->addArgument('SeasonSubNumber', InputArgument::OPTIONAL, 'The season sub number. Enter numeric value or c for current, l for latest.', '')

            ->addOption('clear', null,InputOption::VALUE_NONE, 'Removes the ranking pictos for the selected season.')
            ->addOption('alpha', null,InputOption::VALUE_NONE, 'Instead of calculating a season ranking, disperse ranking pictos')
        ;
    }

    protected function execute_alpha(InputInterface $input, OutputInterface $output): int {

        $alpha_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_ripflash_#00');
        if (!$alpha_picto) return -1;

        $this->commandHelper->leChunk($output, User::class, 100, [], true, true, function (User $u) use (&$alpha_picto) {
            if ($this->userHandler->hasRole($u,'ROLE_USER') && !$this->userHandler->hasRole($u,'ROLE_DUMMY') && $u->getPastLifes()->count() >= 1) {
                if (empty(array_filter( $this->entityManager->getRepository(Picto::class)->findPictoByUserAndTown($u, null), fn(Picto $p) => $p->getPersisted() === 2 ))) {
                    $u->addPicto($p = (new Picto())
                        ->setCount(1)
                        ->setPrototype($alpha_picto)
                        ->setPersisted(2)
                        ->setDisabled(false)
                    );
                    $this->entityManager->persist($p);
                }
            }
        }, true, function() use (&$alpha_picto) { $alpha_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_ripflash_#00'); } );

        $this->commandHelper->leChunk($output, User::class, 100, [], true, true, function (User $u) {
            $this->userHandler->computePictoUnlocks($u);
        }, true );

        $this->commandHelper->leChunk($output, Town::class, 1, [], false, false, function(Town $t) {
            if (!$this->gameFactory->compactTown($t))
                $this->gameFactory->nullifyTown($t,true);
        }, true);

        return 0;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('alpha')) return $this->execute_alpha($input,$output);

        if (
            (!is_numeric($input->getArgument('SeasonNumber')) && !in_array($input->getArgument('SeasonNumber'), ['c','l'])) ||
            (!is_numeric($input->getArgument('SeasonSubNumber')) && !in_array($input->getArgument('SeasonNumber'), ['c','l','']))
        ) throw new Exception('Invalid selector');

        $clear = $input->getOption('clear');

        $season_number     = $input->getArgument('SeasonNumber');
        $season_sub_number = $input->getArgument('SeasonSubNumber');

        if ($season_sub_number === '') $season_sub_number = is_numeric($season_number) ? 'l' : $season_number;

        $current_season = $this->entityManager->getRepository(Season::class)->findOneBy(['current' => true]);
        $latest_season = $this->entityManager->getRepository(Season::class)->findLatest();

        if ($season_number === 'c' && $season_sub_number === 'c')
            $season = $current_season;
        elseif ($season_number === 'l' && $season_sub_number === 'l')  {
            $season = $latest_season;
        } else {

            if (($season_number === 'c' || $season_sub_number === 'c') && !$current_season) throw new Exception('No current season set. To select the alpha season, use "c c".');
            if (($season_number === 'l' || $season_sub_number === 'l') && !$latest_season)  throw new Exception('No seasons available. To select the alpha season, use "c c".');

            if ($season_number === 'c') $season_number = $current_season->getNumber();
            elseif ($season_number === 'l') $season_number = $latest_season->getNumber();

            if ($season_sub_number === 'c') {
                if ($season_number !== $current_season->getNumber()) throw new Exception('The sub season selector "c" is only valid if the current season is covered by the season selector.');
                $season = $current_season;
            } elseif ($season_sub_number === 'l') {
                $seasons = $this->entityManager->getRepository(Season::class)->findBy(['number' => $season_number], ['subNumber' => 'DESC'], 1);
                $season = !empty($seasons) ? $seasons[0] : null;
            } else $season = $this->entityManager->getRepository(Season::class)->findOneBy(['number' => $season_number, 'subNumber' => $season_sub_number]);

            if (!$season) throw new Exception('The specified season was not found.');
        }

        if ($season) $output->writeln("Selected season: <info>{$season->getNumber()}.{$season->getSubNumber()}</info>");
        else $output->writeln("Selected season: <info>ALPHA SEASON</info>");

        $all_town_types = $this->entityManager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC']);

        $io = new SymfonyStyle($input, $output);
        $io->title("Ranking overview");

        $citizen_ranking = [];

        foreach ($all_town_types as $type) {

            if (!$clear) $io->section("Ranking for town type <info>{$type->getLabel()}</info>");

            /** @var TownRankingProxy[] $towns */
            $towns = $clear ? [] : $this->entityManager->getRepository(TownRankingProxy::class)->findTopOfSeason($season, $type);

            $data = [];
            foreach ($towns as $place => $town) {

                $citizens = $town->getCitizens()->getValues();
                usort($citizens, fn(CitizenRankingProxy $a, CitizenRankingProxy $b) => $a->getEnd() <=> $b->getEnd());

                /**
                 * @var int $k Position in the array
                 * @var CitizenRankingProxy $citizen The citizen entity
                 */
                foreach ($citizens as $k => $citizen) {

                    if ($k < ($town->getPopulation() / 8) && $citizen->getDay() < 5) continue;

                    if ($citizen->hasDisableFlag(CitizenRankingProxy::DISABLE_RANKING)) continue;

                    if (!isset($citizen_ranking[$citizen->getUser()->getId()]))
                        $citizen_ranking[$citizen->getUser()->getId()] = [[],[],$citizen->getUser()];

                    if ($place === 0)
                        $citizen_ranking[$citizen->getUser()->getId()][0][] = $town;
                    else $citizen_ranking[$citizen->getUser()->getId()][1][] = $town;
                }

                $data[] = [
                    $place + 1, $town->getId(), $town->getName(), $town->getScore(), $town->getDays()
                ];
            }

            if (!$clear) $io->table(['#', 'ID', 'Name', 'Score', 'Days'], $data);
        }

        $io->section("Citizen Results");

        $ranking_picto     = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_winbas_#00');
        $top_ranking_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_wintop_#00');

        /** @var Picto[] $all_rank_pictos */
        $all_rank_pictos  = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => [$ranking_picto,$top_ranking_picto]]), fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);
        foreach ($all_rank_pictos as $rank_picto)
            if (!isset($citizen_ranking[$rank_picto->getUser()->getId()]))
                $citizen_ranking[$rank_picto->getUser()->getId()] = [[],[],$rank_picto->getUser()];

        usort($citizen_ranking, fn(array $a, array $b) => count($b[0]) <=> count($a[0]) ?: count($b[1]) <=> count($a[1]) ?: $a[2]->getId() <=> $b[2]->getId() );
        $data = [];

        foreach ($citizen_ranking as $k => $citizen_ranking_entry) {
            /** @var Picto[] $existing_top_pictos */
            $existing_top_pictos  = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => $top_ranking_picto, 'user' => $citizen_ranking_entry[2]]), fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);

            /** @var Picto[] $existing_rank_pictos */
            $existing_rank_pictos = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => $ranking_picto, 'user' => $citizen_ranking_entry[2]]),     fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);

            $minus = [0,0];
            $plus  = [0,0];
            $already = [[],[]];

            foreach ($existing_top_pictos as $top_picto) {
                if ($top_picto->getPersisted() !== 2 || !in_array($top_picto->getTownEntry(), $citizen_ranking_entry[0])) {
                    $minus[0] += $top_picto->getCount();
                    $this->entityManager->remove($top_picto);
                } elseif ($top_picto->getCount() > 1) {
                    $minus[0] += ($top_picto->getCount()-1);
                    $this->entityManager->persist($top_picto->setCount(1));
                    $already[0][] = $top_picto->getTownEntry();
                } else $already[0][] = $top_picto->getTownEntry();
            }

            foreach ($existing_rank_pictos as $rank_picto) {
                if ($rank_picto->getPersisted() !== 2 || !in_array($rank_picto->getTownEntry(), $citizen_ranking_entry[1])) {
                    $minus[1] += $rank_picto->getCount();
                    $this->entityManager->remove($rank_picto);
                } elseif ($rank_picto->getCount() > 1) {
                    $minus[1] += ($rank_picto->getCount()-1);
                    $this->entityManager->persist($rank_picto->setCount(1));
                    $already[1][] = $rank_picto->getTownEntry();
                } else $already[1][] = $rank_picto->getTownEntry();
            }

            foreach ($citizen_ranking_entry[0] as $townEntry)
                if (!in_array($townEntry,$already[0])) {
                    $this->entityManager->persist( (new Picto())->setUser( $citizen_ranking_entry[2] )->setCount( 1 )->setPersisted( 2 )->setTownEntry( $townEntry )->setPrototype( $top_ranking_picto ) );
                    $plus[0]++;
                }

            foreach ($citizen_ranking_entry[1] as $townEntry)
                if (!in_array($townEntry,$already[1])) {
                    $this->entityManager->persist( (new Picto())->setUser( $citizen_ranking_entry[2] )->setCount( 1 )->setPersisted( 2 )->setTownEntry( $townEntry )->setPrototype( $ranking_picto ) );
                    $plus[1]++;
                }

            $data[] = [ $k + 1, $citizen_ranking_entry[2]->getId(), $citizen_ranking_entry[2]->getName(), count($citizen_ranking_entry[0]), "-{$minus[0]} / +{$plus[0]}", count($citizen_ranking_entry[1]), "-{$minus[1]} / +{$plus[1]}" ];
        }
        $io->table(['#', 'ID', 'User', 'Top Towns', 'Top Towns Change', 'Ranked Towns', 'Ranked Towns Change'], $data);

        $this->entityManager->flush();

        return 1;
    }
}
