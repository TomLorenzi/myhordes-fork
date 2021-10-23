<?php


namespace App\Structures;

class MyHordesConf extends Conf
{
    const CONF_DOMAINS = 'domains';
    const CONF_URLS = 'urls';

    const CONF_NIGHTLY_RETRIES = 'nightly.retries';
    const CONF_NIGHTLY_DATEMOD = 'nightly.date_modifier';

    const CONF_BACKUP_PATH        = 'backup.path';
    const CONF_BACKUP_COMPRESSION = 'backup.compression';
    const CONF_BACKUP_LIMITS_INC      = 'backup.limits.';

    const CONF_FATAL_MAIL_TARGET = 'fatalmail.target';
    const CONF_FATAL_MAIL_SOURCE = 'fatalmail.source';

    const CONF_TWINOID_SK = 'twinoid.sk';
    const CONF_TWINOID_ID = 'twinoid.id';
    const CONF_TWINOID_DOMAIN = 'twinoid.domain';

    const CONF_ETWIN_REG        = 'etwin.reg';
    const CONF_ETWIN_SK         = 'etwin.sk';
    const CONF_ETWIN_CLIENT     = 'etwin.app';
    const CONF_ETWIN_AUTH       = 'etwin.auth';
    const CONF_ETWIN_API        = 'etwin.api';
    const CONF_ETWIN_DUAL_STACK = 'etwin.dual-stack';
    const CONF_ETWIN_RETURN_URI = 'etwin.return';

    const CONF_SOULPOINT_LIMIT_REMOTE        = 'soulpoints.limits.remote';
    const CONF_SOULPOINT_LIMIT_PANDA         = 'soulpoints.limits.panda';
    const CONF_SOULPOINT_LIMIT_BACK_TO_SMALL = 'soulpoints.limits.return_small';
    const CONF_SOULPOINT_LIMIT_CUSTOM        = 'soulpoints.limits.custom';

    const CONF_TOWNS_AUTO_LANG = 'towns.autolangs';
    const CONF_TOWNS_OPENMIN_REMOTE = 'towns.openmin.remote';
    const CONF_TOWNS_OPENMIN_PANDA  = 'towns.openmin.panda';
    const CONF_TOWNS_OPENMIN_SMALL  = 'towns.openmin.small';
    const CONF_TOWNS_OPENMIN_CUSTOM = 'towns.openmin.custom';

    const CONF_RAW_AVATARS = 'avatars.allow_raw';
    const CONF_AVATAR_SIZE_UPLOAD  = 'avatars.max_processing_size';
    const CONF_AVATAR_SIZE_STORAGE = 'avatars.max_storage_size';

    const CONF_COA_MAX_NUM = 'coalitions.size';

    const CONF_ANTI_GRIEF_SP  = 'anti-grief.min-sp';
    const CONF_ANTI_GRIEF_REG = 'anti-grief.reg-limit';

    const CONF_IMPORT_ENABLED = 'soul_import.enabled';
    const CONF_IMPORT_READONLY = 'soul_import.readonly';
    const CONF_IMPORT_LIMITED = 'soul_import.limited';
    const CONF_IMPORT_SP_THRESHOLD = 'soul_import.sp_threshold';
    const CONF_IMPORT_TW_THRESHOLD = 'soul_import.tw_threshold';
}