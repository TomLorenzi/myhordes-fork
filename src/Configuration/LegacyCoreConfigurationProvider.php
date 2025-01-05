<?php

namespace App\Configuration;

class LegacyCoreConfigurationProvider extends LegacyConfigurationProvider {

    public function __construct(array $global_yaml, array $local_yaml) {
        parent::__construct(array_merge($global_yaml,$local_yaml));
    }

}