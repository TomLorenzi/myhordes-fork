<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessConfigRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method event(?string $v)
 * @property ?string $event
 * @method config(string $v, mixed $expected)
 */
class ConfigRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessConfigRequirement::class;
    }

    public function __call(string $name, array $arguments): self
    {
        if ($name === 'config' && count($arguments) === 2) return parent::__call( "config_{$arguments[0]}", [$arguments[1]] );
        else return parent::__call( $name, $arguments );
    }

    public function getConfigRequirements(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'config_' ))
                $list[] = [substr( $key, 7 ), $value];
        return $list;
    }

}