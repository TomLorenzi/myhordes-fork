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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserInfoCommand extends Command
{
    protected static $defaultName = 'app:users';

    private $entityManager;
    private $pwenc;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $em;
        $this->pwenc = $passwordEncoder;
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
            ->addOption('mods', 'm', InputOption::VALUE_NONE, 'Only list users with elevated permissions.')

            ->addOption('set-password', null, InputOption::VALUE_REQUIRED, 'Changes the user password; set to "auto" to auto-generate.', 'auto')

            ->addOption('find-all-rps', null, InputOption::VALUE_REQUIRED, 'Gives all known RP to a user in the given lang')

            ->addOption('set-mod-level', null, InputOption::VALUE_REQUIRED, 'Sets the moderation level for a user (0 = normal user, 1 = mod, 2 = admin');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($userid = $input->getArgument('UserID')) {
            $userid = (int)$userid;
            /** @var User $user */
            $user = $this->entityManager->getRepository(User::class)->findOneById($userid);

            if (($modlv = $input->getOption('set-mod-level')) !== null) {
                $user->setIsAdmin($modlv >= 2);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($rpLang = $input->getOption('find-all-rps')) {
                $rps = $this->entityManager->getRepository(RolePlayText::class)->findAllByLang($rpLang);
                $count = 0;
                foreach ($rps as $rp) {
                    $alreadyfound = $this->entityManager->getRepository(FoundRolePlayText::class)->findByUserAndText($user, $rp);
                    if ($alreadyfound !== null)
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
            } elseif ($newpw = $input->getOption('set-password')) {

                if ($newpw === 'auto') {
                    $newpw = '';
                    $source = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789-_$';
                    for ($i = 0; $i < 9; $i++) $newpw .= $source[ mt_rand(0, strlen($source) - 1) ];
                }

                $user->setPassword($this->pwenc->encodePassword( $user,$newpw ));
                $output->writeln("New password set: <info>$newpw</info>");
                $this->entityManager->persist($user);
                $this->entityManager->flush();

            }
        } else {
            /** @var User[] $users */
            $users = array_filter( $this->entityManager->getRepository(User::class)->findAll(), function(User $user) use ($input) {

                if ($input->getOption( 'validation-pending' ) && $user->getValidated()) return false;
                if ($input->getOption( 'validated' ) && !$user->getValidated()) return false;
                if ($input->getOption( 'mods' ) && !$user->getIsAdmin()) return false;

                return true;
            } );

            $table = new Table( $output );
            $table->setHeaders( ['ID', 'Name', 'Mail', 'Validated?', 'Mod?', 'ActCitID.','ValTkn.'] );

            foreach ($users as $user) {
                $activeCitizen = $this->entityManager->getRepository(Citizen::class)->findActiveByUser( $user );
                $pendingValidation = $user->getPendingValidation();
                $table->addRow( [
                    $user->getId(), $user->getUsername(), $user->getEmail(), $user->getValidated() ? '1' : '0',
                    $user->getIsAdmin() ? '1' : '0',
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