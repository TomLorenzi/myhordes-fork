<?php


namespace App\Annotations;

use App\Enum\SemaphoreScope;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation
 * @Target({"METHOD","CLASS"})
 */
class Semaphore implements ConfigurationInterface
{

    public ?string $value = null;

    public string $scope  = "global";

    /**
     * @inheritDoc
     */
    public function getAliasName(): string {
        return 'Semaphores';
    }

    /**
     * @inheritDoc
     */
    public function allowArray(): bool {
        return true;
    }

    public function getValue(): string {
        return $this->value ?? "__default_{$this->getScope()->value}";
    }

    public function getScope(): SemaphoreScope {
        return SemaphoreScope::from($this->scope);
    }
}