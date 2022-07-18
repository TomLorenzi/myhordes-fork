<?php


namespace App\Annotations;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ExternalAPI implements ConfigurationInterface
{

    public ?string $value = null;
    public bool $user     = true;
    public bool $app      = true;
    public int  $cost     = 1;
    public bool $fefe     = true;

    /**
     * @inheritDoc
     */
    public function getAliasName(): string {
        return 'ExternalAPI';
    }

    /**
     * @inheritDoc
     */
    public function allowArray(): bool {
        return false;
    }



}