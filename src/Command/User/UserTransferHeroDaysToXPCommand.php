<?php

namespace App\Command\User;


use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RolePlayText;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Messages\Command\CommandMessage;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\Actions\User\UserPictoRollupAction;
use App\Service\CommandHelper;
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsCommand(
    name: 'app:user:migrate:herodays',
    description: 'Transfers old hero days to the new hero exp system'
)]
class UserTransferHeroDaysToXPCommand extends Command
{

    public function __construct(
        private readonly CommandHelper $helper,
        private readonly UserUnlockableService $unlockableService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to update a users hero exp.')

            ->addOption('user', null,InputOption::VALUE_REQUIRED, 'Restrict to a specific user ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userID = $input->getOption('user');

            $this->helper->leChunk($output, User::class, 10, $userID === null ? [] : ['id' => $userID], true, false, function(User $u) {

                $changed = false;
                if ($u->getHeroDaysSpent() > 0) {
                    $this->unlockableService->setLegacyHeroDaysSpent($u, false, $u->getHeroDaysSpent(), true);
                    $u->setHeroDaysSpent(0);
                    $changed = true;
                }

                if ($u->getBonusHeroDaysSpent() > 0) {
                    $this->unlockableService->setLegacyHeroDaysSpent($u, null, $u->getBonusHeroDaysSpent(), true);
                    $u->setBonusHeroDaysSpent(0);
                    $changed = true;
                }

                if ($u->getImportedHeroDaysSpent() > 0) {
                    $this->unlockableService->setLegacyHeroDaysSpent($u, true, $u->getImportedHeroDaysSpent(), true);
                    $u->setImportedHeroDaysSpent(0);
                    $changed = true;
                }

                return $changed;
            }, true);

        return 0;
    }
}
