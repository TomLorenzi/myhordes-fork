<?php

namespace App\Traits\System;

use ArrayHelpers\Arr;
use Composer\Semver\Semver;
use MyHordes\Prime\MyHordesPrimeBundle;

trait PrimeInfo
{
    protected static function primePackageOverrideIsInstalled() {
        return Arr::has( \Composer\InstalledVersions::getAllRawData(), '0.versions.myhordes/prime.replaced.0' );
    }

    protected static function primePackageVersion() {
        return \Composer\InstalledVersions::getVersion(MyHordesPrimeBundle::PKG);
    }

    protected static function defaultPrimePackageVersionIdentifier(): string {
        return 'myhordes/prime@1.0.0.0';
    }

    protected static function buildPrimePackageVersionIdentifier( ?string $package = null, ?string $version = null ): string {
        return ($package ?? MyHordesPrimeBundle::PKG) . '@' . ($version ?? self::primePackageVersion());
    }

    protected static function parsePrimePackageVersionIdentifier( ?string $identifier, ?string &$package, ?string &$version ): void {
        [ $package, $version ] = explode( '@', $identifier ?? self::defaultPrimePackageVersionIdentifier() );
    }

    protected static function primePackageVersionIdentifierSatisfies( ?string $identifier, string $satisfies, ?string $package = null, bool $match_shim = false ): bool {
        self::parsePrimePackageVersionIdentifier( $identifier, $used_package, $used_version );
        return ( ($package ?? MyHordesPrimeBundle::PKG) === $used_package || ( $match_shim && $used_package === 'myhordes/prime' ) )
            ? Semver::satisfies( $used_version, $satisfies )
            : false;
    }
}