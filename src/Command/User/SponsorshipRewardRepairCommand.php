<?php


namespace App\Command\User;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Thread;
use App\Entity\UserGroup;
use App\Entity\UserSponsorship;
use App\Enum\Configuration\TownSetting;
use App\Enum\HeroXPType;
use App\Kernel;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use App\Service\User\UserUnlockableService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:user:sponsorship-repair',
    description: 'Ensures all sponsorship boni are applied correctly'
)]
class SponsorshipRewardRepairCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandHelper          $helper,
        private readonly UserUnlockableService  $unlockableService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Ensures all sponsorship boni are applied correctly')
            ->addArgument('UserId', InputArgument::OPTIONAL, 'The user ID')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->helper->leChunk(
            $output,
            UserSponsorship::class,
            5,
            $input->getArgument('UserId') ? ['sponsor' => (int)$input->getArgument('UserId')] : [],
            true,
            false,
            function(UserSponsorship $s) {
                if ($s->isSeasonalPayout()) return false;

                $paid_hxp = $this->unlockableService->hasRecordedHeroicExperienceFor($s->getUser(), template: 'hxp_debit_base', season: true, total: $total);
                if ($paid_hxp && $total < 0) {
                    if (!$s->isPayout()) {
                        $success = $this->unlockableService->recordHeroicExperience($s->getSponsor(), HeroXPType::Global, 10, 'hxp_ref_first', variables: [
                            'user' => $s->getUser()->getId()
                        ], season:                                                  true);
                        $s->setPayout($success)->setSeasonalPayout($success);
                    } else {
                        $success = $this->unlockableService->recordHeroicExperience($s->getSponsor(), HeroXPType::Global, 2, 'hxp_ref_repeat', variables: [
                            'user' => $s->getUser()->getId()
                        ], season:                                                  true);
                        $s->setSeasonalPayout($success);
                    }
                }

                return true;
            }
        );

        return 0;
    }
}
