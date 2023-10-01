<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\CitizenRole;

class BuildingQueryTownRoleEnabledData
{

    /**
     * @param CitizenRole $query
     * @return BuildingQueryTownRoleEnabledData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( CitizenRole $role ): void {
        $this->role = $role;
    }

    public readonly CitizenRole $role;

    public bool $enabled = false;
}