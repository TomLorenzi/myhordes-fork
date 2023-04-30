<?php


namespace App\Annotations;

use App\Enum\SemaphoreScope;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation
 * @Target({"METHOD","CLASS"})
 */
class Toaster implements ConfigurationInterface
{
    public bool $full = true;

    /**
     * @inheritDoc
     */
    public function getAliasName(): string {
        return 'Toaster';
    }

    /**
     * @inheritDoc
     */
    public function allowArray(): bool {
        return false;
    }

    public function fullSecurity(): bool {
        return $this->full;
    }
}