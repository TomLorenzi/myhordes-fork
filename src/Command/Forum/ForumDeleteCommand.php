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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:forum:delete',
    description: 'Allows deleting forums'
)]
class ForumDeleteCommand extends Command
{
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

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows deleting an existing forum.')
            ->addArgument('ForumID', InputArgument::REQUIRED, 'The Forum ID')

            ->addOption('move-to', 'm', InputOption::VALUE_REQUIRED, 'If set, all threads will be moved to another forum.', false)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Forum $forum */
        $forum = $this->helper->resolve_string($input->getArgument('ForumID'), Forum::class, 'Forum', $this->getHelper('question'), $input, $output);
        if (!$forum) {
            $output->writeln("<error>The selected forum could not be found.</error>");
            return 1;
        }

        /** @var ?Forum $new_forum */
        $new_forum = null;
        if ($input->getOption('move-to')) {
            $new_forum = $this->helper->resolve_string($input->getOption('move-to'), Forum::class, 'Destination forum', $this->getHelper('question'), $input, $output);
            if (!$new_forum) {
                $output->writeln("<error>The selected forum could not be found.</error>");
                return 1;
            }
        }

        $helper = $this->getHelper('question');
        if (!$helper->ask($input, $output, new ConfirmationQuestion($new_forum ? "Are you sure you want to delete the forum <info>{$forum->getTitle()}</info> and move all its content to <info>{$new_forum->getTitle()}</info>? (y/n)" : "Are you sure you want to delete the forum <info>{$forum->getTitle()}</info>? (y/n)", false)))
            return 0;



        if ($new_forum) {
            $f_id = $forum->getId();

            foreach ($forum->getThreads() as $thread)
                if (!$thread->getTranslatable()) {
                    $this->entityManager->persist($thread->setForum($new_forum));
                    foreach ($thread->getPosts() as $post)
                        $this->entityManager->persist($post->setSearchForum($new_forum));
                }

            $this->entityManager->persist($forum);
            $this->entityManager->persist($new_forum);

            $this->entityManager->flush();
            $this->entityManager->clear();

            $forum = $this->entityManager->getRepository(Forum::class)->find($f_id);
            $new_forum = null;
        }

        $this->entityManager->remove($forum);
        $this->entityManager->flush();

        return 0;

    }
}
