<?php


namespace App\Command\Forum;


use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Thread;
use App\Entity\UserGroup;
use App\Enum\Configuration\TownSetting;
use App\Kernel;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\CrowService;
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
    name: 'app:forum:default_posts',
    description: 'Creates default posts for all forums, if they are missing.'
)]
class ForumDefaultThreadsCreatorCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandHelper          $helper,
        private readonly ConfMaster             $conf,
        private readonly CrowService            $crow,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Creates default posts for all forums, if they are missing.')
            ->addArgument('ForumID', InputArgument::OPTIONAL, 'The Forum ID')
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

        $this->helper->leChunk(
            $output,
            Forum::class,
            5,
            $input->getArgument('ForumID') ? ['id' => (int)$input->getArgument('ForumID')] : [],
            true,
            false,
            function(Forum $f) {
                if (!$f->getTown()) return false;

                $conf = $this->conf->getTownConfiguration($f->getTown());

                $create = [
                    Thread::SEMANTIC_BANK => [ 'Bank', 'In diesem Thread dreht sich alles um die Bank.'],
                    Thread::SEMANTIC_DAILYVOTE => [ 'Verbesserung des Tages', 'In diesem Thread dreht sich alles um die geplanten Verbesserungen des Tages.'],
                    Thread::SEMANTIC_WORKSHOP => [ 'Werkstatt', 'In diesem Thread dreht sich alles um die Werkstatt und um Ressourcen.'],
                    Thread::SEMANTIC_CONSTRUCTIONS => [ 'Konstruktionen', 'In diesem Thread dreht sich alles um zukünftige Bauprojekte.'],
                ];

                if ($conf->get(TownSetting::CreateQAPost))
                    $create[ Thread::SEMANTIC_QA ] = ['Fragen & Antworten', 'In diesem Thread können Fragen zum Leben in der Stadt gestellt werden.'];

                foreach ($create as $sem => [$name, $text]) {
                    if ($this->entityManager->getRepository(Thread::class)->findOneBy(['forum' => $f, 'semantic' => $sem])) continue;
                    $this->crow->postToForum( $f, $text, true, true, $name, $sem );
                }

                return true;
            }
        );

        return 0;
    }
}
