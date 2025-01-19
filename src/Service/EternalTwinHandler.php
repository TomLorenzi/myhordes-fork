<?php


namespace App\Service;

use App\Enum\Configuration\MyHordesSetting;
use EternalTwinClient\API;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EternalTwinHandler extends API
{
    public function __construct( ConfMaster $confMaster, UrlGeneratorInterface $generator ) {
        $conf = $confMaster->getGlobalConf();

        $api_url   = $conf->get(MyHordesSetting::EternalTwinApi);
        $oauth_url = $conf->get(MyHordesSetting::EternalTwinAuth);
        $internal_host = $conf->get(MyHordesSetting::EternalTwinAuthInternal);

        parent::__construct(
            $conf->get(MyHordesSetting::EternalTwinSk),
            $conf->get(MyHordesSetting::EternalTwinApp),
            $conf->get(MyHordesSetting::EternalTwinReturnUri) ?? $generator->generate('twinoid_auth_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $internal_host ? "{$internal_host}/api/v1" : $api_url,
            "$oauth_url/authorize",
            $internal_host ? "{$internal_host}/oauth/token" : "$oauth_url/token",
        );
    }
}