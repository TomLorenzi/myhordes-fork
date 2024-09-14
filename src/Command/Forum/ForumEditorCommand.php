<?php


namespace App\Command\Forum;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\UserGroup;
use App\Kernel;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
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
    name: 'app:forum:edit',
    description: 'Allows editing forums'
)]
class ForumEditorCommand extends Command
{
    use ForumIconCollectorTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandHelper $helper,
        private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows editing an existing forum.')
            ->addArgument('ForumID', InputArgument::REQUIRED, 'The Forum ID')

            ->addOption('title', 't', InputOption::VALUE_OPTIONAL, 'The Forum Description', false)
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The Forum Type', false)
            ->addOption('description', 'd', InputOption::VALUE_OPTIONAL, 'The Forum Description', false)
            ->addOption('icon', 'i', InputOption::VALUE_OPTIONAL, 'The Forum Icon', false)
            ->addOption('no-permissions', null, InputOption::VALUE_NONE, 'If set, no permissions will be updated when changing the forum type. If --type is not set or CUSTOM forum type is selected, this option has no effect.')
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
        /** @var Forum $forum */
        $forum = $this->helper->resolve_string($input->getArgument('ForumID'), Forum::class, 'Forum', $this->getHelper('question'), $input, $output);
        if (!$forum) {
            $output->writeln("<error>The selected forum could not be found.</error>");
            return 1;
        }

        $updated = false;
        $helper = $this->getHelper('question');

        if ($input->getOption('title') !== false) {
            $str = $input->getOption('title') ?? $helper->ask($input, $output, new Question("Please enter the new forum title:\n", $forum->getTitle()));

            if ($str !== '') $forum->setTitle($str);
            else throw new Exception('The forum title cannot be empty.');

            $updated = true;
        }

        if ($input->getOption('description') !== false) {
            $str = $input->getOption('description') ?? $helper->ask($input, $output, new Question("Please enter the new forum description of leave the field empty to delete the description:\n", $forum->getDescription() ?? ''));

            $forum->setDescription(($str === '' || $str === null) ? null : $str);
            $updated = true;
        }

        if ($input->getOption('icon') !== false) {
            if ($input->getOption('icon') === null) {
                $icons = $this->listAllIcons('- None -');
                $str = $helper->ask($input, $output, new ChoiceQuestion('Please select the forum icon:', $icons));
                $forum->setIcon($str !== '- None -' ? $str : null);
            } else $forum->setIcon($input->getOption('icon'));
            $updated = true;
        }

        $reset_perms = false;
        if ($input->getOption('type') !== false) {

            if ($input->getOption('type') === null) {
                $r = new ReflectionClass(Forum::class);

                $q = new ChoiceQuestion('Please choose the forum type: ',
                    array_map( fn(string $name):string => substr($name, 9),
                        array_keys(array_filter( $r->getConstants(), fn(string $name):bool => (substr( $name, 0, 9 ) === "ForumType"),ARRAY_FILTER_USE_KEY ))
                    )
                );
                $t = $helper->ask($input,$output, $q);

                if (($tt = $r->getConstant( "ForumType{$t}" )) !== false) {
                    $forum->setType($tt);
                    $reset_perms = true;
                }
            } else {
                $forum->setType((int)$input->getOption('type'));
                $reset_perms = true;
            }

            $updated = true;
        }

        if ($input->getOption('no-permissions')) $reset_perms = false;

        if ($updated) {
            $output->writeln('Updating forum ...');
            $this->entityManager->persist($forum);

            if ($reset_perms) {
                switch ($forum->getType()) {
                    case Forum::ForumTypeDefault:
                        $g = $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultUserGroup]);
                        break;
                    case Forum::ForumTypeElevated:
                        $g = $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultElevatedGroup]);
                        break;
                    case Forum::ForumTypeMods:
                        $g = $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultModeratorGroup]);
                        break;
                    case Forum::ForumTypeAdmins:
                        $g = $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAdminGroup]);
                        break;
                    default:
                        $g = null;
                }

                if ($g) {
                    /** @var ForumUsagePermissions[] $pg */
                    $pg = $this->entityManager->getRepository(ForumUsagePermissions::class)->findBy(['forum' => $forum]);

                    $found_matching_perm = false;
                    foreach ($pg as $perm)
                        if ($perm->getPrincipalGroup() !== null && $perm->getPrincipalGroup() !== $g) {
                            $output->writeln("Removing forum permission object for group '<info>{$perm->getPrincipalGroup()->getName()}</info>'...");
                            $this->entityManager->remove($perm);
                        } elseif ($perm->getPermissionsGranted() !== (ForumUsagePermissions::PermissionReadWrite) || $perm->getPermissionsDenied() !== ForumUsagePermissions::PermissionNone ) {
                            $perm
                                ->setPermissionsGranted(ForumUsagePermissions::PermissionReadWrite)
                                ->setPermissionsDenied(ForumUsagePermissions::PermissionNone);
                            $output->writeln("Updating forum permission object for group '<info>{$perm->getPrincipalGroup()->getName()}</info>' granting <info>{$perm->getPermissionsGranted()}</info> and denying <info>{$perm->getPermissionsDenied()}</info>....");
                            $this->entityManager->persist($perm);
                            $found_matching_perm = true;
                        } else $output->writeln("Ignoring forum permission object for group '<info>{$perm->getPrincipalGroup()->getName()}</info>'.");

                    if (!$found_matching_perm) {
                        $this->entityManager->persist($p = (new ForumUsagePermissions())
                            ->setForum($forum)
                            ->setPrincipalGroup($g)
                            ->setPermissionsGranted(ForumUsagePermissions::PermissionWrite)
                            ->setPermissionsDenied(ForumUsagePermissions::PermissionNone)
                        );
                        $output->writeln( "Created forum permission object for group '<info>{$p->getPrincipalGroup()->getName()}</info>' (<info>{$p->getPrincipalGroup()->getId()}</info>) granting <info>{$p->getPermissionsGranted()}</info> and denying <info>{$p->getPermissionsDenied()}</info>." );
                    }
                }
            }
            $this->entityManager->flush();
            $output->writeln('OK!');
        } else  $output->writeln('Nothing to do.');

        return 0;

    }
}
