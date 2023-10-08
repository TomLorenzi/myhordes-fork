<?php


namespace App\Annotations;

use App\Enum\SemaphoreScope;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Semaphore implements CustomAttribute
{
    public function __construct(
        public ?string $value = null,
        public string $scope = "global",
    ) { }

    public static function getAliasName(): string {
        return 'Semaphores';
    }

    public static function isRepeatable(): bool
    {
        return true;
    }

    public function getValue(): string {
        return $this->value ?? "__default_{$this->getScope()->value}";
    }

    public function getScope(): SemaphoreScope {
        return SemaphoreScope::from($this->scope);
    }


}