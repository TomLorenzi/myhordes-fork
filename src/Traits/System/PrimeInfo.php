<?php

namespace App\Traits\System;

use ArrayHelpers\Arr;
use Composer\Semver\Semver;
use MyHordes\Prime\MyHordesPrimeBundle;

trait PrimeInfo
{
    protected static function primePackageVersion(): string {
        return '4.0.0.0';
    }

    protected static function buildPrimePackageVersionIdentifier( ?string $package = null, ?string $version = null ): string {
        return ($package ?? 'myhordes/prime') . '@' . ($version ?? self::primePackageVersion());
    }

    protected static function parsePrimePackageVersionIdentifier( ?string $identifier, ?string &$package, ?string &$version ): void {
        [ $package, $version ] = explode( '@', $identifier ?? 'myhordes/prime@1.0.0.0' );
    }

    protected static function primePackageVersionIdentifierSatisfies( ?string $identifier, string $satisfies, ?string $package = null ): bool {
        self::parsePrimePackageVersionIdentifier( $identifier, $used_package, $used_version );
        return ( ($package ?? 'myhordes/prime') === $used_package )
            ? Semver::satisfies( $used_version, $satisfies )
            : false;
    }
}