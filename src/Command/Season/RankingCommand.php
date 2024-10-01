<?php


namespace App\Command\Season;

use App\Entity\CitizenRankingProxy;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\SeasonRankingRange;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\CommandHelper;
use App\Service\CrowService;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\User\PictoService;
use App\Service\User\UserCapabilityService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// Note: You can export a ranking page from the original game using this JS snipped:
// [...document.querySelectorAll('.table.mapRanking tr')].filter(e => parseInt(e.querySelector('td.rank')?.textContent ?? 100) <= 35).map(e => e.querySelector('td.name>a').getAttribute('href').match(/dmid=(\d+)/)[1]).join(',')

#[AsCommand(
    name: 'app:season:ranking',
    description: 'Calculates ranking rewards at the end of a season.'
)]
class RankingCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandHelper $commandHelper,
        private readonly UserCapabilityService $userCapabilityService,
        private readonly GameFactory $gameFactory,
        private readonly CrowService $crowService,
        private readonly EventProxyService $proxy,
        private readonly PictoService $pictoService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('SeasonNumber', InputArgument::OPTIONAL, 'The season number. Enter numeric value or c for current, l for latest.', 'c')
            ->addArgument('SeasonSubNumber', InputArgument::OPTIONAL, 'The season sub number. Enter numeric value or c for current, l for latest.', '')

            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Auto-confirm')
            ->addOption('clear', null,InputOption::VALUE_NONE, 'Removes the ranking pictos for the selected season.')
            ->addOption('alpha', null,InputOption::VALUE_NONE, 'Instead of calculating a season ranking, disperse ranking pictos')
            ->addOption('import', null,InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Imports a ranking instead of using our own database. Use form "<type>:<lang>:<id>,<id>,..."', [])
        ;
    }

    protected function execute_alpha(InputInterface $input, OutputInterface $output): int {

        $alpha_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_ripflash_#00');
        if (!$alpha_picto) return -1;

        $this->commandHelper->leChunk($output, User::class, 100, [], true, true, function (User $u) use (&$alpha_picto) {
            if ($this->userCapabilityService->hasRole($u,'ROLE_USER') && !$this->userCapabilityService->hasRole($u,'ROLE_DUMMY') && $u->getPastLifes()->count() >= 1) {
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
            $this->pictoService->computePictoUnlocks($u);
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

        /** @var Season $season */
        if ($season) $output->writeln("Selected season: <info>{$season->getNumber()}.{$season->getSubNumber()}</info>");
        else $output->writeln("Selected season: <info>ALPHA SEASON</info>");

        /** @var Season $upcoming_season */
        $upcoming_season = $this->entityManager->getRepository(Season::class)->findNext($season);
        if ($upcoming_season) $output->writeln("Upcoming season: <info>{$upcoming_season->getNumber()}.{$upcoming_season->getSubNumber()}</info>");
        else $output->writeln("<fg=red>Warning: No upcoming season found!</>");

        $seasonal_merge = $upcoming_season && $upcoming_season->getNumber() > 0 && $season->getNumber() === 0 && !$clear;
        if ($seasonal_merge) $output->writeln("The upcoming season seems to be a <fg=blue>seasonal junction</> as it may be preceded by several different seasons.\n<fg=yellow>Therefore, feature unlocks for the upcoming season will be calculated in additive mode.</>");

        $all_town_types = (empty( $input->getOption('import') ))
            ? $this->entityManager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC'])
            : [];

        $preset_ranking = [];
        $import_lang = null;
        foreach ( $input->getOption('import') as $import ) {

            $base = explode(':', $import);
            if (count($base) !== 3) throw new Exception("Invalid import format ('$import')");

            if ($import_lang === null) $import_lang = $base[1];
            elseif ($import_lang !== $base[1]) throw new Exception("Encountered unexpected language '{$base[1]}'.");

            /** @var TownClass $town_type */
            $town_type = $this->entityManager->getRepository(TownClass::class)->findOneBy(['ranked' => true, 'name' => $base[0]]);
            if (!$town_type) throw new Exception("Invalid town type '{$base[0]}'.");

            $all_town_types[] = $town_type;
            $preset_ranking[$town_type->getName()] =
                array_slice(array_filter( array_map(
                                  fn(?TownRankingProxy $town) => $town ? (!$town->getDisabled() && !$town->hasDisableFlag( TownRankingProxy::DISABLE_RANKING ) ? $town : false) : null,
                                  array_map( fn(int $id) => $this->entityManager->getRepository(TownRankingProxy::class)->findOneBy(['baseID' => $id, 'imported' => true, 'language' => $base[1]]), explode(',', $base[2]))
              ), fn($t) => $t !== false ), 0, $town_type->getRankingLow());

            foreach ($preset_ranking[$town_type->getName()] as $town) {
                /** @var TownRankingProxy $town */
                if ($town && $town->getSeason() !== $season) throw new Exception("Town #{$town->getId()} '{$town->getName()}' is from season {$town->getSeason()?->getNumber()}.{$town->getSeason()?->getSubNumber()}!");
            }
        }

        usort( $all_town_types, fn(TownClass $a, TownClass $b) => $a->getOrderBy() <=> $b->getOrderBy() );

        $io = new SymfonyStyle($input, $output);
        $io->title("Ranking overview");

        $citizen_ranking = [];

        foreach ($all_town_types as $type) {

            if (!$clear) {
                $io->section("Ranking for town type <info>{$type->getLabel()}</info>");
                if ($season) {
                    $range = $this->entityManager->getRepository(SeasonRankingRange::class)->findOneBy(['season' => $season, 'type' => $type]) ?? (new SeasonRankingRange())
                        ->setSeason($season)->setType($type);
                    $this->entityManager->persist( $range->setTop( $type->getRankingTop() )->setMid( $type->getRankingMid() )->setLow( $type->getRankingLow() ) );
                }
            } elseif ($season) {
                $range = $this->entityManager->getRepository(SeasonRankingRange::class)->findOneBy(['season' => $season, 'type' => $type]);
                if ($range) $this->entityManager->remove( $range );
            }

            /** @var TownRankingProxy[] $towns */
            $towns = $clear ? [] : $preset_ranking[$type->getName()] ?? $this->entityManager->getRepository(TownRankingProxy::class)->findTopOfSeason($season, $type);

            $data = [];
            foreach ($towns as $place => $town) {

                $data[] = [
                    $place + 1, $town?->getId() ?? '-', $town?->getName() ?? '-', $town?->getScore() ?? '-', $town?->getDays() ?? '-'
                ];
                if (!$town) continue;

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
                        $citizen_ranking[$citizen->getUser()->getId()] = [[],[],[],$citizen->getUser(),[],[]];

                    if ($place < $type->getRankingTop())
                        $citizen_ranking[$citizen->getUser()->getId()][0][] = $town;
                    if ($place < $type->getRankingMid())
                        $citizen_ranking[$citizen->getUser()->getId()][1][] = $town;
                    if ($place < $type->getRankingLow())
                        $citizen_ranking[$citizen->getUser()->getId()][2][] = $town;
                }
            }

            if (!$clear) $io->table(['#', 'ID', 'Name', 'Score', 'Days'], $data);
        }

        $award_glory            = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneByName('f_glory');
        $award_seasonal_glory   = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneByName('f_glory_temp');
        //$award_alarm            = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneByName('f_alarm');

        $io->section("Global Results");
        $global_citizen_list = [];

        /** @var Season $this_season */
        $top_ranking_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_wintop_#00');
        if (!$clear)
            foreach ($this->entityManager->getRepository(Picto::class)->findBy(['prototype' => $top_ranking_picto, 'persisted' => 2, 'disabled' => false]) as $picto)
                $global_citizen_list[$picto->getUser()->getId()] ??= [$picto->getUser(), 1, 0, null];

        /** @var FeatureUnlock[] $existing_glory */
        $existing_glory = $this->entityManager->getRepository(FeatureUnlock::class)->findBy(['prototype' => $award_glory, 'expirationMode' => FeatureUnlock::FeatureExpirationNone]);
        foreach ($existing_glory as $glory) {
            if ($glory->getSeason() === null) continue;
            if (isset($global_citizen_list[$glory->getUser()->getId()])) {
                $global_citizen_list[$glory->getUser()->getId()][2] = 1;
                $global_citizen_list[$glory->getUser()->getId()][3] = $glory;
            } else $global_citizen_list[$glory->getUser()->getId()] = [$glory->getUser(), 0, 1, $glory];
        }

        foreach ($global_citizen_list as [$user, $needs, $has, $existing]) {
            if ($has > $needs && $existing) $this->entityManager->remove($existing);
            elseif ($has < $needs && !$existing) $this->entityManager->persist((new FeatureUnlock())
                ->setUser( $user )
                ->setSeason( $upcoming_season )
                ->setPrototype( $award_glory )
                ->setExpirationMode( FeatureUnlock::FeatureExpirationNone )
            );
        }

        $io->table(['ID', 'Name', 'Should have Glory', 'Has Glory', 'Change'], array_map(function($entry) {
            return [
                $entry[0]->getId(),
                $entry[0]->getName(),
                $entry[1],
                $entry[2],
                $entry[1] - $entry[2],
            ];
        }, $global_citizen_list));

        $io->section("Citizen Results");

        $part_picto     = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_winthi_#00');
        $ranking_picto     = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_winbas_#00');
        $top_ranking_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_wintop_#00');

        /** @var Picto[] $all_rank_pictos */
        $all_rank_pictos  = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => [$part_picto,$ranking_picto,$top_ranking_picto]]), fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);
        /** @var FeatureUnlock[] $all_rank_rewards */
        $all_rank_rewards = ($upcoming_season && !$seasonal_merge)
            ? $this->entityManager->getRepository(FeatureUnlock::class)->findBy(['prototype' => [$award_seasonal_glory/*,$award_alarm*/], 'expirationMode' => FeatureUnlock::FeatureExpirationSeason, 'season' => $upcoming_season])
            : [];

        foreach ($all_rank_pictos as $rank_picto)
            if (!isset($citizen_ranking[$rank_picto->getUser()->getId()]))
                $citizen_ranking[$rank_picto->getUser()->getId()] = [[],[],[],$rank_picto->getUser(),[],[]];
        foreach ($all_rank_rewards as $rank_reward)
            if (!isset($citizen_ranking[$rank_reward->getUser()->getId()]))
                $citizen_ranking[$rank_reward->getUser()->getId()] = [[],[],[],$rank_reward->getUser(),[],[]];

        usort($citizen_ranking, fn(array $a, array $b) => count($b[0]) <=> count($a[0]) ?: count($b[1]) <=> count($a[1]) ?: count($b[2]) <=> count($a[2]) ?: $a[3]->getId() <=> $b[3]->getId() );

        $message_cache = [];

        $data = [];
        foreach ($citizen_ranking as $k => $citizen_ranking_entry) {
            /** @var Picto[] $existing_top_pictos */
            $existing_top_pictos  = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => $top_ranking_picto, 'user' => $citizen_ranking_entry[3]]), fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);

            /** @var Picto[] $existing_rank_pictos */
            $existing_rank_pictos = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => $ranking_picto, 'user' => $citizen_ranking_entry[3]]),     fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);

            /** @var Picto[] $existing_part_pictos */
            $existing_part_pictos = array_filter( $this->entityManager->getRepository(Picto::class)->findBy(['prototype' => $part_picto, 'user' => $citizen_ranking_entry[3]]),     fn(Picto $p) => $p->getTownEntry() !== null && $p->getTownEntry()->getSeason() === $season);


            /** @var FeatureUnlock[] $existing_alarms */
            /*$existing_alarms = $upcoming_season
                ? $this->entityManager->getRepository(FeatureUnlock::class)->findBy(['prototype' => $award_alarm, 'user' => $citizen_ranking_entry[3], 'expirationMode' => FeatureUnlock::FeatureExpirationSeason, 'season' => $upcoming_season])
                : [];*/

            /** @var FeatureUnlock[] $existing_glory */
            $existing_glory = $upcoming_season
                ? $this->entityManager->getRepository(FeatureUnlock::class)->findBy(['prototype' => $award_seasonal_glory, 'user' => $citizen_ranking_entry[3], 'expirationMode' => FeatureUnlock::FeatureExpirationSeason, 'season' => $upcoming_season])
                : [];

            $minus = [0,0,0,0,0,0];
            $plus  = [0,0,0,0,0,0];
            $already = [[],[],[],false,false,false];

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

            foreach ($existing_part_pictos as $existing_part_picto) {
                if ($existing_part_picto->getPersisted() !== 2 || !in_array($existing_part_picto->getTownEntry(), $citizen_ranking_entry[2])) {
                    $minus[2] += $existing_part_picto->getCount();
                    $this->entityManager->remove($existing_part_picto);
                } elseif ($existing_part_picto->getCount() > 1) {
                    $minus[2] += ($existing_part_picto->getCount()-1);
                    $this->entityManager->persist($existing_part_picto->setCount(1));
                    $already[2][] = $existing_part_picto->getTownEntry();
                } else $already[2][] = $existing_part_picto->getTownEntry();
            }

            /*if ($upcoming_season)
                foreach ($existing_alarms as $n => $alarm) {
                    if ((empty($citizen_ranking_entry[0]) && !$seasonal_merge) || $n > 0) {
                        $minus[3]++;
                        $this->entityManager->remove($alarm);
                    } else $already[3] = true;
                }*/

            if ($upcoming_season)
                foreach ($existing_glory as $n => $glory) {
                    if ((empty($citizen_ranking_entry[0]) && empty($citizen_ranking_entry[1]) && !$seasonal_merge) || $n > 0) {
                        $minus[4]++;
                        $this->entityManager->remove($glory);
                    } else $already[4] = true;
                }

            foreach ($citizen_ranking_entry[0] as $townEntry)
                if (!in_array($townEntry,$already[0])) {
                    $this->entityManager->persist( (new Picto())->setUser( $citizen_ranking_entry[3] )->setCount( 1 )->setPersisted( 2 )->setTownEntry( $townEntry )->setPrototype( $top_ranking_picto ) );
                    $plus[0]++;
                }

            foreach ($citizen_ranking_entry[1] as $townEntry)
                if (!in_array($townEntry,$already[1])) {
                    $this->entityManager->persist( (new Picto())->setUser( $citizen_ranking_entry[3] )->setCount( 1 )->setPersisted( 2 )->setTownEntry( $townEntry )->setPrototype( $ranking_picto ) );
                    $plus[1]++;
                }

            foreach ($citizen_ranking_entry[2] as $townEntry)
                if (!in_array($townEntry,$already[2])) {
                    $this->entityManager->persist( (new Picto())->setUser( $citizen_ranking_entry[3] )->setCount( 1 )->setPersisted( 2 )->setTownEntry( $townEntry )->setPrototype( $part_picto ) );
                    $plus[2]++;
                }

            /*if (!empty($citizen_ranking_entry[0]) && !$already[3] && $upcoming_season) {
                $this->entityManager->persist((new FeatureUnlock())->setPrototype($award_alarm)->setUser($citizen_ranking_entry[3])->setExpirationMode(FeatureUnlock::FeatureExpirationSeason)->setSeason($upcoming_season));
                $plus[3]++;
            }*/
            if ((!empty($citizen_ranking_entry[0])) && !$already[4] && $upcoming_season) {
                $this->entityManager->persist((new FeatureUnlock())->setPrototype($award_seasonal_glory)->setUser($citizen_ranking_entry[3])->setExpirationMode(FeatureUnlock::FeatureExpirationSeason)->setSeason($upcoming_season));
                $plus[4]++;
            }

            if (isset($global_citizen_list[$citizen_ranking_entry[3]->getId()])) {
                $plus[5] = max(0, $global_citizen_list[$citizen_ranking_entry[3]->getId()][1] - $global_citizen_list[$citizen_ranking_entry[3]->getId()][2]);
                $minus[5] = max(0, $global_citizen_list[$citizen_ranking_entry[3]->getId()][2] - $global_citizen_list[$citizen_ranking_entry[3]->getId()][1]);
                $already[5] = !!$global_citizen_list[$citizen_ranking_entry[3]->getId()][3];
            }

            $data[] = [
                $k + 1,
                $citizen_ranking_entry[3]->getId(), $citizen_ranking_entry[3]->getName(),
                count($citizen_ranking_entry[0]), "-{$minus[0]} / +{$plus[0]}",
                count($citizen_ranking_entry[1]), "-{$minus[1]} / +{$plus[1]}",
                count($citizen_ranking_entry[2]), "-{$minus[2]} / +{$plus[2]}",
                empty($citizen_ranking_entry[0]) ? 0 : 1, "-{$minus[3]} / +{$plus[3]}",
                (empty($citizen_ranking_entry[0]) && empty($citizen_ranking_entry[1])) ? 0 : 1, "-{$minus[4]} / +{$plus[4]}",
                $already[5] ? 1 : 0, "-{$minus[5]} / +{$plus[5]}",

            ];

            $message_cache[] = [$citizen_ranking_entry[3]->getId(), array_map( fn(int $plus, int $minus) => $plus - $minus, $plus, $minus ) ];
        }
        $io->table(['#', 'ID', 'User', 'Top Towns', 'Top Towns Change', 'Ranked Towns', 'Ranked Towns Change', 'Part. Towns', 'Part. Towns Change', 'Alarm', 'Alarm Change', 'Glory', 'Glory Change', 'Global Glory', 'Global Glory Change'], $data);

        if ($this->commandHelper->interactiveConfirm( $this->getHelper('question'), $input, $output )) {

            $io->write('Persisting... ');
            $this->entityManager->flush();
            $io->writeln('<fg=green>OK!</>');

            $this->entityManager->clear();
            $io->writeln('Sending crow messages and calculating title unlocks...');
            $sid = $season?->getId();
            foreach ($io->createProgressBar()->iterate($message_cache) as $citizen_ranking_entry) {

                $user = $this->entityManager->getRepository(User::class)->find( $citizen_ranking_entry[0] );

                $season = $sid ? $this->entityManager->getRepository(Season::class)->find($sid) : null;
                $part_picto        = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_winthi_#00');
                $ranking_picto     = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_winbas_#00');
                $top_ranking_picto = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName('r_wintop_#00');

                $award_glory  = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneByName('f_glory');
                $award_seasonal_glory  = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneByName('f_glory_temp');
                //$award_alarm           = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneByName('f_alarm');

                $features = [];
                //if ($citizen_ranking_entry[1][3] > 0) $features[] = $award_alarm;
                if ($citizen_ranking_entry[1][4] > 0) $features[] = $award_seasonal_glory;
                if ($citizen_ranking_entry[1][5] > 0) $features[] = $award_glory;

                if (array_reduce( $citizen_ranking_entry[1], fn(int $carry, int $value) => max( $carry, $value ), 0 ) > 0)
                    $this->entityManager->persist( $this->crowService->createPM_seasonalRewards(
                        $this->entityManager->getRepository(User::class)->find( $citizen_ranking_entry[0] ),
                        [
                            [$top_ranking_picto, $citizen_ranking_entry[1][0]],
                            [$ranking_picto, $citizen_ranking_entry[1][1]],
                            [$part_picto, $citizen_ranking_entry[1][2]],
                        ],
                        $features,
                        $import_lang,
                        $season?->getNumber() ?: 0
                    ) );

                $this->entityManager->flush();
                $this->proxy->pictosPersisted( $user, $season );

                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $io->writeln(' <fg=green>Complete!</>');
        }


        return 0;
    }
}
