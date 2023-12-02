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

    protected function configure(): void
    {
        $this
            ->setHelp('Translation updater.')
            ->addArgument('lang', InputArgument::REQUIRED, 'Language to translate into.')

            ->addOption('disable-db', null, InputOption::VALUE_NONE, 'Disables translation of database content')
            ->addOption('disable-twig', null, InputOption::VALUE_NONE, 'Disables translation of twig files')
            ->addOption('disable-config', null, InputOption::VALUE_NONE, 'Disables translation of config files')
            ->addOption('prime', null, InputOption::VALUE_NONE, 'Limits extraction to the prime bundle.')
            ->addOption('core', null, InputOption::VALUE_NONE, 'Prevents the prime bundle from being processed.')

        ;
    }

    protected function getCommandExecutor( string $command, string $lang, ?string $bundle = null ): \Closure
    {
        $array = [
            'locale' => $lang,
            '--force' => true,
            '--sort' => 'asc',
            '--format' => 'yml',
            '--prefix' => '',
        ];

        if ($bundle) $array['bundle'] = $bundle;

        $input = new ArrayInput($array);
        $input->setInteractive(false);

        $command = $this->getApplication()->find( $command );
        return fn($output) => $command->run($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lang = $input->getArgument( 'lang' );

        $langs = ($lang === 'all') ? array_map(function($item) {return $item['code'];}, array_filter($this->confMaster->getGlobalConf()->get(MyHordesConf::CONF_LANGS), function($item) {
            return $item['generate'];
        })) : [$lang];
        if (count($langs) === 1) {

            $output->writeln("Now working on translations for <info>{$lang}</info>...");

            $this->conf_trans->setConfigured(true);
            if ($input->getOption('disable-db') || $input->getOption('prime')) $this->conf_trans->setDatabaseSearch(false);
            if ($input->getOption('disable-twig')) $this->conf_trans->setTwigSearch(false);
            if ($input->getOption('disable-config')) $this->conf_trans->setConfigSearch(false);

            try {
                if (!$input->getOption('prime')) {
                    $this->conf_trans->setBlacklistedPackages(['myhordes-prime'])->setSkipExistingMessages(false);
                    $this->getCommandExecutor( 'translation:extract', $lang )($output);
                    $this->getCommandExecutor( 'translation:extract', $lang, 'MyHordesFixturesBundle' )($output);
                }

                if (!$input->getOption('core')) {
                    $this->conf_trans->setBlacklistedPackages(null)->setSkipExistingMessages(true);
                    $this->getCommandExecutor( 'translation:extract', $lang, 'MyHordesPrimeBundle' )($output);
                }
            } catch (Exception $e) {
                $output->writeln("Error: <error>{$e->getMessage()}</error>");
                return 1;
            }

        } else foreach ($langs as $current_lang) {
            $com = "app:translation:update $current_lang";
            if ($input->getOption('disable-db')) $com .= " --disable-db";
            if ($input->getOption('disable-twig')) $com .= " --disable-twig";
            if ($input->getOption('disable-config')) $com .= " --disable-config";
            if ($input->getOption('prime')) $com .= " --prime";

            $this->helper->capsule($com, $output);
        }


        return 0;

    }
}
