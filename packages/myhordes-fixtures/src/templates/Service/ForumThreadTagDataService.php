<?php

namespace MyHordes\Fixtures\Service;

use App\Entity\ForumUsagePermissions;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ForumThreadTagDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            'bugs'   => [ 'color' => '3b1c32', 'label' => 'Fehler' ],
            'help'   => [ 'color' => 'ca054d', 'label' => 'Hilfe (forum)'  ],
            'update' => [ 'color' => '3d405b', 'label' => 'Changelog', 'mask' => ForumUsagePermissions::PermissionPostAsDev ],
            'event'  => [ 'color' => '43aa8b', 'label' => 'Event' ],
            'rp'     => [ 'color' => 'd4a373', 'label' => 'RP' ],
            'official' => ['color' => 'aa0000', 'label' => 'Offiziell', 'mask' => ForumUsagePermissions::PermissionPostAsCrow ],

            'dsc_update' => [ 'color' => null, 'label' => 'Update' ],
            'dsc_post'   => [ 'color' => null, 'label' => 'Post' ],
            'dsc_disc'   => [ 'color' => null, 'label' => 'Disk.' ],
            'dsc_guide'  => [ 'color' => null, 'label' => 'Guide' ],
            'dsc_orga'   => [ 'color' => null, 'label' => 'Orga.' ],
            'dsc_sugg'   => [ 'color' => null, 'label' => 'Vorschlag' ],
            'dsc_salc'   => [ 'color' => null, 'label' => 'SALC' ],
            'dsc_proj'   => [ 'color' => null, 'label' => 'Projekt' ],
        ]);
    }
}