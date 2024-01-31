<?php


namespace App\Command\User;


use App\Entity\Citizen;
use App\Entity\User;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a new user account'
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserFactory $userFactory,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to create new users.')

            ->addArgument('name',     InputArgument::REQUIRED, 'The user\'s name.')
            ->addArgument('email',    InputArgument::REQUIRED, 'The user\'s email address.')
            ->addArgument('password', InputArgument::OPTIONAL, 'The user\'s password.')

            ->addOption('validated', null, InputOption::VALUE_NONE, 'Will validate the user automatically.')
            ->addOption('elevated', null, InputOption::VALUE_REQUIRED, 'Will set the elevation level.');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $password = $input->getArgument('password');
        while (empty($password)) {
            $helper = $this->getHelper('question');
            $password1 = new Question('Please enter the user password.');
            $password1->setHidden(true);
            $password1->setHiddenFallback(false);
            $password2 = new Question('Please repeat the user password.');
            $password2->setHidden(true);
            $password2->setHiddenFallback(false);

            $pw1 = $helper->ask( $input, $output, $password1 );
            if (empty($pw1)) {
                $output->writeln('<error>The password cannot be empty.</error>');
                continue;
            }
            $pw2 = $helper->ask( $input, $output, $password2 );

            if ($pw1 !== $pw2) {
                $output->writeln('<error>The passwords do not match.</error>');
                continue;
            }
            $password = $pw1;
        }

        $input->setArgument('password', $password);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('name');
        $usermail = $input->getArgument('email');
        $userpass = $input->getArgument('password');
        $lv = $input->getOption('elevated');

        $new_user = $this->userFactory->createUser( $username, $usermail, $userpass, $input->getOption('validated'), $error );
        switch ($error) {
            case UserFactory::ErrorNone:
                try {
                    if ($lv) $new_user->setRightsElevation($lv);
                    $this->entityManager->persist($new_user);
                    $this->entityManager->flush();
                } catch (Exception $e) {
                    $output->writeln('Could not create user. <error>' . $e->getMessage() . '</error>');
                    return -1;
                }
                $output->writeln('<info>Successfully</info> created user <comment>' . $new_user->getUsername() . '</comment> (<comment>' . $new_user->getEmail() . '</comment>)!');
                return 0;
            case UserFactory::ErrorUserExists:
                $output->writeln("Could not create user. <error>Username '{$username}' already exists.</error>");
                return 1;
            case UserFactory::ErrorMailExists:
                $output->writeln("Could not create user. <error>E-Mail '{$usermail}' already exists.</error>");
                return 2;
            default:
                $output->writeln("Could not create user. <error>Error #{$error}</error>");
                return 3;
        }
    }
}