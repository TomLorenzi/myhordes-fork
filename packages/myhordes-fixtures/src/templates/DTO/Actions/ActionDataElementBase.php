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
        $this->sortAtomList();
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

    private function sortAtomList(): void {
        $tmp = $this->atomList;
        $references = array_unique( array_filter( array_map( fn(Atom $a) => $a->sort->word->stable() ? $a->getClass() : null, $tmp ) ) );

        usort( $tmp, fn(Atom $a, Atom $b) => $a->sort->priority <=> $b->sort->priority );

        $default = array_reverse( array_filter( $tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::Default ) );

        $start = array_reverse( array_filter( $tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::Start ) );
        $end = array_values( array_filter( $tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::End ) );

        /** @var Atom[] $unstable_tmp */
        $unstable_tmp = array_merge(
            array_reverse( array_filter( $tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::Before ) ),
            array_values( array_filter( $tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::After ) )
        );

        $this->atomList = array_merge( $start, $default, $end );

        $unstable = [];
        foreach ($unstable_tmp as $unstable_element) {
            if (!array_key_exists( $unstable_element->getClass(), $unstable )) $unstable[$unstable_element->getClass()] = [];
            $unstable[$unstable_element->getClass()][] = $unstable_element;
        }

        while (!empty($unstable)) {
            $changed = false;

            foreach ($unstable as $ref => $unstable_tmp)
                if (in_array( $ref, $references )) {

                    $hitting = array_filter( $this->atomList, fn(Atom $a) => $a->getClass() === $ref );

                    $first = array_key_first( $hitting );
                    $last = array_key_first( $hitting );

                    $before = array_values( array_filter( $unstable_tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::Before ) );
                    $after  = array_values( array_filter( $unstable_tmp, fn(Atom $a) => $a->sort->word === SortDefinitionWord::After ) );

                    $block_a = array_slice( $this->atomList, 0, $first - 1 );
                    $block_b = array_slice( $this->atomList, $first, ($last - $first) + 1 );
                    $block_c = array_slice( $this->atomList, $last + 1 );

                    $this->atomList = array_merge( $block_a, $before, $block_b, $after, $block_c );
                    $references = array_unique( array_filter( array_map( fn(Atom $a) => $a->getClass(), $this->atomList ) ) );

                    $changed = true;
                    unset( $unstable[$ref] );
                    break;
                }

            if ($changed)
                $references = array_unique( array_map( fn(Atom $a) => $a->getClass(), $this->atomList ) );
            else {
                $before = array_values( array_filter( $unstable, fn(Atom $a) => $a->sort->word === SortDefinitionWord::Before ) );
                $after  = array_values( array_filter( $unstable, fn(Atom $a) => $a->sort->word === SortDefinitionWord::After ) );
                $this->atomList = array_merge( $before, $this->atomList, $after );
                $unstable = [];
            }

        }

    }

    /**
     * @param Atom[] $v
     * @return self
     */
    public function atomList(array $v): self {
        $this->atomList = $v;
        $this->sortAtomList();
        return $this;
    }

    public function beforeSerialization(): void
    {
        parent::beforeSerialization();
        $this->sortAtomList();
    }

    public function afterSerialization(): void
    {
        parent::afterSerialization();
        $class = static::getAtomClass();
        $this->atomList = array_map( fn(array|Atom $a) => is_array($a) ? call_user_func("$class::fromArray", $a ) : call_user_func("$class::fromArray", $a->toArray() ), $this->atomList );
        $this->sortAtomList();
    }
}