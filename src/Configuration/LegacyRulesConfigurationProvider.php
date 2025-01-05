<?php

namespace App\Configuration;

class LegacyRulesConfigurationProvider extends LegacyConfigurationProvider {

    public function __construct(array $global_yaml, array $local_yaml) {
        parent::__construct(array_replace_recursive($global_yaml,$local_yaml));
    }

}