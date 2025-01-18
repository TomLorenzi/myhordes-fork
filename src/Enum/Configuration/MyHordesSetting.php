<?php

namespace App\Enum\Configuration;

enum MyHordesSetting: string implements Configuration
{
    //<editor-fold desc="Core Settings">
    case Languages = 'langs';
    //</editor-fold>

    //<editor-fold desc="Domain Settings">
    case Domains = 'domains';
    case URLs = 'urls';
    case DomainRedirect = 'redirect';
    case MailDomainCap = 'mail.slice_domain';
    //</editor-fold>

    //<editor-fold desc="Nightly Attack Settings">
    case NightlyAttackRetries = 'nightly.retries';
    case NightlyAttackDateModifier = 'nightly.date_modifier';
    //</editor-fold>

    //<editor-fold desc="Backup Settings">
    case BackupPath = 'backup.path';
    case BackupCompression = 'backup.compression';
    case BackupLimits = 'backup.limits';
    //</editor-fold>

    //<editor-fold desc="External Communication Settings">
    case HookModDiscord = 'modmail.discord';
    case HookAnimDiscord = 'animail.discord';
    case HookFatalMailTo = 'fatalmail.target';
    case HookFatalMailFrom = 'fatalmail.source';
    case HookFatalDiscord = 'fatalmail.discord';
    //</editor-fold>

    //<editor-fold desc="EternalTwin Settings">
    case EternalTwinReg = 'etwin.reg';
    case EternalTwinSk = 'etwin.sk';
    case EternalTwinApp = 'etwin.app';
    case EternalTwinAuth = 'etwin.auth';
    case EternalTwinAuthInternal = 'etwin.internal';
    case EternalTwinApi = 'etwin.api';
    case EternalTwinDualStackEnabled = 'etwin.dual-stack';
    case EternalTwinReturnUri = 'etwin.return';
    //</editor-fold>

    //<editor-fold desc="Soul Point Limit Settings">
    case SoulPointRequirementRemote = 'soulpoints.limits.remote';
    case SoulPointRequirementPanda = 'soulpoints.limits.panda';
    case SoulPointRequirementSmallReturn = 'soulpoints.limits.return_small';
    case SoulPointRequirementCustom = 'soulpoints.limits.custom';
    //</editor-fold>

    //<editor-fold desc="Town Settings">
    case TownGeneratorLanguages = 'towns.autolangs';
    case TownGeneratorMinRemote = 'towns.openmin.remote';
    case TownGeneratorMinPanda = 'towns.openmin.panda';
    case TownGeneratorMinSmall = 'towns.openmin.small';
    case TownGeneratorMinCustom = 'towns.openmin.custom';
    case TownLimitMaxPrivate = 'towns.max_private';
    //</editor-fold>

    //<editor-fold desc="Town Settings">
    case AvatarMaxSizeUpload = 'avatars.max_processing_size';
    case AvatarMaxSizeProcess = 'avatars.max_storage_size';
    //</editor-fold>

    //<editor-fold desc="Small Coalition Settings">
    case CoalitionMaxSize = 'coalitions.size';
    case CoalitionMaxInactivityDays = 'coalitions.inactive_after_days';
    //</editor-fold>

    //<editor-fold desc="Anti Grief Settings">
    case AntiGriefMinSp = 'anti-grief.min-sp';
    case AntiGriefRegistrationLimit = 'anti-grief.reg-limit';
    case AntiGriefForeignCap = 'anti-grief.foreign-cap';
    //</editor-fold>

    //<editor-fold desc="Soul Import Settings">
    case SoulImportEnabled = 'soul_import.enabled';
    case SoulImportReadOnly = 'soul_import.readonly';
    case SoulImportLimitsActive = 'soul_import.limited';
    case SoulImportLimitSpThreshold = 'soul_import.sp_threshold';
    case SoulImportLimitTwThreshold = 'soul_import.tw_threshold';
    case SoulImportLimitTwCutoff = 'soul_import.tw_cutoff';
    //</editor-fold>

    //<editor-fold desc="Staging Server Settings">
    case StagingRegistrationTokenNeeded = 'registration.token_only';
    case StagingSettingsEnabled = 'staging.enabled';
    case StagingProtoTownEnabled = 'staging.prototown.enabled';
    case StagingProtoTownDuration = 'staging.prototown.days';
    case StagingProtoFeatures = 'staging.features';
    case StagingProtoHeroDays = 'staging.herodays';
    case StagingProtoHxp = 'staging.hxp';
    //</editor-fold>

    //<editor-fold desc="Issue Reporting Settings">
    case IssueReportingFallbackUrl = 'issue_tracking.fallback-url';
    case IssueReportingGitlabToken = 'issue_tracking.gitlab';
    //</editor-fold>

    //<editor-fold desc="Event Override Settings">
    case EventOverrideAutopostAddendum = 'override.autopost_addendum';
    case EventOverrideBlackboard = 'override.blackboard';
    case EventOverrideVersion = 'override.version';
    //</editor-fold>

    //<editor-fold desc="General Settings">
    case HxpFirstSeason = 'hxp.first_season';
    //</editor-fold>

    public function abstract(): false
    {
        return false;
    }

    public function parent(): null {
        return null;
    }

    public function children(): array
    {
        return [];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function key(): string {
        return $this->value;
    }

    public function default(): null|bool|int|float|string|array
    {
        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match ($this) {
            self::Languages => [],

            self::Domains,
            self::URLs,
            self::DomainRedirect,
                => [],
            self::MailDomainCap => 0,

            self::NightlyAttackRetries => 3,
            self::NightlyAttackDateModifier => 'tomorrow',

            self::BackupPath,
            self::BackupCompression,
                => null,
            self::BackupLimits => [],

            self::HookModDiscord,
            self::HookAnimDiscord,
            self::HookFatalMailFrom,
            self::HookFatalMailTo,
            self::HookFatalDiscord,
                => null,

            self::EternalTwinReg,
            self::EternalTwinSk,
            self::EternalTwinApp,
            self::EternalTwinAuth,
            self::EternalTwinApi,
            self::EternalTwinReturnUri,
                => null,
            self::EternalTwinDualStackEnabled => true,

            self::SoulPointRequirementRemote,
            self::SoulPointRequirementSmallReturn,
            self::SoulPointRequirementCustom
                => 0,
            self::SoulPointRequirementPanda => 200,

            self::TownGeneratorLanguages => [],
            self::TownGeneratorMinRemote,
            self::TownGeneratorMinPanda,
                => 1,
            self::TownGeneratorMinSmall,
            self::TownGeneratorMinCustom,
                => 0,
            self::TownLimitMaxPrivate => 10,

            self::AvatarMaxSizeUpload => 3145728,
            self::AvatarMaxSizeProcess => 1048576,

            self::CoalitionMaxSize,
            self::CoalitionMaxInactivityDays => 5,

            self::AntiGriefMinSp => 20,
            self::AntiGriefRegistrationLimit => 2,
            self::AntiGriefForeignCap => 3,

            self::SoulImportEnabled => true,
            self::SoulImportReadOnly,
            self::SoulImportLimitsActive
                => false,
            self::SoulImportLimitSpThreshold,
            self::SoulImportLimitTwThreshold,
            self::SoulImportLimitTwCutoff
                => -1,

            self::StagingRegistrationTokenNeeded,
            self::StagingSettingsEnabled,
            self::StagingProtoTownEnabled,
                => false,
            self::StagingProtoFeatures => [],
            self::StagingProtoTownDuration,
            self::StagingProtoHeroDays,
            self::StagingProtoHxp,
                => 0,

            self::IssueReportingFallbackUrl => '',
            self::IssueReportingGitlabToken => null,

            self::EventOverrideAutopostAddendum,
            self::EventOverrideBlackboard,
            self::EventOverrideVersion,
                => null,

            self::HxpFirstSeason => 17,

            default => null,
        };
    }

    public function fallback(): array
    {
        return [];
    }

    /**
     * @return MyHordesSetting[]
     */
    public static function validCases(): array
    {
        return array_filter(self::cases(), fn(MyHordesSetting $s) => !$s->abstract());
    }

    public function merge(mixed $old, mixed $new): mixed
    {
        return $new;
    }

    public function translationKey(): string
    {
        return "cfg_mh_" . str_replace(".", "_", $this->value);
    }
}