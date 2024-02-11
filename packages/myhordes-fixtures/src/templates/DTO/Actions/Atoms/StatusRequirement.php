<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessStatusRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method shunned(?bool $v)
 * @property ?bool $shunned
 * @method status(string $name, ?bool $enabled)
 */
class StatusRequirement extends RequirementsAtom {

    public function __call(string $name, array $arguments): self
    {
        if ($name === 'status' && count($arguments) === 2) return parent::__call( "{$name}_{$arguments[0]}", [$arguments[1]] );
        else return parent::__call( $name, $arguments );
    }

    public function getNeededStatus(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'status_' ) && $value === true)
                $list[] = substr( $key, 7 );
        return $list;
    }

    public function getForbiddenStatus(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'status_' ) && $value === false)
                $list[] = substr( $key, 7 );
        return $list;
    }

    public function getClass(): string
    {
        return ProcessStatusRequirement::class;
    }
}