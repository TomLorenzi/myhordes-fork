<?php


namespace App\Command\Utils;


use App\Entity\AttackSchedule;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\HeaderStat;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Enum\Configuration\MyHordesSetting;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\ConfMaster;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:utils:conclude-attack',
    description: 'Concludes a given attack.'
)]
class ConcludeAttackCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InvalidateTagsInAllPoolsAction $clearCache,
        private readonly ConfMaster $conf
    )
    {

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command concludes a scheduled attack.')

            ->addArgument('schedule', InputArgument::REQUIRED, 'Schedule ID.')

            ->addOption('failures', null, InputOption::VALUE_REQUIRED, 'Number of failed towns.')
        ;
        parent::configure();
    }


    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedule_id = (int)$input->getArgument('schedule');

        $s = $this->entityManager->getRepository(AttackSchedule::class)->find($schedule_id);
        if (!$s) throw new \Exception("Schedule ID $schedule_id not found.");

        if ($s->getStartedAt() === null) throw new \Exception("Schedule ID $schedule_id has not started yet.");
        if ($s->getCompleted() || $s->getCompletedAt() !== null) throw new \Exception("Schedule ID $schedule_id has already completed.");

        $this->entityManager->persist($s
            ->setCompleted(true)
            ->setCompletedAt( new DateTimeImmutable('now') )
            ->setFailures( (int)$input->getOption('failures') ?? 0 )
        );

        try {
            ($this->clearCache)('daily');
        } catch (\Throwable $e) {}

        $this->entityManager->flush();

        try {
            $criteria = new Criteria();
            $criteria->andWhere($criteria->expr()->neq('end', null));
            $criteria->orWhere($criteria->expr()->neq('importID', 0));
            $deadCitizenCount = $this->entityManager->getRepository(CitizenRankingProxy::class)->matching($criteria)->count() + $this->entityManager->getRepository(Citizen::class)->count(['alive' => 0]);

            $pictoKillZombies = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_killz_#00']);
            $zombiesKilled = $this->entityManager->getRepository(Picto::class)->countPicto($pictoKillZombies);

            $pictoCanibal = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_cannib_#00']);
            $cannibalismCount = $this->entityManager->getRepository(Picto::class)->countPicto($pictoCanibal);

            $this->entityManager->persist( (new HeaderStat())
                ->setTimestamp( new DateTime() )
                ->setKilledCitizens( $deadCitizenCount )
                ->setKilledZombies( $zombiesKilled )
                ->setCannibalismActs($cannibalismCount)
            );
        } catch (\Throwable $e) {}

        $datemod = $this->conf->getGlobalConf()->get(MyHordesSetting::NightlyAttackDateModifier);
        if ($datemod !== 'never') {

            $new_date = (new DateTime())->setTimestamp( $s->getTimestamp()->getTimestamp() )->modify($datemod);
            if ($new_date !== false && $new_date > $s->getTimestamp())
                $this->entityManager->persist( (new AttackSchedule())->setTimestamp( DateTimeImmutable::createFromMutable($new_date) ) );

        }

        $this->entityManager->flush();

        return 0;
    }
}
