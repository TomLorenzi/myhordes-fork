<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessProfessionRoleRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method hero(?bool $v)
 * @property ?bool hero
 * @method job(string $name, ?bool $enabled)
 * @method role(string $name, ?bool $enabled)
 * @property ?bool $job_none
 * @property ?bool $job_basic
 * @property ?bool $job_collec
 * @property ?bool $job_guardian
 * @property ?bool $job_hunter
 * @property ?bool $job_tamer
 * @property ?bool $job_tech
 * @property ?bool $job_shaman
 * @property ?bool $job_survivalist
 * @property ?bool $role_shaman
 * @property ?bool $role_guide
 * @property ?bool $role_ghoul
 * @property ?bool $role_cata
 */
class ProfessionRoleRequirement extends RequirementsAtom {

    public function __call(string $name, array $arguments): self
    {
        if (($name === 'job' || $name === 'role') && count($arguments) === 2) return parent::__call( "{$name}_{$arguments[0]}", [$arguments[1]] );
        else return parent::__call( $name, $arguments );
    }

    public function getNeededJobs(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'job_' ) && $value === true)
                $list[] = substr( $key, 4 );
        return $list;
    }

    public function getForbiddenJobs(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'job_' ) && $value === false)
                $list[] = substr( $key, 4 );
        return $list;
    }

    public function getNeededRoles(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'role_' ) && $value === true)
                $list[] = substr( $key, 5 );
        return $list;
    }

    public function getForbiddenRoles(): array {
        $list = [];
        foreach ($this->data as $key => $value)
            if (str_starts_with( $key, 'role_' ) && $value === false)
                $list[] = substr( $key, 5 );
        return $list;
    }

    public function getClass(): string
    {
        return ProcessProfessionRoleRequirement::class;
    }
}