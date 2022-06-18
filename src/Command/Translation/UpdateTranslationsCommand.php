<?php


namespace App\Command\Translation;

use App\Service\CommandHelper;
use App\Service\Globals\TranslationConfigGlobal;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTranslationsCommand extends Command
{
    protected static $defaultName = 'app:translation:update';

    private CommandHelper $helper;

    private TranslationConfigGlobal $conf_trans;

    public function __construct(TranslationConfigGlobal $conf_trans, CommandHelper $helper)
    {
        $this->conf_trans = $conf_trans;
        $this->helper = $helper;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Updates translation files and adds missing translations')
            ->setHelp('Translation updater.')
            ->addArgument('lang', InputArgument::REQUIRED, 'Language to translate into.')

            ->addOption('file', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Limits translations to specific files', [])
            ->addOption('disable-php', null, InputOption::VALUE_NONE, 'Disables translation of PHP files')
            ->addOption('disable-db', null, InputOption::VALUE_NONE, 'Disables translation of database content')
            ->addOption('disable-twig', null, InputOption::VALUE_NONE, 'Disables translation of twig files')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lang = $input->getArgument( 'lang' );
        $langs = ($lang === 'all') ? ['en', 'fr', 'es', 'de'] : [$lang];
        if (count($langs) === 1) {

            $this->conf_trans->setConfigured(true);
            if ($input->getOption('disable-db')) $this->conf_trans->setDatabaseSearch(false);
            if ($input->getOption('disable-php')) $this->conf_trans->setPHPSearch(false);
            if ($input->getOption('disable-twig')) $this->conf_trans->setTwigSearch(false);
            foreach ($input->getOption('file') as $file_name)
                $this->conf_trans->addMatchedFileName($file_name);

            $command = $this->getApplication()->find('translation:extract');

            $output->writeln("Now working on translations for <info>{$lang}</info>...");
            $input = new ArrayInput([
                                        'locale' => $lang,
                                        '--force' => true,
                                        '--sort' => 'asc',
                                        '--format' => 'yml',
                                        '--prefix' => '',
                                    ]);
            $input->setInteractive(false);
            try {
                $command->run($input, $output);
            } catch (Exception $e) {
                $output->writeln("Error: <error>{$e->getMessage()}</error>");
                return 1;
            }

        } elseif (extension_loaded('pthreads')) {
            $output->writeln("Using pthreads !");
            $threads = [];
            foreach ($langs as $current_lang) {

                $com = "app:translation:update $current_lang";
                if ($input->getOption('disable-db')) $com .= " --trans-disable-db";
                if ($input->getOption('disable-php')) $com .= " --trans-disable-php";
                if ($input->getOption('disable-twig')) $com .= " --trans-disable-twig";
                foreach ($input->getOption('file') as $file_name)
                    $com .= " --file $file_name";

                $threads[] = new class(function () use ($com, $output) {
                    $this->helper->capsule($com, $output);
                }) extends \Worker {
                    private $_f;

                    public function __construct(callable $fun)
                    {
                        $this->_f = $fun;
                    }

                    public function run()
                    {
                        ($this->_f)();
                    }
                };
            }

            foreach ($threads as $thread) $thread->start();
            foreach ($threads as $thread) $thread->join();
        } else foreach ($langs as $current_lang) {
            $com = "app:translation:update $current_lang";
            if ($input->getOption('disable-db')) $com .= " --disable-db";
            if ($input->getOption('disable-php')) $com .= " --disable-php";
            if ($input->getOption('disable-twig')) $com .= " --disable-twig";
            foreach ($input->getOption('file') as $file_name)
                $com .= " --file $file_name";

            $this->helper->capsule($com, $output);
        }


        return 0;

    }
}
