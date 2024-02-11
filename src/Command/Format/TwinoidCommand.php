<?php


namespace App\Command\Format;


use App\Entity\PictoPrototype;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Service\CommandHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:format:twinoid',
    description: 'Twinoid data collections.'
)]
class TwinoidCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private CommandHelper $commandHelper;

    public function __construct(EntityManagerInterface $em, CommandHelper $commandHelper)
    {
        $this->entityManager = $em;
        $this->commandHelper = $commandHelper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Twinoid data.')

            ->addArgument('Format', InputArgument::OPTIONAL, 'Output Format', 'nice')

            ->addOption('known-titles', null, InputOption::VALUE_NONE, 'Lists all titles from imported twinoid souls')
            ->addOption('known-icons', null, InputOption::VALUE_NONE, 'Lists all titles from imported twinoid souls')
        ;
    }

    protected function get_data(OutputInterface $output, string $type, ?array &$collector = null): array {
        if ($collector === null) $collector = [];

        $f = function($t) use (&$collector, $type) {
            $data = $t->getData($this->entityManager);

            foreach ($data->getUnlockables() as $unlockable)
                if ($unlockable->getType() === $type) {
                    $picto = $unlockable->convertPicto()->getId();
                    $payload = $unlockable->getData();
                    $count = $unlockable->getCount();

                    switch ($t->getScope()) {
                        case "www.hordes.fr": $scope = 'fr'; break;
                        case "www.die2nite.com": $scope = 'en'; break;
                        case "www.dieverdammten.de": $scope = 'de'; break;
                        case "www.zombinoia.com": $scope = 'es'; break;
                        default: $scope = $t->getScope(); break;
                    }

                    if (!isset($collector[$picto])) $collector[$picto] = [];
                    if (!isset($collector[$picto][$count])) $collector[$picto][$count] = [];
                    if (!isset($collector[$picto][$count][$scope])) $collector[$picto][$count][$scope] = [];

                    if (!in_array($payload, $collector[$picto][$count][$scope])) $collector[$picto][$count][$scope][] = $payload;
                }

            return false;
        };

        $this->commandHelper->leChunk( $output, TwinoidImport::class, 50, [], true, false, $f);
        $this->commandHelper->leChunk( $output, TwinoidImportPreview::class, 50, [], true, false, $f);
        return $collector;
    }

    protected function print_data_nice(OutputInterface $output, array $collection) {
        ksort($collection,SORT_NUMERIC);
        foreach ($collection as $picto_id => &$data) {

            /** @var PictoPrototype $picto */
            $picto = $this->entityManager->getRepository(PictoPrototype::class)->find($picto_id);
            $output->writeln( "<info>{$picto->getLabel()}</info> [{$picto->getName()}] [<comment>{$picto->getId()}</comment>]" );

            ksort($data, SORT_NUMERIC);

            foreach ($data as $count => &$titles) {
                ksort($titles, SORT_STRING);
                foreach ($titles as $domain => $title)
                    foreach ($title as $i => $one_title)
                        if ($domain === array_key_first($titles) && $i === 0) $output->writeln(sprintf("\t<comment>%5u</comment>\t[%s] %s", $count, $domain, $one_title));
                        else $output->writeln(sprintf("\t     \t[%s] %s", $domain, $one_title));
            }
        }
    }

    protected function print_data_list(OutputInterface $output, array $collection) {
        $flat = [];

        foreach ($collection as &$data) foreach ($data as $titles) foreach ($titles as $title) foreach ($title as $one_title)
            if (!in_array($one_title,$data)) $flat[] = $one_title;
        sort($flat, SORT_STRING);
        foreach ($flat as $entry) $output->writeln($entry);
    }

    protected function print_data_urls(OutputInterface $output, array $collection) {
        $flat = [];

        $have = [];

        foreach ($collection as &$data) foreach ($data as $titles) foreach ($titles as $title) foreach ($title as $one_title) {
            if (!str_starts_with($one_title, 'http')) continue;

            $elems = explode('/', $one_title);
            if (!in_array($elems[array_key_last($elems)], $have)) {
                $have[] = $elems[array_key_last($elems)];
                $flat[] = $one_title;
            }
        }

        sort($flat, SORT_STRING);
        foreach ($flat as $entry) $output->writeln($entry);
    }

    protected function print_data_php_icons(OutputInterface $output, array $collection) {
        $flat = [];

        $have = [];

        foreach ($collection as $picto_id => $data)  {
            $picto = $this->entityManager->getRepository(PictoPrototype::class)->find($picto_id);
            foreach ($data as $count => $titles) foreach ($titles as $title) foreach ($title as $one_title) {
                if (!str_starts_with($one_title, 'http')) continue;
                $elems = explode('/', $one_title);
                $icon = str_replace('.gif','',$elems[array_key_last($elems)]);
                if (!in_array($elems[array_key_last($elems)], $have)) {
                    $have[] = $elems[array_key_last($elems)];
                    $flat[sprintf("{06u_}06u_%s", $picto_id)] =
                        "['icon'=>'$icon', 'unlockquantity'=>$count, 'associatedpicto'=>'{$picto->getName()}'],";
                }
            }
        }

        ksort($flat, SORT_STRING);
        foreach ($flat as $entry) $output->writeln($entry);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $data = [];

        if ($input->getOption('known-titles') && !in_array($input->getArgument('Format'), ['urls','php']))
            $data = $this->get_data($output, 'title');
        else if ($input->getOption('known-icons'))
            $data = $this->get_data($output, 'icon');

        switch ($input->getArgument('Format')) {
            case 'nice': $this->print_data_nice($output, $data); break;
            case 'list': $this->print_data_list($output, $data); break;
            case 'urls': $this->print_data_urls($output, $data); break;
            case 'php':  $this->print_data_php_icons($output, $data); break;
            default: break;
        }

        return 0;
    }
}