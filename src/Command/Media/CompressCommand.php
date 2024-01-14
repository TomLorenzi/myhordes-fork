<?php


namespace App\Command\Media;


use App\Entity\Avatar;
use App\Service\CommandHelper;
use App\Service\Media\ImageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:media:compress',
    description: 'Compress stored media files.'
)]
class CompressCommand extends Command
{
    private CommandHelper $helper;

    public function __construct(CommandHelper $h)
    {
        $this->helper = $h;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Compress stored media files.')
            
            ->addOption('estimate', null, InputOption::VALUE_NONE, 'Only perform estimation.')
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = [];

        $simulate = $input->getOption('estimate');

        $this->helper->leChunk( $output, Avatar::class, 5, [], true, false, function(Avatar $a) use (&$table, $simulate) {

            $f = $a->getFormat();
            if (!isset($table[$f])) $table[$f] = [
                'number' => 0,
                'success' => 0,
                'original' => 0,
                'compressed' => 0,
                's_original' => 0,
                's_compressed' => 0
            ];

            $data = stream_get_contents( $a->getImage() );
            $original = strlen( $data );

            $table[$f]['number']++;
            $table[$f]['original']+=$original;

            $image = ImageService::createImageFromData( $data );
            $format = null;
            if ($image)
                foreach ( ImageService::getCompressionOptions( $image ) as $test_format ) {
                    if ($test_format === null) continue;
                    $tmp = ImageService::save( $image, $test_format );
                    if ($tmp && strlen( $data ) > strlen( $tmp )) {
                        $format = $test_format;
                        $data = $tmp;
                    }
                }

            $success = $format !== null;
            $compressed = strlen( $data );

            if ($success) {

                $table[$f]['success']++;
                $table[$f]['compressed']+=$compressed;
                $table[$f]['s_original']+=$original;
                $table[$f]['s_compressed']+=$compressed;

                if (!$simulate) $a->setImage( $data )->setFormat( $format );
                if ( $a->getSmallImage() ) {
                    $data = stream_get_contents( $a->getSmallImage() );

                    $table[$f]['original']+=strlen( $data );
                    $table[$f]['s_original']+=strlen( $data );

                    $data = ImageService::convertImageData( $data, $format );

                    $table[$f]['compressed']+=strlen( $data );
                    $table[$f]['s_compressed']+=strlen( $data );

                    if (!$simulate) $a->setSmallImage( $data );
                }
            } else $table[$f]['compressed']+=$original;

            return $success && !$simulate;
        }, true );

        $out = new Table( $output );
        $out->setHeaders( ['Original format', 'Number', 'Success', 'Original size', 'Compressed size', 'Total compression', 'Abs. compression', 'Saved'] );

        $saved = 0;
        foreach ($table as $format => $data) {
            $saved += ( $data['original'] - $data['compressed'] );

            $out->addRow( [
                $format,
                $data['number'],
                $data['success'] . ' (' . ( 100 * round($data['success'] / ($data['number'] ?: 1) , 2)) . '%)',
                round($data['original'] / 1048576, 2) . ' MB',
                round($data['compressed'] / 1048576, 2) . ' MB',
                (100 * ($data['original'] > 0 ? round(1 - $data['compressed'] / $data['original'], 2) : 0)) . '%',
                (100 * ($data['s_original'] > 0 ? round(1 - $data['s_compressed'] / $data['s_original'], 2) : 0)) . '%',
                round(( $data['original'] - $data['compressed'] ) / 1048576, 2) . ' MB',
            ] );
        }

        $out->render();
        $output->writeln('Total savings: <fg=green>' . round($saved / 1048576, 4) . ' MB</>');
        return 0;
    }
}