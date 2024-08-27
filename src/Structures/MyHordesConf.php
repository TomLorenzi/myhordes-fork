<?php


namespace App\Structures;

class MyHordesConf extends Conf
{
    const CONF_DOMAINS = 'domains';
    const CONF_URLS = 'urls';

    const CONF_DOMAIN_REDIRECTION = 'redirect';

    const CONF_NIGHTLY_RETRIES = 'nightly.retries';
    const CONF_NIGHTLY_DATEMOD = 'nightly.date_modifier';

    const CONF_BACKUP_PATH        = 'backup.path';
    const CONF_BACKUP_COMPRESSION = 'backup.compression';
    const CONF_BACKUP_LIMITS_INC  = 'backup.limits.';
    const CONF_BACKUP_STORAGES    = 'backup.storages';

    const CONF_MOD_MAIL_DCHOOK = 'modmail.discord';

    const CONF_ANIM_MAIL_DCHOOK = 'animail.discord';

    const CONF_FATAL_MAIL_TARGET = 'fatalmail.target';
    const CONF_FATAL_MAIL_SOURCE = 'fatalmail.source';
    const CONF_FATAL_MAIL_DCHOOK = 'fatalmail.discord';

    const CONF_TWINOID_SK = 'twinoid.sk';
    const CONF_TWINOID_ID = 'twinoid.id';
    const CONF_TWINOID_DOMAIN = 'twinoid.domain';

    const CONF_ETWIN_REG        = 'etwin.reg';
    const CONF_ETWIN_SK         = 'etwin.sk';
    const CONF_ETWIN_CLIENT     = 'etwin.app';
    const CONF_ETWIN_AUTH       = 'etwin.auth';
    const CONF_ETWIN_AUTH_INTERNAL = 'etwin.internal';
    const CONF_ETWIN_API        = 'etwin.api';
    const CONF_ETWIN_DUAL_STACK = 'etwin.dual-stack';
    const CONF_ETWIN_RETURN_URI = 'etwin.return';

    const CONF_SOULPOINT_LIMIT_REMOTE        = 'soulpoints.limits.remote';
    const CONF_SOULPOINT_LIMIT_PANDA         = 'soulpoints.limits.panda';
    const CONF_SOULPOINT_LIMIT_BACK_TO_SMALL = 'soulpoints.limits.return_small';
    const CONF_SOULPOINT_LIMIT_CUSTOM        = 'soulpoints.limits.custom';

    const CONF_LANGS                         = 'langs';

    const CONF_TOWNS_AUTO_LANG = 'towns.autolangs';
    const CONF_TOWNS_OPENMIN_REMOTE = 'towns.openmin.remote';
    const CONF_TOWNS_OPENMIN_PANDA  = 'towns.openmin.panda';
    const CONF_TOWNS_OPENMIN_SMALL  = 'towns.openmin.small';
    const CONF_TOWNS_OPENMIN_CUSTOM = 'towns.openmin.custom';
    const CONF_TOWNS_MAX_PRIVATE = 'towns.max_private';

    const CONF_RAW_AVATARS = 'avatars.allow_raw';
    const CONF_AVATAR_SIZE_UPLOAD  = 'avatars.max_processing_size';
    const CONF_AVATAR_SIZE_STORAGE = 'avatars.max_storage_size';

    const CONF_COA_MAX_NUM = 'coalitions.size';
    const CONF_COA_MAX_DAYS_INACTIVITY = 'coalitions.inactive_after_days';

    const CONF_ANTI_GRIEF_SP  = 'anti-grief.min-sp';
    const CONF_ANTI_GRIEF_REG = 'anti-grief.reg-limit';

    const CONF_ANTI_GRIEF_FOREIGN_CAP    = 'anti-grief.foreign-cap';

    const CONF_IMPORT_ENABLED = 'soul_import.enabled';
    const CONF_IMPORT_READONLY = 'soul_import.readonly';
    const CONF_IMPORT_LIMITED = 'soul_import.limited';
    const CONF_IMPORT_SP_THRESHOLD = 'soul_import.sp_threshold';
    const CONF_IMPORT_TW_THRESHOLD = 'soul_import.tw_threshold';
    const CONF_IMPORT_TW_CUTOFF    = 'soul_import.tw_cutoff';

    const CONF_TOKEN_NEEDED_FOR_REGISTRATION = 'registration.token_only';

    const CONF_STAGING_ENABLED = 'staging.enabled';
    const CONF_STAGING_TOWN_ENABLED = 'staging.prototown.enabled';
    const CONF_STAGING_TOWN_DAYS    = 'staging.prototown.days';
    const CONF_STAGING_FEATURES    = 'staging.features';
    const CONF_STAGING_HERODAYS    = 'staging.herodays';

    const CONF_ISSUE_REPORTING_FALLBACK    = 'issue_tracking.fallback-url';
    const CONF_ISSUE_REPORTING_GITLAB    = 'issue_tracking.gitlab';

    const CONF_MAIL_DOMAINCAP    = 'mail.slice_domain';

    const CONF_OVERRIDE_AUTOPOST_ADDENDUM = 'override.autopost_addendum';
    const CONF_OVERRIDE_BLACKBOARD = 'override.blackboard';
    const CONF_OVERRIDE_VERSION = 'override.version';

    public function getAddendumFor(int $semantic, ?string $lang): ?string {
        if ($semantic === 0) return null;

        $addendum = $this->get( self::CONF_OVERRIDE_AUTOPOST_ADDENDUM, null );
        return match(true) {
            empty($addendum) => null,
            is_string($addendum) => $addendum,
            is_array($addendum) && array_key_exists( $semantic, $addendum ) && is_string( $addendum[$semantic] ) => $addendum[$semantic],
            is_array($addendum) && array_key_exists( $semantic, $addendum ) && is_array( $addendum[$semantic] ) => $addendum[$semantic][$lang ?? 'de'] ?? null,
            default => null
        };
    }

    public function getBlackboardOverrideFor(?string $lang): ?string {
        return $this->getSubKey( self::CONF_OVERRIDE_BLACKBOARD, $lang, null ) ?? $this->get( self::CONF_OVERRIDE_BLACKBOARD, null );
    }

    public function getVersionLinkOverrideFor(?string $lang): ?string {
        return $this->get( self::CONF_OVERRIDE_VERSION, null ) ?? $this->getSubKey( self::CONF_OVERRIDE_VERSION, $lang, null );
    }
}