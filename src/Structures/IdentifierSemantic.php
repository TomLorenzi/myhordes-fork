<?php


namespace App\Structures;


use Doctrine\ORM\Mapping\Entity;

class IdentifierSemantic
{

    const NoMatch = 0;
    const GuessMatch = 1;
    const WeakMatch = 2;
    const StrongMatch = 3;
    const PerfectMatch = 4;
    const LikelyMatch = 5;

    private $_data = [];    /** [0] strength [1] property [2] entity */

    public function __construct() {}

    public function getMatchStrength(string $classname): int {
        return isset($this->_data[$classname]) ? $this->_data[$classname][0] : self::NoMatch;
    }

    private function internalAddResult(string $class, int $strength, string $property, object $e): bool {
        $classname = "$class:{$e->getId()}";
        if ($strength > $this->getMatchStrength($classname)) {
            $this->_data[$classname] = [$strength, $property, $e, $class];
            return true;
        } else return false;
    }

    public function addResult(object $e, int $strength, string $property): bool {
        $added = false;
        $class = get_class($e);
        while ($class) {
            $a = $this->internalAddResult($class, $strength, $property, $e);
            $added = $added || $a;
            $class = get_parent_class($class);
        }
        return $added;
    }

    public function sortResults(): void {
        uasort($this->_data, function ($a, $b) {
            return ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]) ?: ($a[2] <=> $b[2]);
        });
    }

    public function getMatches(int $min_level = 1, bool $exact_level = false): array {
        return array_filter( array_keys($this->_data), function(string $class) use ($min_level,$exact_level) {
            $m = $this->getMatchedStrength($class);
            return $m === $min_level || (!$exact_level && $m > $min_level);
        } );
    }

    public function getMatchedClass(string $class): ?string {
        return isset($this->_data[$class]) ? $this->_data[$class][3] : null;
    }

    public function getMatchedObject(string $class): ?object {
        return isset($this->_data[$class]) ? $this->_data[$class][2] : null;
    }

    public function getMatchedProperty(string $class): ?string {
        return isset($this->_data[$class]) ? $this->_data[$class][1] : null;
    }

    public function getMatchedStrength(string $class): int {
        return isset($this->_data[$class]) ? $this->_data[$class][0] : self::NoMatch;
    }
}