<?php


namespace App\Service;

use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Inventory;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Interfaces\NamedEntity;
use App\Structures\IdentifierSemantic;
use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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

    public function printObject(object $e): string {
        $class = get_class($e);
        while (strpos($class, 'Proxies\__CG__\\') === 0)
            $class = get_parent_class($class);

        switch ($class) {
            case User::class:
                /** @var User $e */
                return "User #{$e->getId()} <comment>{$e->getUsername()}</comment> ({$e->getEmail()})";
            case Citizen::class:
                /** @var Citizen $e */
                return "Citizen #{$e->getId()} <comment>{$e->getUser()->getUsername()}</comment> ({$e->getProfession()->getLabel()} in {$e->getTown()->getName()})";
            case ItemPrototype::class:
                /** @var ItemPrototype $e */
                return "Item Type #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})";
            case PictoPrototype::class:
                /** @var PictoPrototype $e */
                return "Picto Type #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})";
            case BuildingPrototype::class:
                /** @var BuildingPrototype $e */
                return "Building Type #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})";
            case Town::class:
                /** @var Town $e */
                return "Town #{$e->getId()} <comment>{$e->getName()}</comment> ({$e->getLanguage()}, Day {$e->getDay()})";
            case Forum::class:
                /** @var Forum $e */
                return $e->getTown()
                    ? "Town Forum #{$e->getId()} <comment>{$e->getTitle()}</comment>"
                    : "Forum #{$e->getId()} <comment>{$e->getTitle()}</comment> (Type: <comment>{$e->getType()}</comment>)";
            case UserGroup::class:
                /** @var UserGroup $e */
                return "User group #{$e->getId()} <comment>{$e->getName()}</comment> (Ref1 '<comment>{$e->getRef1()}</comment>' Ref2 '<comment>{$e->getRef2()}</comment>')";
            case ForumUsagePermissions::class:
                /** @var ForumUsagePermissions $e */
                $front = $e->getForum() ? "Forum Permissions #{$e->getId()} ('<comment>{$e->getForum()->getTitle()}</comment>')" : "Default Forum Permissions #{$e->getId()}";
                if ($e->getPrincipalUser()) return "$front for User '<comment>{$e->getPrincipalUser()->getUsername()}</comment>'";
                elseif ($e->getPrincipalGroup()) return "$front for User '<comment>{$e->getPrincipalGroup()->getName()}</comment>'";
                else return $front;
            case Inventory::class:
                /** @var Inventory $e */
                if ($e->getTown())              return "Inventory #{$e->getId()} (Bank of {$e->getTown()->getName()})";
                else if ($e->getHome())         return "Inventory #{$e->getId()} (Home of {$e->getHome()->getCitizen()->getUser()->getUsername()} in {$e->getHome()->getCitizen()->getTown()->getName()})";
                else if ($e->getCitizen())      return "Inventory #{$e->getId()} (Rucksack of {$e->getCitizen()->getUser()->getUsername()} in {$e->getCitizen()->getTown()->getName()})";
                else if ($e->getZone())         return "Inventory #{$e->getId()} (Floor of {$e->getZone()->getX()}/{$e->getZone()->getY()} in {$e->getZone()->getTown()->getName()})";
                else if ($e->getRuinZone())     return "Inventory #{$e->getId()} (Ruin Floor of {$e->getRuinZone()->getX()}/{$e->getRuinZone()->getY()} at {$e->getRuinZone()->getZone()->getX()}/{$e->getRuinZone()->getZone()->getY()} in {$e->getRuinZone()->getZone()->getTown()->getName()})";
                else if ($e->getRuinZoneRoom()) return "Inventory #{$e->getId()} (Room Floor of {$e->getRuinZoneRoom()->getX()}/{$e->getRuinZoneRoom()->getY()} at {$e->getRuinZoneRoom()->getZone()->getX()}/{$e->getRuinZoneRoom()->getZone()->getY()} in {$e->getRuinZoneRoom()->getZone()->getTown()->getName()})";
                else return "Inventory #{$e->getId()} (unknown)";
            default:
                $cls_ex = explode('\\', get_class($e));
                $niceName =  preg_replace('/(\w)([ABCDEFGHIJKLMNOPQRSTUVWXYZ\d])/', '$1 $2', array_pop($cls_ex));
                $niceName = get_class($e);
                if (is_a($e, NamedEntity::class))
                    return "$niceName #{$e->getId()} <comment>{$e->getLabel()}</comment> ({$e->getName()})";
                else return "$niceName #{$e->getId()}";
        }
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

        $this->_db[Forum::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[Forum::class][IdentifierSemantic::LikelyMatch]  = ['title'];
        $this->_db[Forum::class][IdentifierSemantic::GuessMatch]   = ['%title', '%description'];

        $this->_db[UserGroup::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[UserGroup::class][IdentifierSemantic::LikelyMatch]  = ['name'];
        $this->_db[UserGroup::class][IdentifierSemantic::GuessMatch]   = ['%name'];

        $this->_db[Recipe::class][IdentifierSemantic::PerfectMatch] = ['#id',];
        $this->_db[Recipe::class][IdentifierSemantic::LikelyMatch]  = ['name'];
        $this->_db[Recipe::class][IdentifierSemantic::GuessMatch]   = ['%name'];

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
                    case UserGroup::class:
                        /** @var UserGroup $base */
                        return $base->getType() === UserGroup::GroupTownInhabitants ? $this->entity_manager->getRepository(Town::class)->find($base->getRef1()) : null;
                    default: null;
                }
            }
            case Forum::class: {
                switch (get_class($base)) {
                    case Town::class:
                        /** @var Town $base */
                        return $base->getForum();
                    default: null;
                }
            }
            case UserGroup::class: {
                switch (get_class($base)) {
                    case Town::class:
                        /** @var Town $base */
                        return $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTownInhabitants, 'ref1' => $base->getID()]);
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
                            case 'zone':case 'floor': return $base->getActiveCitizen()->getZone() ? $base->getActiveCitizen()->getZone()->getFloor() : null;
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
            elseif ($base->getMatchedStrength($match) >= IdentifierSemantic::GuessMatch) {
                $derived = $this->semantic_resolve($e, $class, $hint);
                if ($derived !== null && is_a($derived, $class))
                    $final->addResult($derived, max(1,$base->getMatchedStrength($match) - 1), 'semantics');
            }
        }

        $final->sortResults();
        return $final;
    }

    /**
     * @param string $id
     * @param string $class
     * @param string $label
     * @param QuestionHelper|null $qh
     * @param InputInterface|null $in
     * @param OutputInterface|null $out
     * @return object|null
     */
    public function resolve_string(string $id, string $class, string $label = 'Principal Object', ?QuestionHelper $qh = null, ?InputInterface $in = null, ?OutputInterface $out = null): ?object {

        $sem = explode(':', $id);
        $hint = null;
        if (count($sem) > 1) {
            $hint = array_pop($sem);
            if (preg_match('/^\w*$/', $hint) !== false) {
                $id = implode(':', $sem);
            } else $hint = null;

            if ($hint === 'auto') $hint = null;
        }

        $sem = $this->resolve_as($id, $class, $hint);

        if (count($sem->getMatches(IdentifierSemantic::LikelyMatch)) === 1)
            return $sem->getMatchedObject(array_values($sem->getMatches(IdentifierSemantic::LikelyMatch))[0]);
        elseif (count($sem->getMatches(IdentifierSemantic::PerfectMatch)) === 1)
            return $sem->getMatchedObject(array_values($sem->getMatches(IdentifierSemantic::PerfectMatch))[0]);
        elseif (count($sem->getMatches(IdentifierSemantic::StrongMatch)) === 1)
            return $sem->getMatchedObject(array_values($sem->getMatches(IdentifierSemantic::StrongMatch))[0]);
        elseif ($qh !== null && $in !== null && $out !== null) {

            $l = [];
            foreach ( $sem->getMatches() as $match ) {
                $o = $sem->getMatchedObject($match);
                $l[$this->printObject($o)] = $o;
            }

            if (empty($l)) {
                if ($hint)
                    $out->writeln("<error>Your query '$id' ('$hint') for $label did not yield any results.</error>");
                else $out->writeln("<error>Your query '$id' for $label did not yield any results.</error>");
                return null;
            }

            if(count($l) === 1) {
                $out->writeln("Your query '$id' has been resolved to : <comment>{$l[array_keys($l)[0]]->getName()}</comment>");
                return $l[array_keys($l)[0]];
            }

            $result = $qh->ask($in, $out, new ChoiceQuestion(
                "Your input for '$label' is ambiguous. Please select an option from the list below:",
                array_keys($l)
            ) );

            return $l[$result];
        }
        else return null;

    }
}