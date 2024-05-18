<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessBuildingRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method building(string $name, ?bool $enabled)
 * @property string $building
 */
class BuildingRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessBuildingRequirement::class;
    }

    public function __call(string $name, array $arguments): self
    {
        if (($name === 'building') && count($arguments) === 2) return parent::__call( "{$name}_{$arguments[0]}", [$arguments[1]] );
        else return parent::__call( $name, $arguments );
    }

    public function getNeededBuildings(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'building_' ) && $value === true)
                $list[] = substr( $key, 9 );
        return $list;
    }

    public function getForbiddenBuildings(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'building_' ) && $value === false)
                $list[] = substr( $key, 9 );
        return $list;
    }

}