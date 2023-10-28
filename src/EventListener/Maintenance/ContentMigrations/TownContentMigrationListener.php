<?php


namespace App\EventListener\Maintenance\ContentMigrations;

use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\EventListener\ContainerTypeTrait;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class TownContentMigrationListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [];
    }

    abstract protected function getMigrationName(): string;

    abstract protected function applies( TownContentMigrationEvent $event ): bool;

    abstract protected function execute( TownContentMigrationEvent $event ): void;

    final public function handle( TownContentMigrationEvent $event ) {
        if ($this->applies( $event )) {
            $event->line( "<fg=yellow>{$this->getMigrationName()}</>" );
            $this->execute( $event );
            $event->line("<fg=green>Complete.</>");
        } else $event->line( "<fg=yellow>{$this->getMigrationName()}</> - Skipped." );
    }

}