<?php

namespace App\Service\Actions\Game;

use App\Entity\HeroicActionPrototype;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

readonly class SpanHeroicActionInheritanceTreeAction
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) { }

    protected function upwards(HeroicActionPrototype $skill): array {
        // Initial condition: Empty set of already found actions, focus on the given action
        $action_cache = [];
        $current_names = [$skill->getName()];

        do {
            // Fetch all actions replacing an action within our focus that has not been fetched before
            $level = $this->entityManager->getRepository(HeroicActionPrototype::class)->matching((new Criteria())
                ->andWhere( Criteria::expr()->in('replaced_action', $current_names) )
                ->andWhere( Criteria::expr()->notIn('id', array_map( fn(HeroicActionPrototype $p) => $p->getId(), $action_cache )) )
            )->toArray();

            // Add newly found actions to cache
            $action_cache = [...$action_cache, ...$level];
            // Set all names for the last found actions as our new focus
            $current_names = array_map(fn(HeroicActionPrototype $p) => $p->getName(), $level);
        } while(!empty($current_names));

        return $action_cache;
    }

    protected function downwards(HeroicActionPrototype $skill): array {
        // Initial condition: Empty set of already found actions, focus on the given action
        $action_cache = [];
        $current = $skill;

        // Travel downwards the tree until we find no more replaced actions
        // We're including the already found action IDs in the query to prevent circular references
        while (
            ($current->getReplacedAction() !== null) &&
            ($proto = $this->entityManager->getRepository(HeroicActionPrototype::class)->matching((new Criteria())
                ->andWhere(Criteria::expr()->eq('name', $current->getReplacedAction()))
                ->andWhere( Criteria::expr()->notIn('id', array_map( fn(HeroicActionPrototype $p) => $p->getId(), $action_cache )) )
            )->first())
        )
            $action_cache[] = $current = $proto;

        return $action_cache;
    }

    protected function parallel(HeroicActionPrototype $skill): array {
        // We currently do not have a concept of same-level skills
        return [];
    }

    /**
     * @param HeroicActionPrototype $skill Base skill
     * @param int $direction Positive value to go up the tree (get all actions that replace the given action), negative value to go down the tree (get all actions replaced by the given action).
     * @return HeroicActionPrototype[]
     */
    public function __invoke(
        HeroicActionPrototype $skill,
        int $direction
    )
    {
        return match (true) {
            $direction === 0 => $this->parallel($skill),
            $direction < 0 => $this->downwards($skill),
            $direction > 0 => $this->upwards($skill),
        };
    }
}