<?php


namespace App\Annotations;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class AdminLogProfile implements ConfigurationInterface
{

    public ?string $value = null;
    public bool $enabled = false;
    public array $mask = [];

    /**
     * @inheritDoc
     */
    public function getAliasName(): string {
        return 'AdminLogProfile';
    }

    /**
     * @inheritDoc
     */
    public function allowArray(): bool {
        return false;
    }

    public function enableLogging(): bool {
        return $this->enabled || strtolower($this->value ?? '') === "on";
    }

    public function isMasked( string $parameter ): bool {
        return in_array( $parameter, $this->mask );
    }

}