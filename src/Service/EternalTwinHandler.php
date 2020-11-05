<?php


namespace App\Service;

use App\Structures\MyHordesConf;
use EternalTwinClient\API;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EternalTwinHandler extends API
{
    public function __construct( ConfMaster $confMaster, UrlGeneratorInterface $generator ) {
        $conf = $confMaster->getGlobalConf();

        $api_url   = $conf->get(MyHordesConf::CONF_ETWIN_API);
        $oauth_url = $conf->get(MyHordesConf::CONF_ETWIN_AUTH);

        parent::__construct(
            $conf->get(MyHordesConf::CONF_ETWIN_SK, null),
            $conf->get(MyHordesConf::CONF_ETWIN_CLIENT, null),
            $generator->generate('twinoid_auth_endpoint', [], UrlGeneratorInterface::NETWORK_PATH),
            $api_url,
            "$oauth_url/authorize",
            "$oauth_url/token",
        );
    }
}