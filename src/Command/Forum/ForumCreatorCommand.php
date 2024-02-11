<?php


namespace App\Command\Forum;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumTitle;
use App\Entity\ForumUsagePermissions;
use App\Entity\ThreadTag;
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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:forum:create',
    description: 'Allows creation of new forums'
)]
class ForumCreatorCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private KernelInterface $kernel;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel)
    {
        $this->entityManager = $em;
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows creating a new forum.')
            ->addArgument('Name', InputArgument::REQUIRED, 'The Forum Name')
            ->addArgument('Type', InputArgument::REQUIRED, 'The Forum Type')

            ->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'The Forum Description')
            ->addOption('icon', 'i', InputOption::VALUE_REQUIRED, 'The Forum Icon')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED, 'The Forum Language')
            ->addOption('no-permissions', null, InputOption::VALUE_NONE, 'If set, no permissions will be set for the forum. If CUSTOM forum type is selected, this option has no effect.')

            ->addOption('localize', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Adds a localized title in a given lang.')
            ->addOption('localize-title', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Adds the actual title for each --localize flag.')
            ->addOption('localize-desc', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Adds the actual description for each --localize flag.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
		/** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        while (empty($input->getArgument('Name')))
            $input->setArgument('Name', $helper->ask($input, $output, new Question('Please enter the forum name: ')));

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
        }

        if (empty($input->getOption('description'))) {
            $str = $helper->ask($input, $output, new Question("If you want the forum to have a description, enter it now. Entering a description is optional.\n", ""));
            if (!empty($str)) $input->setOption('description', $str);
        }

        if (empty($input->getOption('icon'))) {
            $icons = ['- None -'];
            foreach (scandir("{$this->kernel->getProjectDir()}/assets/img/forum/banner") as $f)
                if ($f !== '.' && $f !== '..' && $f !== 'bannerForumVoid.gif') $icons[] = $f;

            $str = $helper->ask($input, $output, new ChoiceQuestion('If you want the forum to have a icon, select it now.', $icons));
            if (!empty($str) && $str !== '- None -') $input->setOption('icon', $str);
        }

        if ($input->getOption('lang') === 'mu' && empty( $input->getOption('localize') )) {
            $langs = [];
            $langs_titles = [];
            $langs_descs = [];
            foreach (['de', 'en', 'fr', 'es'] as $lang) {
                $str = $helper->ask($input, $output, new Question("Enter forum title for language '$lang' or leave empty to skip adding this language.\n", ""));
                if (!empty($str)) {
                    $langs[] = $lang;
                    $langs_titles[] = $str;
                    $langs_descs[] = $helper->ask($input, $output, new Question("Enter forum description for language '$lang' or leave empty to skip adding a description for this language.\n", ""));
                }
            }

            $input->setOption( 'localize', $langs );
            $input->setOption( 'localize-title', $langs_titles );
            $input->setOption( 'localize-desc', $langs_descs );
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $langs = $input->getOption('localize');
        $langs_values = $input->getOption('localize-title');
        $langs_descs = $input->getOption('localize-desc');

        if (count($langs) !== count($langs_values) || count($langs) !== count($langs_descs))
            throw new \Exception('Must use exactly one --localize-title and one --localize-desc for each --localize.');

        $langs_t = array_combine( $langs, $langs_values );
        $langs_d = array_combine( $langs, $langs_descs );

        $p = null;
        $this->entityManager->persist($newForum = (new Forum())
            ->setTitle( $input->getArgument('Name') )
            ->setType( (int)$input->getArgument('Type') )
            ->setDescription( $input->getOption('description') ?? null )
            ->setIcon( $input->getOption('icon') ?? null )
            ->setWorldForumLanguage( $input->getOption('lang') ?? null )
        );

        foreach ($langs_t as $lang => $title)
            $newForum->addTitle( (new ForumTitle())->setLanguage($lang)->setTitle($title)->setDescription($langs_d[$lang]) );

        if (!$input->getOption('no-permissions')) {
            $g = match ($newForum->getType()) {
                Forum::ForumTypeDefault => [$this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultUserGroup])],
                Forum::ForumTypeElevated => [$this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultElevatedGroup])],
                Forum::ForumTypeMods => [$this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultModeratorGroup])],
                Forum::ForumTypeAdmins => [$this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAdminGroup])],
                Forum::ForumTypeAnimac => [
                    $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAnimactorGroup]),
                    $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultOracleGroup]),
                ],
                Forum::ForumTypeDev => [
                    $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAdminGroup]),
                    $this->entityManager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultDevGroup]),
                ],
                default => [],
            };

            foreach ($this->entityManager->getRepository(ThreadTag::class)->findAll() as $tag)
                $newForum->addAllowedTag($tag);

            foreach ($g as $group)
                $this->entityManager->persist( $p = (new ForumUsagePermissions())
                    ->setForum($newForum)
                    ->setPrincipalGroup($group)
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
