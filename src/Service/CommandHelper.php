<?php


namespace App\Service;

use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Interfaces\NamedEntity;
use App\Structures\IdentifierSemantic;
use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandHelper
{
    private $entity_manager;
    private $app;
    private $_db = null;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel) {
        $this->entity_manager = $em;
        $this->app = $kernel;
    }

    private function resolverDatabase(): array {
        if ($this->_db !== null) return $this->_db;
        $this->_db = [];
        foreach (new DirectoryIterator("{$this->app->getProjectDir()}/src/Entity") as $fileInfo) {
            if( !$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php' ) continue;
            $class = "App\\Entity\\{$fileInfo->getBasename(".{$fileInfo->getExtension()}")}";
            if (!class_exists($class)) continue;
            $this->_db[$class] = [
                IdentifierSemantic::GuessMatch => [],
                IdentifierSemantic::WeakMatch => [],
                IdentifierSemantic::StrongMatch => [],
                IdentifierSemantic::PerfectMatch => ['#id'],
                IdentifierSemantic::LikelyMatch => [],
            ];

            if (is_a($class, NamedEntity::class, true)) {
                $this->_db[$class][IdentifierSemantic::PerfectMatch][] = 'label';
                $this->_db[$class][IdentifierSemantic::PerfectMatch][] = 'name';
                $this->_db[$class][IdentifierSemantic::GuessMatch][] = '%label';
                $this->_db[$class][IdentifierSemantic::GuessMatch][] = '%name';
            }
        }

        $this->_db[User::class][IdentifierSemantic::LikelyMatch] = ['#id','name','email'];
        $this->_db[User::class][IdentifierSemantic::GuessMatch] =  ['%name','%email'];
        $this->_db[Town::class][IdentifierSemantic::LikelyMatch] = ['#id','name'];
        $this->_db[Town::class][IdentifierSemantic::GuessMatch] =  ['%name'];
        foreach ([ItemPrototype::class,BuildingPrototype::class,PictoPrototype::class] as $c) {
            $this->_db[$c][IdentifierSemantic::LikelyMatch][] = 'label';
            $this->_db[$c][IdentifierSemantic::LikelyMatch][] = 'name';
        }

        return $this->_db;
    }

    public function resolve(string $id): IdentifierSemantic {
        $sem = new IdentifierSemantic();
        foreach ($this->resolverDatabase() as $class => &$config) {
            $repo = $this->entity_manager->getRepository($class);
            if ($repo === null) continue;
            foreach ($config as $strength => &$prop_list)
                foreach ($prop_list as &$prop) {
                    $e = null;

                    $mod = '';
                    if (in_array($prop[0], ['#','%'])) {
                        $mod = $prop[0];
                        $prop = substr($prop, 1);
                    }

                    if ($mod === '#' && is_numeric($id))
                        $e = $repo->findBy([$prop => (int)$id]);
                    elseif ($mod === '%')
                        $e = $repo->createQueryBuilder('e')
                            ->where("e.{$prop} LIKE :param")->setParameter('param', "%{$id}%")
                            ->getQuery()->getResult();
                    elseif ($mod === '') $e = $repo->findBy([$prop => $id]);

                    if ($e !== null) foreach ($e as $entity)
                        $sem->addResult($entity, $strength, $prop);
                }
        }
        $sem->sortResults();
        return $sem;
    }

    private function semantic_resolve( object $base, string $class, ?string $hint ): ?object {
        switch ($class) {
            case Citizen::class:
                switch (get_class($base)) {
                    case User::class:
                        /** @var User $base */
                        return $base->getActiveCitizen();
                    default: return null;
                }
            case Town::class: {
                switch (get_class($base)) {
                    case User::class:
                        /** @var User $base */
                        return $base->getActiveCitizen() ? $base->getActiveCitizen()->getTown() : null;
                    default: null;
                }
            }
            case Inventory::class: {
                switch (get_class($base)) {
                    case User::class:
                        if ($hint === null) $hint = 'rucksack';

                        /** @var User $base */
                        if (!$base->getActiveCitizen()) return null;

                        switch ($hint) {
                            case 'rucksack':case 'ruck':case 'sack':case 'inventory':case 'inv': return $base->getActiveCitizen()->getInventory();
                            case 'home':case 'house':case 'chest': return $base->getActiveCitizen()->getHome()->getChest();
                            case 'bank':case 'town': return $base->getActiveCitizen()->getTown()->getBank();
                            default: return null;
                        }
                    default: null;
                }
            }
            default: return null;
        }
    }

    public function resolve_as(string $id, string $class, ?string $hint = null) {

        $base = $this->resolve($id);
        $final = new IdentifierSemantic();
        foreach ($this->resolve($id)->getMatches() as $match) {

            if (is_a($e = $base->getMatchedObject($match), $class))
                $final->addResult($e, $base->getMatchedStrength($match), $base->getMatchedProperty($match));
            elseif ($base->getMatchedStrength($match) > IdentifierSemantic::GuessMatch) {
                $derived = $this->semantic_resolve($e, $class, $hint);
                if ($derived !== null && is_a($derived, $class))
                    $final->addResult($derived, $base->getMatchedStrength($match) - 1, 'semantics');
            }
        }

        $final->sortResults();
        return $final;
    }
}