<?php


namespace App\Command\Season;

use App\Entity\CitizenRankingProxy;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\CommandHelper;
use App\Service\CrowService;
use App\Service\GameFactory;
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

#[AsCommand(
    name: 'app:season:manage',
    description: 'Calculates ranking rewards at the end of a season.'
)]
class SeasonCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('SeasonNumber', InputArgument::REQUIRED, 'The season number.')
            ->addArgument('SeasonSubNumber', InputArgument::OPTIONAL, 'The season sub number.', '0')

            ->addOption('make', null, InputOption::VALUE_NONE, 'Create the given season.')
            ->addOption('activate', null,InputOption::VALUE_NONE, 'Activate the given season.')
        ;
    }


    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $number = (int)$input->getArgument('SeasonNumber');
        $sub_number = (int)$input->getArgument('SeasonSubNumber');

        $make = $input->getOption('make');
        $activate = $input->getOption('activate');

        $existing_season = $this->entityManager->getRepository(Season::class)->findOneBy(['number' => $number, 'subNumber' => $sub_number]);
        if ($existing_season && $make) throw new Exception("Season {$number}.{$sub_number} already exists.");
        if (!$existing_season && !$make) throw new Exception("Season {$number}.{$sub_number} does not exists.");

        if ($make) {
            $existing_season = (new Season())->setNumber($number)->setSubNumber($sub_number)->setCurrent(false);

            $this->entityManager->persist( $existing_season );
            $this->entityManager->flush();
            $output->writeln("Created <fg=blue>Season {$number}.{$sub_number}</>");
        }

        if ($activate && $existing_season->getCurrent()) $output->writeln("<fg=yellow>Season {$number}.{$sub_number} is already active.</>");
        elseif ($activate) {
            /** @var Season $current_season */
            $current_season = $this->entityManager->getRepository(Season::class)->findOneBy(['current' => true]);
            $current_season?->setCurrent(false);
            if ($current_season) $this->entityManager->persist($current_season);

            $existing_season->setCurrent(true);
            $this->entityManager->persist( $existing_season );
            $this->entityManager->flush();

            if ($current_season) $output->writeln("<fg=red>Disabled</> <fg=blue>Season {$current_season->getNumber()}.{$current_season->getSubNumber()}</>");
            $output->writeln("<fg=green>Enabled</> <fg=blue>Season {$existing_season->getNumber()}.{$existing_season->getSubNumber()}</>");
        }

        return 0;
    }
}
