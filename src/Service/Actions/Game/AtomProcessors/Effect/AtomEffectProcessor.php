<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;
use App\Entity\Citizen;
use App\Enum\SortDefinitionWord;
use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\Atom;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AtomEffectProcessor
{
    public function __construct(
        protected readonly ContainerInterface $container
    ) { }

    abstract public function __invoke( Execution $cache, EffectAtom $data ): void;

    /**
     * @template T as Atom
     * @param array<T> $atoms
     * @return array<T>
     */
    private static function sortAtomList(array $atoms): array {
        // To speed up the process, we check if there is any sorting defined
        if (empty( array_filter( $atoms, fn(Atom $a) => $a->sort->word !== SortDefinitionWord::Default || $a->sort->priority !== 0 ) ))
            return $atoms;

        // Sort all atoms with stable positions
        $current = array_filter( $atoms, fn(Atom $a) => $a->sort->word->stable() || !in_array( $a->sort->reference, array_map( fn(Atom $a) => $a::class, $atoms ) ) );
        usort($current, fn(Atom $a, Atom $b) => $a->sort->stableSort($b->sort));

        // Group all atoms with unstable positions by their reference
        $refs = [];
        foreach (array_filter( $atoms, fn(Atom $a) => !$a->sort->word->stable() && in_array( $a->sort->reference, array_map( fn(Atom $a) => $a::class, $atoms ) )) as $ref)
            /** @var Atom $ref */
            $refs[$ref->sort->reference] = [...($refs[$ref->sort->reference] ?? []), $ref];

        $change = true;
        while ($change && !empty($refs)) {

            $remaining_types = [];
            array_walk_recursive( $refs, function(Atom $a) use (&$remaining_types) {
                $remaining_types[] = $a::class;
            });
            $next_keys = array_filter( array_keys( $refs ), fn(string $t) => !in_array($t, $remaining_types) );

            $change = !empty($next_keys);

            foreach ($next_keys as $next_key) {

                $before = array_filter( $refs[$next_key], fn(Atom $a) => $a->sort->word === SortDefinitionWord::Before );
                usort($before, fn(Atom $a, Atom $b) => $b->sort->priority <=> $a->sort->priority);
                $after  = array_filter( $refs[$next_key], fn(Atom $a) => $a->sort->word === SortDefinitionWord::After );
                usort($after, fn(Atom $a, Atom $b) => $b->sort->priority <=> $a->sort->priority);

                $before_id = null;
                $after_id = null;
                foreach ($current as $id => $atom)
                    if ($atom::class === $next_key)
                        $before_id ??= $after_id = $id;

                $current = [
                    ...array_slice( $current, 0, $before_id ),
                    ...$before,
                    ...array_slice( $current, $before_id, $after_id - $before_id + 1 ),
                    ...$after,
                    ...array_slice( $current, $before_id + 1 ),
                ];

                unset($refs[$next_key]);
            }
        }

        if (!empty($refs))
            throw new \Exception('Unable to determine effect atom sorting!');

        return $current;
    }

    public static function process( ContainerInterface $container, Execution $cache, EffectAtom|array $data, ?Citizen $contextCitizen = null ): void {
        foreach (self::sortAtomList( is_array($data) ? $data : [$data] ) as $atom) (new ($atom->getClass())($container))( $cache, $atom->withContext( $contextCitizen ?? $cache->citizen, $cache->conf ) );
    }
}