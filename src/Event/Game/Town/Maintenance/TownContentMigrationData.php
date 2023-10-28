<?php

namespace App\Event\Game\Town\Maintenance;

use App\Entity\Building;
use App\Entity\ItemPrototype;
use App\Entity\Season;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectEvent;
use Symfony\Component\Console\Output\OutputInterface;

class TownContentMigrationData
{
    private readonly OutputInterface $output;

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