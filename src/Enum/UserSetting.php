<?php

namespace App\Enum;

enum UserSetting: string {
    case Flag                           = 'country-flag';
    case NoAutomaticNameManagement      = 'et-no-auto-names';
    case ClassicBankSort                = 'classic-bank-sort';
    case NoAutomaticThreadSubscription  = 'no-auto-follow-threads';
    case OpenDashboardInSameWindow      = 'mod-open-dash-same-window';
    case PreferredPronoun               = 'preferred-pronoun';
    case UseICU                         = 'use-icu';
    case UseExpertMode                  = 'use-expert-mode';
    case DisableEffects                 = 'disable-fx';
    case PostAs                         = 'mod-post-as';
    case PreferSmallAvatars             = 'prefer-small-avatars';
    case LimitTownListSize              = 'limit-town-lists';
    case NotifyMeWhenMentioned          = 'notify-on-mention-mode';
    case NotifyMeOnFriendRequest        = 'notify-on-friend-request';
    case PushNotifyMeOnPM               = 'push-notify-on-pm';
    case PushNotifyOnFriendTownJoin     = 'push-notify-on-town-join';
    case PushNotifyOnOfficialGroupChat  = 'push-notify-on-og';
    case PushNotifyOnModReport          = 'push-notify-on-mod';
    case ReorderActionButtonsBeyond     = 'reorder-action-buttons-beyond';
    case ReorderTownLocationButtons     = 'reorder-location-buttons-town';
    case DistinctionTop3                = 'distinctions-top-3';
    case TitleLanguage                  = 'title-language';
    case PrivateForumsOnTop             = 'private-forums-on-top';
    case PreferredPronounTitle          = 'preferred-pronoun-title';
    case LargerPMIcon                   = 'larger-pm-icon';

    /**
     * @return UserSetting[]
     */
    public static function migrateCases(): array {
        return [
            self::Flag, self::NoAutomaticNameManagement, self::ClassicBankSort, self::NoAutomaticThreadSubscription,
            self::OpenDashboardInSameWindow, self::PreferredPronoun, self::UseICU, self::UseExpertMode,
            self::DisableEffects, self::PostAs, self::PreferSmallAvatars
        ];
    }

    public function isToggleSetting(): bool {
        return match ($this) {
            UserSetting::NoAutomaticNameManagement,
            UserSetting::PrivateForumsOnTop,
            UserSetting::ClassicBankSort,
            UserSetting::NoAutomaticThreadSubscription,
            UserSetting::OpenDashboardInSameWindow,
            UserSetting::UseICU,
            UserSetting::UseExpertMode,
            UserSetting::DisableEffects,
            UserSetting::PreferSmallAvatars,
            UserSetting::LimitTownListSize,
            UserSetting::NotifyMeOnFriendRequest,
            UserSetting::PushNotifyMeOnPM,
            UserSetting::PushNotifyOnFriendTownJoin,
            UserSetting::ReorderActionButtonsBeyond,
            UserSetting::ReorderTownLocationButtons,
            UserSetting::LargerPMIcon,
            UserSetting::PushNotifyOnOfficialGroupChat,
            UserSetting::PushNotifyOnModReport => true,

            UserSetting::Flag,
            UserSetting::PreferredPronoun,
            UserSetting::PostAs,
            UserSetting::NotifyMeWhenMentioned,
            UserSetting::DistinctionTop3,
            UserSetting::TitleLanguage,
            UserSetting::PreferredPronounTitle => false,
        };
    }

    public function isExposedSetting(): bool {
        return match ($this) {
            UserSetting::NoAutomaticNameManagement,
            UserSetting::PrivateForumsOnTop,
            UserSetting::ClassicBankSort,
            UserSetting::NoAutomaticThreadSubscription,
            UserSetting::OpenDashboardInSameWindow,
            UserSetting::UseICU,
            UserSetting::UseExpertMode,
            UserSetting::DisableEffects,
            UserSetting::PreferSmallAvatars,
            UserSetting::LimitTownListSize,
            UserSetting::NotifyMeOnFriendRequest,
            UserSetting::PushNotifyMeOnPM,
            UserSetting::PushNotifyOnFriendTownJoin,
            UserSetting::ReorderActionButtonsBeyond,
            UserSetting::ReorderTownLocationButtons,
            UserSetting::Flag,
            UserSetting::PreferredPronoun,
            UserSetting::PostAs,
            UserSetting::NotifyMeWhenMentioned,
            UserSetting::TitleLanguage,
            UserSetting::PreferredPronounTitle,
            UserSetting::LargerPMIcon,
            UserSetting::PushNotifyOnOfficialGroupChat,
            UserSetting::PushNotifyOnModReport => true,

            UserSetting::DistinctionTop3          => false,
        };
    }

    public function defaultValue(): int|bool|array|string|null
    {
        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match ($this) {
            UserSetting::Flag                          => null,
            UserSetting::NoAutomaticNameManagement     => false,
            UserSetting::ClassicBankSort               => false,
            UserSetting::NoAutomaticThreadSubscription => false,
            UserSetting::OpenDashboardInSameWindow     => false,
            UserSetting::PreferredPronoun              => 0,
            UserSetting::UseICU                        => false,
            UserSetting::UseExpertMode                 => false,
            UserSetting::DisableEffects                => false,
            UserSetting::PostAs                        => null,
            UserSetting::PreferSmallAvatars            => false,
            UserSetting::LimitTownListSize             => true,
            UserSetting::NotifyMeWhenMentioned         => 0, // 0 = Disabled, 1 = Towns Only, 2 = Everywhere, 3 = Global Only
            UserSetting::NotifyMeOnFriendRequest       => true,
            UserSetting::PushNotifyMeOnPM              => true,
            UserSetting::PushNotifyOnFriendTownJoin    => true,
            UserSetting::ReorderActionButtonsBeyond    => false,
            UserSetting::ReorderTownLocationButtons    => true,
            UserSetting::DistinctionTop3               => [null,null,null],
            UserSetting::TitleLanguage                 => '_them',
            UserSetting::PrivateForumsOnTop            => true,
            UserSetting::PreferredPronounTitle         => 0,
            UserSetting::LargerPMIcon                  => false,
            UserSetting::PushNotifyOnOfficialGroupChat => true,
            UserSetting::PushNotifyOnModReport         => true,
        };
    }
}