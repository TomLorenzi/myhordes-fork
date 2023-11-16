<?php


namespace App\Command\Debug;

use App\Entity\TwinoidImport;
use App\Service\CommandHelper;
use Composer\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as InputOptionAlias;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:debug:twinoid:structure',
    description: 'Debug command to analyze Twinoid data structure'
)]
class TwinoidStructureCommand extends Command
{
    public function __construct(
        private readonly CommandHelper $helper
    )
    {
        parent::__construct();
    }

    private function extract_structure($array): array {
        $proto = array_map( fn($value) => match(true) {
            is_string( $value ) => 'string',
            is_int( $value ) => 'int',
            is_float( $value ) => 'float',
            is_bool( $value ) => 'bool',
            is_array( $value ) => $this->extract_structure( $value ),
            default => 'unknown'
        }, $array );

        if (array_is_list( $proto )) {

            $base = array_values(array_unique(array_filter( $proto, fn($v) => !is_array( $v ) )));
            $struct = array_reduce(
                array_filter( $proto, fn($v) => is_array( $v ) ),
                fn(array $c, array $v) => array_replace_recursive( $v, $c ),
                []
            );
            if (empty($base) || empty($struct)) return ['arrayOf' => $base ?: $struct];
            $base[] = $struct;
            return ['arrayOf' => $base];

        } else return $proto;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Debug Twinoid data.')

            ->addOption('limit', null, InputOptionAlias::VALUE_REQUIRED, 'Amount of datasets to process')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->hasOption('limit') ? $input->getOption('limit') : null;

        $structure = [];
        $this->helper->leChunk( $output, TwinoidImport::class, 50, [], true, false, function(TwinoidImport $import) use (&$structure) {
            $structure = array_replace_recursive( $structure, $this->extract_structure( $import->getPayload() ) );
        }, true, limit: $limit );

        $output->writeln( json_encode( $structure, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT ) );

        return 0;
    }
}
