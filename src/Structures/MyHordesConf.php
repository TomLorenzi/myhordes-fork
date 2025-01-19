<?php


namespace App\Structures;

use App\Enum\Configuration\MyHordesSetting;
use ArrayHelpers\Arr;

class MyHordesConf extends Conf
{
    public function getAddendumFor(int $semantic, ?string $lang): ?string {
        if ($semantic === 0) return null;

        $addendum = $this->get( MyHordesSetting::EventOverrideAutopostAddendum );
        return match(true) {
            empty($addendum) => null,
            is_string($addendum) => $addendum,
            is_array($addendum) && array_key_exists( $semantic, $addendum ) && is_string( $addendum[$semantic] ) => $addendum[$semantic],
            is_array($addendum) && array_key_exists( $semantic, $addendum ) && is_array( $addendum[$semantic] ) => $addendum[$semantic][$lang ?? 'de'] ?? null,
            default => null
        };
    }

    public function getBlackboardOverrideFor(?string $lang): ?string {
        $override = $this->get( MyHordesSetting::EventOverrideBlackboard );
        return is_array( $override ) ? Arr::get( $override, $lang ?? 'de' ) : $override;
    }

    public function getVersionLinkOverrideFor(?string $lang): ?string {
        $override = $this->get( MyHordesSetting::EventOverrideVersion );
        return is_array( $override ) ? Arr::get( $override, $lang ?? 'de' ) : $override;
    }
}