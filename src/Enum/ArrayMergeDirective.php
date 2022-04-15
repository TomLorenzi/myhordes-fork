<?php

namespace App\Enum;

enum ArrayMergeDirective: int {

    case Keep               = 1;
    case Replace            = 2;
    case None               = 3;
    case Append             = 4;
    case AppendRecursive    = 5;
    case Overwrite          = 6;
    case OverwriteRecursive = 7;

    public function apply(array $baseArray, array $newArray): array {
        return match ($this) {
            self::Keep => $baseArray,
            self::Replace => $newArray,
            self::None => [],
            self::Append => array_merge( $newArray, $baseArray ),
            self::AppendRecursive => array_merge_recursive( $newArray, $baseArray ),
            self::Overwrite => array_merge( $baseArray, $newArray ),
            self::OverwriteRecursive => array_merge_recursive( $baseArray, $newArray ),
        };
    }

    public function dominant( mixed $a, mixed $b ) {
        return match ($this) {
            self::Keep, self::Append, self::AppendRecursive => $a,
            self::Replace, self::Overwrite, self::OverwriteRecursive => $b,
            self::None => null,
        };
    }

    public function recessive( mixed $a, mixed $b ) {
        return match ($this) {
            self::Keep, self::Append, self::AppendRecursive => $b,
            self::Replace, self::Overwrite, self::OverwriteRecursive => $a,
            self::None => null,
        };
    }
}