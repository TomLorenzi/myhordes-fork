<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\User;
use App\Enum\Configuration\CitizenProperties;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'app:citizen',
    description: 'Manipulates and lists information about a single citizen.'
)]
class CitizenInspectorCommand extends LanguageCommand
{
    private EntityManagerInterface $entityManager;
    private ItemFactory $itemFactory;
    private InventoryHandler $inventoryHandler;
    private CitizenHandler $citizenHandler;
    private UserHandler $userHandler;

    public function __construct(EntityManagerInterface $em, ItemFactory $if, InventoryHandler $ih, CitizenHandler $ch, CommandHelper $comh, UserHandler $uh)
    {
        $this->entityManager = $em;
        $this->inventoryHandler = $ih;
        $this->itemFactory = $if;
        $this->citizenHandler = $ch;
        $this->helper = $comh;
        $this->userHandler = $uh;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you work on single citizen.')
            ->addArgument('CitizenID', InputArgument::REQUIRED, 'The citizen ID')

            ->addOption('set-ap', 'a',InputOption::VALUE_REQUIRED, 'Sets the current AP.', -1)
            ->addOption('set-pm', 'm',InputOption::VALUE_REQUIRED, 'Sets the current PM.', -1)
            ->addOption('set-cp', 'c',InputOption::VALUE_REQUIRED, 'Sets the current CP.', -1)

            ->addOption('add-status','s',InputOption::VALUE_REQUIRED, 'Adds a new status.', '')
            ->addOption('remove-status',null,InputOption::VALUE_REQUIRED, 'Removes an existing status.', '')

            ->addOption('add-role','r',InputOption::VALUE_REQUIRED, 'Adds a new role.', '')
            ->addOption('remove-role',null,InputOption::VALUE_REQUIRED, 'Removes an existing role.', '')

            ->addOption('set-banned', null, InputOption::VALUE_OPTIONAL, 'Bans a citizen', '')

            ->addOption('set-hunger', null, InputOption::VALUE_REQUIRED, 'Sets the ghoul hunger.', '')
        ;
        parent::configure();
    }

    protected function info(Citizen &$citizen, OutputInterface $output): int {
        $output->writeln("<info>{$citizen->getUser()->getUsername()}</info> is a citizen of '<info>{$citizen->getTown()->getName()}</info>'.");

        $output->writeln('<comment>Citizen info</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['Active?', 'Alive?', 'Banished?', 'UID', 'TID', 'InvID', 'HomeID', 'HomeInvID', 'AP', 'Status', 'Roles'] );

        $table->addRow([
            (int)$citizen->getActive(),
            (int)$citizen->getAlive(),
            (int)$citizen->getBanished(),
            (int)$citizen->getUser()->getId(),
            (int)$citizen->getTown()->getId(),
            (int)$citizen->getInventory()->getId(),
            (int)$citizen->getHome()->getId(),
            (int)$citizen->getHome()->getChest()->getId(),
            (int)$citizen->getAp(),
            implode("\n", array_map( function(CitizenStatus $s): string { return $s->getLabel(); }, $citizen->getStatus()->getValues() ) ),
            implode("\n", array_map( function(CitizenRole $r): string { return $r->getLabel(); }, $citizen->getRoles()->getValues() ) )
        ]);
        $table->render();

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Citizen $citizen */
        $citizen = $this->helper->resolve_string($input->getArgument('CitizenID'), Citizen::class, 'Citizen', $this->getHelper('question'), $input, $output);

        $updated = false;

        if (!$citizen) {
            $output->writeln("<error>The selected citizen could not be found.</error>");
            return 1;
        }

        $set_ap = $input->getOption('set-ap');
        if ($set_ap >= 0) {
            $citizen->setAp( $set_ap );
            $updated = true;
            if($set_ap > 0) {
                $this->citizenHandler->removeStatus($citizen, "tired");
            } else {
                $this->citizenHandler->inflictStatus($citizen, "tired");
            }
        }

        $set_pm = $input->getOption('set-pm');
        if ($set_pm >= 0 && $citizen->hasRole('shaman')) {
            $citizen->setPm( $set_pm );
            $updated = true;
        }

        $set_cp = $input->getOption('set-cp');
        if ($set_cp >= 0 && $citizen->getProfession()->getName() == "tech") {
            $citizen->setBp( $set_cp );
            $updated = true;
        }

        $set_hunger = $input->getOption('set-hunger');
        if ($set_hunger !== '' && $set_hunger >= 0) {
            $citizen->setGhulHunger( $set_hunger );
            $updated = true;
        }

        if (($ban = $input->getOption('set-banned')) !== '') {
            $citizen->setBanished($ban);
            if ($ban && $citizen->getTown()->getDay() >= 3)
                foreach ($citizen->property( CitizenProperties::RevengeItems ) as $item)
                    $this->inventoryHandler->forceMoveItem( $citizen->getInventory(), $this->itemFactory->createItem( $item ) );

            $updated = true;
        }

        if ($new_status = $input->getOption('add-status')) {

            /** @var CitizenStatus $status */
            $status = $this->helper->resolve_string($new_status, CitizenStatus::class, 'NewStatus', $this->getHelper('question'), $input, $output);
            if (!$status) {
                $output->writeln("<error>The selected status could not be found.</error>");
                return 1;
            }

            $output->writeln( "Adding status '<info>{$status->getName()}</info>'.\n" );
            $citizen->addStatus( $status );

            $updated = true;
        }

        if ($rem_status = $input->getOption('remove-status')) {

            /** @var CitizenStatus $status */
            $status = $this->helper->resolve_string($rem_status, CitizenStatus::class, 'RemoveStatus', $this->getHelper('question'), $input, $output);
            if (!$status) {
                $output->writeln("<error>The selected status could not be found.</error>");
                return 1;
            }

            $output->writeln( "Removing status '<info>{$status->getName()}</info>'.\n" );

            if(in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] ))
                $this->citizenHandler->healWound($citizen);
            else
                $citizen->removeStatus( $status );

            $updated = true;
        }

        if ($new_role = $input->getOption('add-role')) {

            /** @var CitizenRole $role */
            $role = $this->helper->resolve_string($new_role, CitizenRole::class, 'NewRole', $this->getHelper('question'), $input, $output);
            if (!$role) {
                $output->writeln("<error>The selected role could not be found.</error>");
                return 1;
            }

            $output->writeln( "Adding role '<info>{$role->getName()}</info>'.\n" );
            $citizen->addRole( $role );

            if($role->getName() === 'shaman') {
                $status = $this->entityManager->getRepository(CitizenStatus::class)->findOneByName("tg_shaman_immune");
                $citizen->addStatus( $status );
            }

            $updated = true;
        }

        if ($rem_role = $input->getOption('remove-role')) {

            /** @var CitizenRole $role */
            $role = $this->helper->resolve_string($rem_role, CitizenRole::class, 'RemoveRole', $this->getHelper('question'), $input, $output);

            if (!$role) {
                $output->writeln("<error>The selected role could not be found.</error>");
                return 1;
            }

            $output->writeln( "Removing role '<info>" .$this->translate($role->getName(), "game") . "</info>'.\n" );
            $citizen->removeRole( $role );

            if($role->getName() === 'shaman') {
                $status = $this->entityManager->getRepository(CitizenStatus::class)->findOneByName("tg_shaman_immune");
                $citizen->removeStatus( $status );
            }

            $updated = true;
        }

        if ($updated) {
            $this->entityManager->persist($citizen);
            $this->entityManager->flush();
        }

        return $this->info($citizen, $output);
    }
}
