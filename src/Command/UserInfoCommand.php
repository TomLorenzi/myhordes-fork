<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserInfoCommand extends Command
{
    protected static $defaultName = 'app:users';

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Lists information about users.')
            ->setHelp('This command allows you list users, or get information about a specific user.')

            ->addArgument('UserID', InputArgument::OPTIONAL, 'The user ID')
            ->addOption('validation-pending', 'v0', InputOption::VALUE_NONE, 'Only list users with pending validation.')
            ->addOption('validated', 'v1', InputOption::VALUE_NONE, 'Only list validated users.')
            ->addOption('find-all-rps', 'rps', InputOption::VALUE_REQUIRED, 'Gives all known RP to a user in the given lang');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($userid = $input->getArgument('UserID')){
            $userid = (int) $userid;
            $user = $this->entityManager->getRepository(User::class)->findOneById($userid);

            if($rpLang = $input->getOption('find-all-rps')){
                $rps = $this->entityManager->getRepository(RolePlayText::class)->findAllByLang($rpLang);
                $count = 0;
                foreach ($rps as $rp) {
                    $alreadyfound = $this->entityManager->getRepository(FoundRolePlayText::class)->findByUserAndText($user, $rp);
                    if($alreadyfound !== null)
                        continue;
                    $count++;
                    $foundrp = new FoundRolePlayText();
                    $foundrp->setUser($user)->setText($rp);
                    $user->getFoundTexts()->add($foundrp);

                    $this->entityManager->persist($foundrp);
                }
                echo "Added $count RPs to user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        } else {
            /** @var User[] $users */
            $users = array_filter( $this->entityManager->getRepository(User::class)->findAll(), function(User $user) use ($input) {

                if ($input->getOption( 'validation-pending' ) && $user->getValidated()) return false;
                if ($input->getOption( 'validated' ) && !$user->getValidated()) return false;

                return true;
            } );

            $table = new Table( $output );
            $table->setHeaders( ['ID', 'Name', 'Mail', 'Validated?', 'ActCitID.','ValTkn.'] );

            foreach ($users as $user) {
                $activeCitizen = $this->entityManager->getRepository(Citizen::class)->findActiveByUser( $user );
                $pendingValidation = $user->getPendingValidation();
                $table->addRow( [
                    $user->getId(), $user->getUsername(), $user->getEmail(), $user->getValidated() ? '1' : '0',
                    $activeCitizen ? $activeCitizen->getId() : '-',
                    $pendingValidation ? $pendingValidation->getPkey() : '-'
                ] );
            }

            $table->render();
            $output->writeln('Found a total of <info>' . count($users) . '</info> users.');
        }

        return 0;
    }
}