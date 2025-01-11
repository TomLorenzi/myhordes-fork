<?php


namespace App\Command\Translation;

use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\Globals\TranslationConfigGlobal;
use App\Structures\MyHordesConf;
use DirectoryIterator;
use Exception;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\Loader\FileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

#[AsCommand(
    name: 'app:translation:stats',
    description: 'Displays translation statistics.'
)]
class TranslationsStatsCommand extends Command
{
    public function __construct(
        private readonly ConfMaster $confMaster,
        private readonly ContainerInterface $container,
        private readonly ParameterBagInterface $param,
        private readonly KernelInterface $kernel,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Translation stats.')

            ->addOption('absolute', 'a', InputOption::VALUE_NONE, 'Displays the absolute number of missing translations instead of a percentage.')
        ;
    }

    protected function executeForDirectory(InputInterface $input, OutputInterface $output, string $directory): void {
        $known_domains = [];
        foreach (new DirectoryIterator($directory) as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isFile()) $known_domains[ explode('+', explode('.', $fileInfo->getFilename())[0])[0] ] = true;
        }

        $known_domains = array_filter( array_keys($known_domains), fn($d) => !in_array($d, ['','security','validators']) );
        $known_states = [];

        $stats = [];
        $total_messages = ['TOTAL' => 0];

        $display_absolute = $input->getOption('absolute');

        foreach ($known_domains as $domain) $stats[$domain] = [];
        $stats['TOTAL'] = [];

        $langs = array_map(function($item) {return $item['code'];}, array_filter($this->confMaster->getGlobalConf()->get(MyHordesConf::CONF_LANGS), function($item) {
            return $item['generate'];
        }));

        $foreignLangs = array_filter($langs, function($item) {
            return $item != "de";
        });

        foreach ($known_domains as $domain) {
            $de_icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$domain}+intl-icu.de.yml";
            $de_base_file = "{$this->param->get('kernel.project_dir')}/translations/{$domain}.de.yml";

            $de_file = file_exists($de_icu_file) ? $de_icu_file : ( file_exists( $de_base_file ) ? $de_base_file : null );
            if ($de_file === null) throw new Exception('Source file not found.');

            /** @var FileLoader $file_loader */
            $file_loader = $this->container->get('translation.tools.loader');
            $de_messages = $file_loader->load( $de_file, 'de', $domain );

            $total_messages[$domain] = 0;
            foreach ( array_keys( $de_messages->all($domain) ) as $key ) {
                $used = true;
                foreach ( ($de_messages->getMetadata($key, $domain)['notes'] ?? []) as $note )
                    if ($note['category'] === 'from' && $note['content'] === '[unused]')
                        $used = false;
                if ($used) $total_messages[$domain]++;
            }

            $total_messages['TOTAL'] += $total_messages[$domain];

            foreach ($foreignLangs as $lang) {

                $icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$domain}+intl-icu.{$lang}.yml";
                $base_file = "{$this->param->get('kernel.project_dir')}/translations/{$domain}.{$lang}.yml";

                $file = file_exists($icu_file) ? $icu_file : ( file_exists( $base_file ) ? $base_file : null );
                if ($file === null) throw new Exception('Source file not found.');

                $messages = $file_loader->load( $file, $lang, $domain );

                $states = [];
                foreach ( $messages->all($domain) as $key => $message ) {
                    $state = 'translated';
                    $used = true;
                    $explicit_state = false;
                    foreach ( ($messages->getMetadata($key, $domain)['notes'] ?? []) as $note ) {
                        if ($note['category'] === 'state') {
                            $state = $note['content'] ?: 'translated';
                            $explicit_state = true;
                        }
                        if ($note['category'] === 'from' && $note['content'] === '[unused]')
                            $used = false;
                    }

                    if (!$explicit_state) $state = match(true) {
                        $key === $message => 'dubious',
                        default => 'translated'
                    };

                    if (!$used) continue;
                    $known_states[$state] = true;

                    if (!isset($states[$state])) $states[$state] = 1;
                    else $states[$state]++;

                    if ($state === 'madeup') {
                        if (!isset($states['translated'])) $states['translated'] = 1;
                        else $states['translated']++;
                    }
                }

                $stats[$domain][$lang] = $states;
                if (empty($stats['TOTAL'][$lang])) $stats['TOTAL'][$lang] = $states;
                else foreach ($states as $state => $count) {
                    if (!isset( $stats['TOTAL'][$lang][$state] )) $stats['TOTAL'][$lang][$state] = $count;
                    else $stats['TOTAL'][$lang][$state] += $count;
                }
            }
        }

        $known_domains[] = 'TOTAL';

        foreach ( array_keys($known_states) as $state ) {
            $table = new Table($output);
            $table->setHeaders( $display_absolute ? array_merge([mb_strtoupper( $state )], array_map(function($item){return "[ " . strtoupper($item) . " ]";}, $langs)) : array_merge([mb_strtoupper( $state )], array_map(function($item){return "[ " . strtoupper($item) . " ]";}, $foreignLangs)));

            foreach ($known_domains as $domain) {
                $table->addRow( array_merge([ $domain ], array_map( function($lang) use ($domain,$state,$display_absolute,&$stats,&$total_messages) {
                    $total = $lang === 'de' ? $total_messages[$domain] : ($stats[$domain][$lang][$state] ?? 0);
                    $matching = min(100,round(100 * $total / $total_messages[$domain]));
                    if ($total < $total_messages[$domain]) $matching = min(99, $matching);
                    if ($total > 0) $matching = max(1, $matching);


                    $col = $state === 'translated' ? $matching : (100 - $matching);
                    $display = $display_absolute ? $total : "$matching%";

                    if ($lang === 'de')  $color = 'bright-white';
                    elseif ($col >= 100) $color = 'bright-blue';
                    elseif ($col >= 90)  $color = 'bright-green';
                    elseif ($col >= 75)  $color = 'yellow';
                    elseif ($col >= 50)  $color = 'bright-yellow';
                    elseif ($col >= 30)  $color = 'bright-red';
                    else                 $color = 'red';

                    return "<fg=$color>{$display}</>";
                }, $display_absolute ? $langs : $foreignLangs)) );

            }

            $table->render();

        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=yellow>=== Core translations ===</>');
        $this->executeForDirectory( $input, $output, "{$this->param->get('kernel.project_dir')}/translations" );

        return 0;
    }
}
