<?php

namespace App\Event\Game\Town\Maintenance;

use Symfony\Component\Console\Output\OutputInterface;

class TownContentMigrationData
{
    private readonly OutputInterface $output;

    public int $manually_distributed_votes = 0;

    /**
     * @return TownContentMigrationEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(OutputInterface $output): void {
        $this->output = $output;
    }

    public function line( string $str ): void {
        $this->output->writeln("\t$str");
    }

    public function debug( string $str ): void {
        $this->output->writeln("\t\t$str", OutputInterface::VERBOSITY_DEBUG | OutputInterface::OUTPUT_NORMAL);
    }
}