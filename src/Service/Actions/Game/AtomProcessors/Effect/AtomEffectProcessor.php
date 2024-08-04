<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;
use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AtomEffectProcessor
{
    public function __construct(
        protected readonly ContainerInterface $container
    ) { }

    abstract public function __invoke( Execution $cache, EffectAtom $data ): void;

    public static function process( ContainerInterface $container, Execution $cache, EffectAtom|array $data ): void {
        foreach (is_array($data) ? $data : [$data] as $atom) (new ($atom->getClass())($container))( $cache, $atom->withContext( $cache->citizen, $cache->conf ) );
    }
}