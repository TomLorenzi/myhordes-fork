<?php


namespace App\Command\Translation;

use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Structures\MyHordesConf;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:translation:update',
    description: 'Updates translation files and adds missing translations'
)]
class UpdateTranslationsCommand extends Command
{
    private CommandHelper $helper;

    private TranslationConfigGlobal $conf_trans;
    private ConfMaster $confMaster;

    public function __construct(TranslationConfigGlobal $conf_trans, CommandHelper $helper, ConfMaster $confMaster)
    {
        $this->conf_trans = $conf_trans;
        $this->helper = $helper;
        $this->confMaster = $confMaster;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp('Translation updater.')
            ->addArgument('lang', InputArgument::REQUIRED, 'Language to translate into.')

            ->addOption('file', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Limits translations to specific files', [])
            ->addOption('disable-php', null, InputOption::VALUE_NONE, 'Disables translation of PHP files')
            ->addOption('disable-db', null, InputOption::VALUE_NONE, 'Disables translation of database content')
            ->addOption('disable-twig', null, InputOption::VALUE_NONE, 'Disables translation of twig files')
            ->addOption('disable-config', null, InputOption::VALUE_NONE, 'Disables translation of config files')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lang = $input->getArgument( 'lang' );

        $langs = ($lang === 'all') ? array_map(function($item) {return $item['code'];}, array_filter($this->confMaster->getGlobalConf()->get(MyHordesConf::CONF_LANGS), function($item) {
            return $item['generate'];
        })) : [$lang];
        if (count($langs) === 1) {

            $this->conf_trans->setConfigured(true);
            if ($input->getOption('disable-db')) $this->conf_trans->setDatabaseSearch(false);
            if ($input->getOption('disable-php')) $this->conf_trans->setPHPSearch(false);
            if ($input->getOption('disable-twig')) $this->conf_trans->setTwigSearch(false);
            if ($input->getOption('disable-config')) $this->conf_trans->setConfigSearch(false);
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

            $input = new ArrayInput([
                                        'locale' => $lang,
                                        'bundle' => 'MyHordesFixturesBundle',
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

            $command = $this->getApplication()->find('app:translation:bundle');
            $input = new ArrayInput([
                                        'locale' => $lang,
                                        'bundle' => 'MyHordesPrimeBundle',
                                        '--force' => true,
                                        '--sort' => 'asc',
                                        '--format' => 'yml',
                                        '--prefix' => '',
                                    ]);
            $input->setInteractive(false);

            $this->conf_trans->setDatabaseSearch(false);
            $this->conf_trans->setTwigSearch(false);

            try {
                $command->run($input, $output);
            } catch (Exception $e) {
                $output->writeln("Error: <error>{$e->getMessage()}</error>");
                return 1;
            }

        } else foreach ($langs as $current_lang) {
            $com = "app:translation:update $current_lang";
            if ($input->getOption('disable-db')) $com .= " --disable-db";
            if ($input->getOption('disable-php')) $com .= " --disable-php";
            if ($input->getOption('disable-twig')) $com .= " --disable-twig";
            if ($input->getOption('disable-config')) $com .= " --disable-config";
            foreach ($input->getOption('file') as $file_name)
                $com .= " --file $file_name";

            $this->helper->capsule($com, $output);
        }


        return 0;

    }
}
