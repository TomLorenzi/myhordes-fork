<?php

namespace App\Configuration;

use App\Event\Game\EventHooks\Christmas\NightlyEvent;
use App\Translation\T;
use MyHordes\Plugins\Interfaces\ConfigurationProviderInterface;

class LegacyCoreConfigurationProvider extends LegacyConfigurationProvider {

    public function __construct(array $global_yaml, array $local_yaml) {
        parent::__construct(array_merge($global_yaml,$local_yaml));
    }

}