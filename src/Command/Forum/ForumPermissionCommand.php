<?php


namespace App\Command\Forum;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Kernel;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\PermissionHandler;
use App\Service\StatusFactory;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:forum:permissions',
    description: 'Allows editing forum permissions'
)]
class ForumPermissionCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private CommandHelper $helper;

    public function __construct(EntityManagerInterface $em, CommandHelper $comh)
    {
        $this->entityManager = $em;
        $this->helper = $comh;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows editing forum permissions.')

            ->addArgument('ForumID', InputArgument::REQUIRED, 'The Forum ID')

            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'The group to edit permissions for. Cannot be used together with --user.')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'The user to edit permissions for. Cannot be used together with --group.')
            ->addOption('grant', null, InputOption::VALUE_OPTIONAL, 'Permissions to grant.', false)
            ->addOption('deny', null, InputOption::VALUE_OPTIONAL, 'Permissions to deny.', false)
        ;
    }

    protected function configure_perms(InputInterface $input, OutputInterface $output, int $existing_perms = 0, $deny = false): int {

        $r = new ReflectionClass(ForumUsagePermissions::class);

        $perms_proto = array_filter( $r->getConstants(), fn(int $value, string $name):bool => (substr( $name, 0, 10 ) === "Permission" && $value > 0),ARRAY_FILTER_USE_BOTH );
        $perms = [];
        foreach ( $perms_proto as $name => $value )
            $perms[ substr($name,10) ] = $value;


        $pop = null;
        $pop = function(&$array, ?int $pattern = null) use ($perms,&$pop) {
            foreach ($perms as $current => $cv) if ($pattern === null || ($pattern !== $cv && ($cv & $pattern) === $cv)) {
                foreach ($perms as $name => $v) if ($pattern === null || ($pattern !== $v  && ($v & $pattern)  === $v))
                    if ($v !== $cv && (($v & $cv) === $cv)) continue 2;

                $array[$current] = [];
                $pop($array[$current], $cv);
            }
        };

        $tree = [];
        $pop($tree);

		/** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $v = $deny ? 'DENIED' : 'GRANTED';

        $print = null;
        $print = function (array $data, string $pre = '') use ($output, &$print) {
            foreach ($data as $entry => $sub) {
                $output->writeln("{$pre}{$entry}");
                $print($sub, "{$pre}\t");
            }
        };

        $search = null;
        $search = function (array $data) use ($output, $print, &$search, $perms, &$existing_perms) {
            foreach ($data as $entry => $sub) {
                if ( ($perms[$entry] & $existing_perms) === $perms[$entry] ) {
                    $output->writeln("{$entry}");
                    $print($data[$entry], "\t");
                }
                else $search( $sub );
            }
        };

        $no_add = false;

        do {

            $output->writeln("The <info>following permissions</info> are currently <comment>$v</comment>:");
            if ($existing_perms === ForumUsagePermissions::PermissionNone) $output->writeln('None');
            else $search( $tree );

            $output->writeln("");

            $can_add = $can_remove = [];
            foreach ($perms as $name => $value) {
                if (($existing_perms & $value) === $value) $can_remove[] = $name;
                else $can_add[] = $name;
            }

            if (empty($can_add)) $no_add = true;
            if (!$no_add && $helper->ask($input, $output, new ConfirmationQuestion('Would you like to <info>add</info> to this list? (y/n) ', false))) {

                $existing_perms |= $perms[ $helper->ask($input,$output, new ChoiceQuestion('Select a permission to add:', $can_add)) ];
                continue;

            } else $no_add = true;

            if (!empty($can_remove) && $helper->ask($input, $output, new ConfirmationQuestion('Would you like to <info>remove</info> from this list? (y/n) ', false))) {

                $existing_perms &= ~$perms[ $helper->ask($input,$output, new ChoiceQuestion('Select a permission to remove:', $can_remove)) ];
                continue;

            }

            break;

        } while (true);

        return $existing_perms;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Forum $forum */
        $forum = $this->helper->resolve_string($input->getArgument('ForumID'), Forum::class, 'Forum', $this->getHelper('question'), $input, $output);
        if (!$forum) {
            $output->writeln("<error>The selected forum could not be found.</error>");
            return 1;
        }

        if ($input->getOption('user') && $input->getOption('group'))
            throw new Exception('Cannot use --user together with --group.');

        $user = $group = null;
        if ($input->getOption('user'))
            $user = $this->helper->resolve_string($input->getOption('user'), User::class, 'User', $this->getHelper('question'), $input, $output);
        if ($input->getOption('group'))
            $group = $this->helper->resolve_string($input->getOption('group'), UserGroup::class, 'Group', $this->getHelper('question'), $input, $output);

        /** @var $user User|null */
        /** @var $group UserGroup|null */
        if (!$user && !$group) {
            $output->writeln("<error>The selected principal not be found.</error>");
            return 1;
        }

        $new = false;

        $perm = $this->entityManager->getRepository(ForumUsagePermissions::class)->findOneBy(['forum' => $forum, 'principalUser' => $user, 'principalGroup' => $group]);
        if ($perm) $output->writeln('Permission object loaded.');
        else {
            $output->writeln('Creating new permission object.');
            $perm = (new ForumUsagePermissions())
                ->setForum($forum)
                ->setPrincipalUser($user)
                ->setPrincipalGroup($group)
                ->setPermissionsGranted( ForumUsagePermissions::PermissionNone )
                ->setPermissionsDenied(ForumUsagePermissions::PermissionNone);
            $new = true;
        }

        $perm->setPermissionsGranted( $this->configure_perms($input,$output,$perm->getPermissionsGranted(), false) );
        $perm->setPermissionsDenied( $this->configure_perms($input,$output,$perm->getPermissionsDenied(), true) );

        if ($new && $perm->getPermissionsGranted() === ForumUsagePermissions::PermissionNone && $perm->getPermissionsDenied() === ForumUsagePermissions::PermissionNone) {
            $output->writeln('The new permission object is empty. <info>Therefore, it will not be created!</info>');
        } elseif ($perm->getPermissionsGranted() === ForumUsagePermissions::PermissionNone && $perm->getPermissionsDenied() === ForumUsagePermissions::PermissionNone) {
            $output->writeln('The permission object is empty. <info>Therefore, it will be removed!</info>');
            $this->entityManager->remove($perm);
        } else {
            $output->writeln( "Configuring forum permission object to grant <info>{$perm->getPermissionsGranted()}</info> and deny <info>{$perm->getPermissionsDenied()}</info>." );
            $this->entityManager->persist($perm);
        }
        $this->entityManager->flush();

        return 0;

    }
}
