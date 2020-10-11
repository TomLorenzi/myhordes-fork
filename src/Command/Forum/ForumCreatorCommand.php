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
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

class ForumCreatorCommand extends Command
{
    protected static $defaultName = 'app:forum:create';

    private EntityManagerInterface $entityManager;
    private CommandHelper $helper;
    private KernelInterface $kernel;

    public function __construct(EntityManagerInterface $em, CommandHelper $comh, KernelInterface $kernel)
    {
        $this->entityManager = $em;
        $this->helper = $comh;
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Allows creation of new forums')
            ->setHelp('This command allows creating a new forum.')
            ->addArgument('Name', InputArgument::REQUIRED, 'The Forum Name')
            ->addArgument('Type', InputArgument::REQUIRED, 'The Forum Type')

            ->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'The Forum Description')
            ->addOption('icon', 'i', InputOption::VALUE_REQUIRED, 'The Forum Icon')
            ->addOption('no-permissions', null, InputOption::VALUE_NONE, 'If set, no permissions will be set for the forum. If CUSTOM forum type is selected, this option has no effect.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions_asked = false;

        $helper = $this->getHelper('question');
        while (empty($input->getArgument('Name'))) {
            $input->setArgument('Name', $helper->ask($input, $output, new Question('Please enter the forum name: ')));
            $questions_asked = true;
        }

        while (empty($input->getArgument('Type')) && $input->getArgument('Type') !== '0') {
            $r = new ReflectionClass(Forum::class);

            $q = new ChoiceQuestion('Please choose the forum type: ',
                array_map( fn(string $name):string => substr($name, 9),
                    array_keys(array_filter( $r->getConstants(), fn(string $name):bool => (substr( $name, 0, 9 ) === "ForumType"),ARRAY_FILTER_USE_KEY ))
                )
            );
            $t = $helper->ask($input,$output, $q);

            if (($tt = $r->getConstant( "ForumType{$t}" )) !== false)
                $input->setArgument('Type', "{$tt}");

            $questions_asked = true;
        }

        if ($questions_asked && empty($input->getOption('description'))) {
            $str = $helper->ask($input, $output, new Question("If you want the forum to have a description, enter it now. Entering a description is optional.\n"));
            if (!empty($str)) $input->setOption('description', $str);
        }

        if ($questions_asked && empty($input->getOption('icon'))) {
            $icons = ['- None -'];
            foreach (scandir("{$this->kernel->getProjectDir()}/public/build/images/forum/banner") as $f)
                if ($f !== '.' && $f !== '..' && $f !== 'bannerForumVoid.gif') $icons[] = $f;

            $str = $helper->ask($input, $output, new ChoiceQuestion('If you want the forum to have a icon, select it now.', $icons));
            if (!empty($str) && $str !== '- None -') $input->setOption('icon', $str);
        }

    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $p = null;
        $this->entityManager->persist($newForum = (new Forum())
            ->setTitle( $input->getArgument('Name') )
            ->setType( (int)$input->getArgument('Type') )
            ->setDescription( $input->getOption('description') ?? null )
            ->setIcon( $input->getOption('icon') ?? null )
        );

        if (!$input->getOption('no-permissions')) {
            switch ($newForum->getType()) {
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


            if ($g)
                $this->entityManager->persist( $p = (new ForumUsagePermissions())
                    ->setForum($newForum)
                    ->setPrincipalGroup($g)
                    ->setPermissionsGranted(ForumUsagePermissions::PermissionReadWrite)
                    ->setPermissionsDenied(ForumUsagePermissions::PermissionNone)
                );
        }

        $this->entityManager->flush();
        $output->writeln( "Created forum '<info>{$newForum->getTitle()}</info>' (<info>{$newForum->getId()}</info>)." );
        /** @var $p ForumUsagePermissions|null */
        if ($p) $output->writeln( "Created forum permission object for group '<info>{$p->getPrincipalGroup()->getName()}</info>' (<info>{$p->getPrincipalGroup()->getId()}</info>) granting <info>{$p->getPermissionsGranted()}</info> and denying <info>{$p->getPermissionsDenied()}</info>." );

        return 0;

    }
}
