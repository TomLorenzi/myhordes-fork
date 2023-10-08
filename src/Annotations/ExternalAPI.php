<?php


namespace App\Annotations;

use App\Enum\ExternalAPIInterface;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ExternalAPI implements CustomAttribute
{
    public function __construct(
        public bool $user = true,
        public bool $app  = true,
        public int  $cost = 1,
        public bool $fefe = true,
        public ExternalAPIInterface $api = ExternalAPIInterface::GENERIC
    ) { }

    public static function getAliasName(): string {
        return 'ExternalAPI';
    }


    public static function isRepeatable(): bool
    {
        return false;
    }
}