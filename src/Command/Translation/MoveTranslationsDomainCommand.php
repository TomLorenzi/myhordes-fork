<?php


namespace App\Command\Translation;

use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Structures\MyHordesConf;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\Loader\FileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

#[AsCommand(
    name: 'app:translation:move-domain',
    description: 'Performs migrations to update content after a version update.'
)]
class MoveTranslationsDomainCommand extends Command
{
    private ConfMaster $confMaster;

    private ContainerInterface $container;
    private ParameterBagInterface $param;

    public function __construct(ParameterBagInterface $param, ContainerInterface $container, ConfMaster $confMaster)
    {
        $this->container = $container;
        $this->param = $param;
        $this->confMaster = $confMaster;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Migrations.')
            ->addArgument('from', InputArgument::REQUIRED, 'Source domain')
            ->addArgument('to', InputArgument::REQUIRED, 'Target domain')

            ->addOption('match', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Selects only translations appearing in the given source', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $domain = $input->getArgument('from');
        $target = $input->getArgument('to');

        $icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$domain}+intl-icu.de.xlf";
        $base_file = "{$this->param->get('kernel.project_dir')}/translations/{$domain}.de.xlf";

        $file = file_exists($icu_file) ? $icu_file : ( file_exists( $base_file ) ? $base_file : null );
        if ($file === null) throw new Exception('Source file not found.');

        /** @var FileLoader $file_loader */
        $file_loader = $this->container->get('translation.tools.loader');

        /** @var FileDumper $file_dumper */
        $file_dumper = $this->container->get('translation.tools.dumper');

        $messages = $file_loader->load( $file, 'de', $domain );

        $format_source = fn($s) => str_replace(['twig://', 'db://', 'php://'],['<fg=bright-yellow>TWIG</> ', '<fg=bright-green>DB</> ', '<fg=bright-blue>PHP</> '], $s);

        $filters = $input->getOption('match');
        if (empty($filters)) $output->writeln('Moving messages from <info>$domain</info> to <info>$target</info>.');
        else $output->writeln("Moving messages from <info>$domain</info> to <info>$target</info> that come from the following sources: \n" . $format_source(implode("\n", array_map(fn($s) => "\t$s", $filters))));

        $move_keys = [];
        $ambiguous = [];

        foreach ( $messages->all($domain) as $key => $message) {
            if (empty($filters)) $move_keys[] = $key;
            else {
                $sources = [];
                foreach ( ($messages->getMetadata($key, $domain)['notes'] ?? []) as $note )
                    if ($note['category'] === 'from' && $note['content'] !== '[unused]')
                        $sources = array_merge( $sources, explode(';', $note['content']) );

                if (empty($sources)) continue;

                $hit = [];
                $miss = [];
                foreach ( $sources as $source ) {
                    if (in_array($source, $filters))
                        $hit[] = $source;
                    else $miss[] = $source;
                }

                if (!empty($hit) && !empty($miss)) $ambiguous[$key] = $miss;
                elseif ($hit) $move_keys[] = $key;
            }
        }

        if (!empty($ambiguous)) {
            $table = new Table($output);
            $table->setHeaders(['Message', 'Blocking source']);

            foreach ($ambiguous as $message => $entry)
                $table->addRow([ mb_strlen($message) > 64 ? (mb_substr($message, 0, 63) . '…') : $message, $format_source(implode("\n", $entry)) ]);

            $table->render();

            $output->writeln('The <fg=red>' . count($ambiguous) . '</> messages above match the given filter, but are also used from an additional, unmatched source. <fg=red>These entries will be ignored going forward!</>');
        }

        $output->writeln('Found <info>' . count($move_keys) . '</info> translation entries to move.');
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
        if (!$helper->ask($input, $output, new ConfirmationQuestion('Continue? (y/n) ', false)))
            return 0;

        $langs = array_map(function($item) {return $item['code'];}, array_filter($this->confMaster->getGlobalConf()->get(MyHordesConf::CONF_LANGS), function($item) {
            return $item['generate'];
        }));
        foreach ($langs as $lang) {
            $output->write("Loading <comment>$lang</comment> catalogue files... ");
            $in_icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$domain}+intl-icu.{$lang}.xlf";
            $in_base_file = "{$this->param->get('kernel.project_dir')}/translations/{$domain}.{$lang}.xlf";

            $input_file = file_exists($in_icu_file) ? $in_icu_file : ( file_exists( $in_base_file ) ? $in_base_file : null );
            if ($input_file === null) throw new Exception('Could not load message catalog for ' . $lang);
            $input_catalogue = $file_loader->load( $input_file, $lang, $domain );

            $out_icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$target}+intl-icu.{$lang}.xlf";
            $out_base_file = "{$this->param->get('kernel.project_dir')}/translations/{$target}.{$lang}.xlf";

            $output_file = file_exists($out_base_file) ? $base_file : $out_icu_file;
            $out_catalogue = file_exists($output_file) ? $file_loader->load( $output_file, $lang, $domain ) : new MessageCatalogue($lang,[]);
            $output->writeln("<info>OK!</info>");

            $final_domain = file_exists( $in_icu_file  )   ? ($domain . MessageCatalogueInterface::INTL_DOMAIN_SUFFIX) : $domain;
            $final_target = !file_exists( $out_base_file ) ? ($target . MessageCatalogueInterface::INTL_DOMAIN_SUFFIX) : $target;

            $output->write("Adding messages to the <comment>$target/$lang</comment> catalogue... ");
            foreach ($move_keys as $key) {
                $out_catalogue->set($key, $input_catalogue->get($key, $domain), $final_target);
                $out_catalogue->setMetadata( $key, $input_catalogue->getMetadata( $key, $domain ), $final_target );
            }
            $file_dumper->dump( $out_catalogue, ['path' => "{$this->param->get('kernel.project_dir')}/translations"] );
            $output->writeln("<info>OK!</info>");

            $output->write("Removing messages from the <comment>$domain/$lang</comment> catalogue... ");
            $clean_catalogue = new MessageCatalogue($lang,[]);
            foreach ($input_catalogue->all($domain) as $key => $message) if (!in_array( $key, $move_keys )) {
                $clean_catalogue->set( $key, $message, $final_domain );
                $clean_catalogue->setMetadata( $key, $input_catalogue->getMetadata( $key, $domain ), $final_domain );
            }
            $file_dumper->dump( $clean_catalogue, ['path' => "{$this->param->get('kernel.project_dir')}/translations"] );
            $output->writeln("<info>OK!</info>");
        }

        return 0;
    }
}
