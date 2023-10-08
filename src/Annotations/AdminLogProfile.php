<?php
namespace App\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AdminLogProfile implements CustomAttribute
{
    public function __construct(
        public bool  $enabled = true,
        public array $mask = []
    ) {}

    public static function getAliasName(): string {
        return 'AdminLogProfile';
    }

    public static function isRepeatable(): bool {
        return false;
    }

    public function enableLogging(): bool {
        return $this->enabled;
    }

    public function isMasked( string $parameter ): bool {
        return in_array( $parameter, $this->mask );
    }

}