<?php


namespace App\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Toaster implements CustomAttribute
{
    public function __construct(
        public bool $full = true
    ) { }

    public static function getAliasName(): string {
        return 'Toaster';
    }

    public static function isRepeatable(): bool
    {
        return false;
    }


    public function fullSecurity(): bool {
        return $this->full;
    }
}