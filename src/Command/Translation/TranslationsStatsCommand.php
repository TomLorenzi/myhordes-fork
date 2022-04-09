<?php


namespace App\Command\Translation;

use App\Service\CommandHelper;
use App\Service\Globals\TranslationConfigGlobal;
use DirectoryIterator;
use Exception;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
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

class TranslationsStatsCommand extends Command
{
    protected static $defaultName = 'app:translation:stats';

    private CommandHelper $helper;

    private ContainerInterface $container;
    private ParameterBagInterface $param;

    public function __construct(TranslationConfigGlobal $conf_trans, CommandHelper $helper, ParameterBagInterface $param, ContainerInterface $container)
    {
        $this->conf_trans = $conf_trans;
        $this->container = $container;
        $this->helper = $helper;
        $this->param = $param;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Translation stats')
            ->setHelp('Translation stats.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $known_domains = [];
        foreach (new DirectoryIterator("{$this->param->get('kernel.project_dir')}/translations") as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isFile()) $known_domains[ explode('+', explode('.', $fileInfo->getFilename())[0])[0] ] = true;
        }

        $known_domains = array_filter( array_keys($known_domains), fn($d) => !in_array($d, ['','security','validators']) );
        $known_states = [];

        $stats = [];
        $total_messages = ['TOTAL' => 0];

        foreach ($known_domains as $domain) $stats[$domain] = [];
        $stats['TOTAL'] = [];

        foreach ($known_domains as $domain) {
            $de_icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$domain}+intl-icu.de.xlf";
            $de_base_file = "{$this->param->get('kernel.project_dir')}/translations/{$domain}.de.xlf";

            $de_file = file_exists($de_icu_file) ? $de_icu_file : ( file_exists( $de_base_file ) ? $de_base_file : null );
            if ($de_file === null) throw new Exception('Source file not found.');

            /** @var FileLoader $file_loader */
            $file_loader = $this->container->get('translation.tools.loader');
            $de_messages = $file_loader->load( $de_file, 'de', $domain );

            $total_messages['TOTAL'] += ($total_messages[$domain] = count( $de_messages->all($domain) ));

            foreach (['en','fr','es'] as $lang) {

                $icu_file  = "{$this->param->get('kernel.project_dir')}/translations/{$domain}+intl-icu.{$lang}.xlf";
                $base_file = "{$this->param->get('kernel.project_dir')}/translations/{$domain}.{$lang}.xlf";

                $file = file_exists($icu_file) ? $icu_file : ( file_exists( $base_file ) ? $base_file : null );
                if ($file === null) throw new Exception('Source file not found.');

                $messages = $file_loader->load( $file, $lang, $domain );

                $states = [];
                foreach ( array_keys( $messages->all($domain) ) as $key ) {
                    $state = 'translated';
                    foreach ( ($messages->getMetadata($key, $domain)['notes'] ?? []) as $note )
                        if ($note['category'] === 'state')
                            $state = $note['content'] ?: 'translated';

                    $known_states[$state] = true;

                    if (!isset($states[$state])) $states[$state] = 1;
                    else $states[$state]++;
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
            $table->setHeaders([mb_strtoupper( $state ), '[ FR ]', '[ EN ]', '[ ES ]' ]);

            foreach ($known_domains as $domain) {

                $table->addRow( array_merge([ $domain ], array_map( function($lang) use ($domain,$state,&$stats,&$total_messages) {
                    $matching = min(100,round(100 * ($stats[$domain][$lang][$state] ?? 0) / $total_messages[$domain]));

                    $col = $state === 'translated' ? $matching : (100 - $matching);

                    if ($col >= 100) $color = 'bright-blue';
                    elseif ($col >= 90) $color = 'bright-green';
                    elseif ($col >= 75) $color = 'bright-yellow';
                    elseif ($col >= 50) $color = 'yellow';
                    elseif ($col >= 30) $color = 'bright-red';
                    else                $color = 'red';

                    return "<fg=$color>{$matching}%</>";
                }, ['fr','en','es'])) );

            }

            $table->render();

        }

        return 0;
    }
}
