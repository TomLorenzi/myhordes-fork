<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Enum\SortDefinitionWord;
use MyHordes\Fixtures\DTO\Element;

/**
 * @property string $identifier
 * @method self identifier(string $v)
 * @property Atom[] $atomList
 * @property int $type
 * @method self type(int $v)
 * @property string $text
 * @method self text(string $v)
 * @property string $text_key
 * @method self text_key(string $v)
 * @property array $collection
 * @method self collection(array $v)
 */
abstract class ActionDataElementBase extends Element {

    public static function getAtomClass(): string {
        return Atom::class;
    }

    public function add(Atom $atom): self {
        $this->atomList = array_merge($this->atomList ?? [], [$atom]);
        return $this;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param callable $c
     * @psalm-param Closure(T):void $c
     * @return self
     */
    public function first(string $class, callable $c): self {
        $c(array_values(array_filter($this->atomList, fn(Atom $a) => is_a( $a, $class ) || is_a( $a->getClass(), $class, true )))[0]);
        return $this;
    }

    public function clear( string $class ): self {
        $this->atomList = array_values( array_filter( $this->atomList, fn(Atom $a) =>
            !is_a( $a, $class ) && !is_a( $a->getClass(), $class, true )
        ) );
        return $this;
    }

    /**
     * @param Atom[] $v
     * @return self
     */
    public function atomList(array $v): self {
        $this->atomList = $v;
        return $this;
    }

    public function afterSerialization(): void
    {
        parent::afterSerialization();
        $class = static::getAtomClass();
        $this->atomList = array_map( fn(array|Atom $a) => is_array($a) ? call_user_func("$class::fromArray", $a ) : call_user_func("$class::fromArray", $a->toArray() ), $this->atomList );
    }
}