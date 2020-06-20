<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class CitizenInspectorCommand extends Command
{
    protected static $defaultName = 'app:citizen';

    private $entityManager;
    private $statusFactory;
    private $itemFactory;
    private $inventoryHandler;
    private $citizenHandler;

    public function __construct(EntityManagerInterface $em, StatusFactory $sf, ItemFactory $if, InventoryHandler $ih, CitizenHandler $ch)
    {
        $this->entityManager = $em;
        $this->statusFactory = $sf;
        $this->inventoryHandler = $ih;
        $this->itemFactory = $if;
        $this->citizenHandler = $ch;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manipulates and lists information about a single citizen.')
            ->setHelp('This command allows you work on single citizen.')
            ->addArgument('CitizenID', InputArgument::REQUIRED, 'The citizen ID')

            ->addOption('set-ap', 'ap',InputOption::VALUE_REQUIRED, 'Sets the current AP.', -1)
            ->addOption('set-pm', 'pm',InputOption::VALUE_REQUIRED, 'Sets the current PM.', -1)

            ->addOption('add-status','sn',InputOption::VALUE_REQUIRED, 'Adds a new status.', '')
            ->addOption('remove-status',null,InputOption::VALUE_REQUIRED, 'Removes an existing status.', '')

            ->addOption('add-role','sr',InputOption::VALUE_REQUIRED, 'Adds a new role.', '')
            ->addOption('remove-role',null,InputOption::VALUE_REQUIRED, 'Removes an existing role.', '')

            ->addOption('set-banned', null, InputOption::VALUE_OPTIONAL, 'Bans a citizen', '')
        ;
    }

    protected function info(Citizen &$citizen, OutputInterface $output) {
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Citizen $citizen */
        $citizen = $this->entityManager->getRepository(Citizen::class)->find( (int)$input->getArgument('CitizenID') );

        $updated = false;

        if (!$citizen) {
            $output->writeln("<error>The selected citizen could not be found.</error>");
            return 1;
        }

        $set_ap = $input->getOption('set-ap');
        if ($set_ap >= 0) {
            $citizen->setAp( $set_ap );
            $updated = true;
        }

        $set_pm = $input->getOption('set-pm');
        if ($set_pm >= 0 && $citizen->hasRole('shaman')) {
            $citizen->setPm( $set_pm );
            $updated = true;
        }

        if (($ban = $input->getOption('set-banned')) !== '') {
            $citizen->setBanished($ban);
            if($ban && $citizen->getProfession()->getHeroic() && $this->citizenHandler->hasSkill($citizen, 'revenge') && $citizen->getTown()->getDay() >= 3) {
                $this->inventoryHandler->forceMoveItem( $citizen->getInventory(), $this->itemFactory->createItem( 'poison_#00' ) );
                $this->inventoryHandler->forceMoveItem( $citizen->getInventory(), $this->itemFactory->createItem( 'poison_#00' ) );
            }
            $updated = true;
        }

        if ($new_status = $input->getOption('add-status')) {

            $status = $this->statusFactory->createStatus( $new_status );
            if (!$status) {
                $output->writeln("<error>The selected status could not be found.</error>");
                return 1;
            }

            $output->writeln( "Adding status '<info>{$status->getName()}</info>'.\n" );
            $citizen->addStatus( $status );

            $updated = true;
        }

        if ($rem_status = $input->getOption('remove-status')) {

            $status = $this->statusFactory->createStatus( $rem_status );
            if (!$status) {
                $output->writeln("<error>The selected status could not be found.</error>");
                return 1;
            }

            $output->writeln( "Removing status '<info>{$status->getName()}</info>'.\n" );
            $citizen->removeStatus( $status );

            $updated = true;
        }

        if ($new_role = $input->getOption('add-role')) {

            $role = $this->entityManager->getRepository(CitizenRole::class)->findOneByName( $new_role );
            if (!$role) {
                $output->writeln("<error>The selected role could not be found.</error>");
                return 1;
            }

            $output->writeln( "Adding role '<info>{$role->getName()}</info>'.\n" );
            $citizen->addRole( $role );

            if($new_role === 'shaman') {
                $status = $this->entityManager->getRepository(CitizenStatus::class)->findOneByName("tg_shaman_immune");
                $citizen->addStatus( $status );
            }

            $updated = true;
        }

        if ($rem_role = $input->getOption('remove-role')) {

            $role = $this->entityManager->getRepository(CitizenRole::class)->findOneByName( $rem_role );
            if (!$role) {
                $output->writeln("<error>The selected role could not be found.</error>");
                return 1;
            }

            $output->writeln( "Removing role '<info>{$role->getName()}</info>'.\n" );
            $citizen->removeRole( $role );

            if($rem_role === 'shaman') {
                $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("tg_shaman_immune");
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
