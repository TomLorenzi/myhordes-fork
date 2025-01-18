<?php

namespace App\Service\Actions\EMail;

use App\Enum\Configuration\MyHordesSetting;
use App\Service\ConfMaster;

class GetEMailDomainAction
{
    public function __construct(private readonly ConfMaster $conf) { }

    public function __invoke(): string
    {
        $domain = ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $domain_slice = $this->conf->getGlobalConf()->get( MyHordesSetting::MailDomainCap );
        if ($domain_slice >= 2)
            $domain = implode('.', array_slice( explode( '.', $domain ), -$domain_slice ));

        return $domain;
    }
}