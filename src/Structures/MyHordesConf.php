<?php


namespace App\Structures;

class MyHordesConf extends Conf
{
    const CONF_FATAL_MAIL_TARGET = 'fatalmail.target';
    const CONF_FATAL_MAIL_SOURCE = 'fatalmail.source';

    const CONF_TWINOID_SK = 'twinoid.sk';
    const CONF_TWINOID_ID = 'twinoid.id';

    const CONF_SOULPOINT_LIMIT_REMOTE        = 'soulpoints.limits.remote';
    const CONF_SOULPOINT_LIMIT_PANDA         = 'soulpoints.limits.panda';
    const CONF_SOULPOINT_LIMIT_BACK_TO_SMALL = 'soulpoints.limits.return_small';
    const CONF_SOULPOINT_LIMIT_CUSTOM        = 'soulpoints.limits.custom';

    const CONF_TOWNS_OPENMIN_REMOTE = 'towns.openmin.remote';
    const CONF_TOWNS_OPENMIN_PANDA  = 'towns.openmin.panda';
    const CONF_TOWNS_OPENMIN_SMALL  = 'towns.openmin.small';
    const CONF_TOWNS_OPENMIN_CUSTOM = 'towns.openmin.custom';

    const CONF_RAW_AVATARS = 'allow_raw_avatars';

    const CONF_COA_MAX_NUM = 'coalitions.size';
}