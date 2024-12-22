<?php

namespace MyHordes\Fixtures\Service;

use App\Entity\UserGroup;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class PermissionDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            ['name'=>'[users]',      'type'=> UserGroup::GroupTypeDefaultUserGroup],
            ['name'=>'[elevated]',   'type'=> UserGroup::GroupTypeDefaultElevatedGroup],
            ['name'=>'[oracles]',    'type'=> UserGroup::GroupTypeDefaultOracleGroup],
            ['name'=>'[mods]',       'type'=> UserGroup::GroupTypeDefaultModeratorGroup],
            ['name'=>'[admins]',     'type'=> UserGroup::GroupTypeDefaultAdminGroup],
            ['name'=>'[animaction]', 'type'=> UserGroup::GroupTypeDefaultAnimactorGroup],
            ['name'=>'[artistic]',   'type'=> UserGroup::GroupTypeDefaultArtisticGroup],
            ['name'=>'[dev]',        'type'=> UserGroup::GroupTypeDefaultDevGroup],
        ]);
    }
}