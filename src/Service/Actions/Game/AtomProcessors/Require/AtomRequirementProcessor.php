<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AtomRequirementProcessor
{
    public function __construct(
        protected readonly ContainerInterface $container
    ) { }

    abstract public function __invoke( Evaluation $cache, RequirementsAtom $data ): bool;

    public static function process( ContainerInterface $container, Evaluation $cache, RequirementsAtom|array $data ): bool {
        if (!is_array($data)) $data = [$data];
        return array_reduce(
            $data,
            fn( bool $c, RequirementsAtom $atom ) => $c && (new ($atom->getClass())($container))( $cache, $atom->withContext( $cache->citizen, $cache->conf ) ),
            true
        );
    }
}