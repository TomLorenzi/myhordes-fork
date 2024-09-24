<?php

namespace App\Enum\Configuration;

enum CitizenProperties: string implements Configuration
{
    //<editor-fold desc="Enabled Features">
    case Section_Features = '--section--/Features';
    case EnableBlackboard = 'features.blackboard';
    case EnableGroupMessages = 'features.group_messages';
    case EnableBuildingRecommendation = 'features.building_recommendation';
    case EnableProWatchman = 'features.pro.watchman';
    case EnableProCamper = 'features.pro.camper';
    case EnableAdvancedTheft = 'features.advanced_theft';
    case EnableClairvoyance = 'features.clairvoyance.base';
    case EnableOmniscience = 'features.clairvoyance.expanded';

    //</editor-fold>

    //<editor-fold desc="Property Values">
    case Section_Properties = '--section--/Properties';
    case TownDefense = 'props.town_defense';
    case AnonymousMessageLimit = 'props.limit.anonymous.messages';
    case AnonymousPostLimit = 'props.limit.anonymous.posts';
    case ComplaintLimit = 'props.limit.anonymous.complaint';
    case LogManipulationLimit = 'props.limit.log_manipulation';
    case LogPurgeLimit = 'props.limit.log_purge';
    case WatchSurvivalBonus = 'props.bonus.watch.survival';
    case ZoneControlBonus = 'props.bonus.zone_control.base';
    case ZoneControlCleanBonus = 'props.bonus.zone_control.clean';
    case ZoneControlHydratedBonus = 'props.bonus.zone_control.hydrated';
    case ZoneControlSoberBonus = 'props.bonus.zone_control.sober';
    case InventorySpaceBonus = 'props.bonus.rucksack_space';
    case OxygenTimeBonus = 'props.bonus.oxygen_time';
    case ChestSpaceBonus = 'props.bonus.chest_space';
    case HeroPunchKills = 'props.actions.hero_punch.kills';
    case HeroPunchEscapeTime = 'props.actions.hero_punch.escape';
    case HeroSecondWindBaseSP = 'props.actions.hero_sw.sp';
    case HeroSecondWindBonusAP = 'props.actions.hero_sw.ap';
    case HeroRescueRange = 'props.actions.hero_rescue.range';
    case HeroReturnRange = 'props.actions.hero_return.range';
    case HeroImmuneStatusList = 'props.actions.hero_immune.protection';
    case HeroImmuneHeals = 'props.actions.hero_immune.heals';
    case ProCamperUsageLimit = 'props.limit.camping.pro';
    case CampingChanceCap = 'props.limit.camping.cap';
    case TrashSearchLimit = 'props.limit.trash.count';
    case ChestHiddenStashLimit = 'props.limit.chest.hidden.count';
    //</editor-fold>

    //<editor-fold desc="Config Values">
    case Section_Config = '--section--/Config';
    case RevengeItems = 'config.revenge_items';
    //</editor-fold>

    public function abstract(): bool
    {
        return match ($this) {
            self::Section_Features,
            self::Section_Properties,
            self::Section_Config,
                => true,

            default => false
        };
    }

    public function parent(): ?CitizenProperties {
        return match ($this) {
            self::EnableBlackboard,
            self::EnableGroupMessages,
            self::EnableBuildingRecommendation,
            self::EnableProWatchman,
            self::EnableProCamper,
            self::EnableClairvoyance,
            self::EnableOmniscience,
                => self::Section_Features,

            self::TownDefense,
            self::AnonymousMessageLimit,
            self::AnonymousPostLimit,
            self::ComplaintLimit,
            self::WatchSurvivalBonus,
            self::LogManipulationLimit,
            self::LogPurgeLimit,
            self::ZoneControlBonus,
            self::ZoneControlCleanBonus,
            self::ZoneControlHydratedBonus,
            self::ZoneControlSoberBonus,
            self::InventorySpaceBonus,
            self::ChestSpaceBonus,
            self::OxygenTimeBonus,
            self::HeroPunchKills,
            self::HeroPunchEscapeTime,
            self::HeroSecondWindBaseSP,
            self::HeroSecondWindBonusAP,
            self::HeroRescueRange,
            self::HeroReturnRange,
            self::HeroImmuneStatusList,
            self::HeroImmuneHeals,
            self::ProCamperUsageLimit,
            self::CampingChanceCap,
            self::TrashSearchLimit,
            self::ChestHiddenStashLimit,
                => self::Section_Properties,

            self::RevengeItems,
                => self::Section_Config,

            default => null
        };
    }

    public function children(): array
    {
        return array_filter(self::cases(), fn(self $setting) => $setting->parent() === $this);
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
            self::EnableBlackboard               => false,
            self::EnableGroupMessages            => false,
            self::EnableBuildingRecommendation   => false,
            self::EnableProWatchman              => false,
            self::EnableProCamper                => false,
            self::EnableAdvancedTheft            => false,
            self::EnableClairvoyance             => false,
            self::EnableOmniscience              => false,
            self::HeroImmuneHeals                => false,

            self::TownDefense                    => 5,
            self::AnonymousMessageLimit          => 0,
            self::AnonymousPostLimit             => 0,
            self::ComplaintLimit                 => 4,
            self::LogManipulationLimit           => 0,
            self::LogPurgeLimit                  => 0,
            self::ZoneControlBonus               => 0,
            self::ZoneControlCleanBonus          => 0,
            self::ZoneControlHydratedBonus       => 0,
            self::ZoneControlSoberBonus          => 0,
            self::WatchSurvivalBonus             => 0.0,
            self::InventorySpaceBonus            => 0,
            self::ChestSpaceBonus                => 0,
            self::OxygenTimeBonus                => 0,
            self::HeroPunchKills                 => 2,
            self::HeroPunchEscapeTime            => 0,
            self::HeroSecondWindBaseSP           => 0,
            self::HeroSecondWindBonusAP          => 0,
            self::HeroRescueRange                => 2,
            self::HeroReturnRange                => 9,
            self::ProCamperUsageLimit            => 0,
            self::CampingChanceCap               => 0.9,
            self::TrashSearchLimit               => 3,
            self::ChestHiddenStashLimit          => 0,

            self::RevengeItems                   => [],
            self::HeroImmuneStatusList           => [],

            default => null,
        };
    }

    public function fallback(): array
    {
        return [];
    }

    /**
     * @return CitizenProperties[]
     */
    public static function validCases(): array
    {
        return array_filter(self::cases(), fn(CitizenProperties $s) => !$s->abstract());
    }

    public function merge(mixed $old, mixed $new): mixed
    {
        $old ??= $this->default();

        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match ($this) {
            self::EnableBlackboard               => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableGroupMessages            => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableBuildingRecommendation   => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableProWatchman              => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableProCamper                => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableAdvancedTheft            => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableClairvoyance             => $this->default() ? ($old && $new) : ($old || $new),
            self::EnableOmniscience              => $this->default() ? ($old && $new) : ($old || $new),
            self::HeroImmuneHeals                => $this->default() ? ($old && $new) : ($old || $new),

            self::TownDefense                    => ($old + $new) - $this->default(),
            self::AnonymousMessageLimit          => ($old + $new) - $this->default(),
            self::AnonymousPostLimit             => ($old + $new) - $this->default(),
            self::ComplaintLimit                 => ($old + $new) - $this->default(),
            self::LogManipulationLimit           => ($old + $new) - $this->default(),
            self::LogPurgeLimit                  => ($old + $new) - $this->default(),
            self::ZoneControlBonus               => ($old + $new) - $this->default(),
            self::ZoneControlCleanBonus          => ($old + $new) - $this->default(),
            self::ZoneControlHydratedBonus       => ($old + $new) - $this->default(),
            self::ZoneControlSoberBonus          => ($old + $new) - $this->default(),
            self::WatchSurvivalBonus             => ($old + $new) - $this->default(),
            self::InventorySpaceBonus            => ($old + $new) - $this->default(),
            self::ChestSpaceBonus                => ($old + $new) - $this->default(),
            self::OxygenTimeBonus                => ($old + $new) - $this->default(),
            self::HeroPunchKills                 => ($old + $new) - $this->default(),
            self::HeroPunchEscapeTime            => ($old + $new) - $this->default(),
            self::HeroSecondWindBaseSP           => ($old + $new) - $this->default(),
            self::HeroSecondWindBonusAP          => ($old + $new) - $this->default(),
            self::HeroRescueRange                => ($old + $new) - $this->default(),
            self::HeroReturnRange                => ($old + $new) - $this->default(),
            self::ProCamperUsageLimit            => ($old + $new) - $this->default(),
            self::TrashSearchLimit               => ($old + $new) - $this->default(),
            self::ChestHiddenStashLimit          => ($old + $new) - $this->default(),
            self::CampingChanceCap               => ($old + $new) - $this->default(),

            self::RevengeItems                   => [...$old,...$new],
            self::HeroImmuneStatusList           => [...$old,...$new],

            default => null,
        };
    }

    public function translationKey(): string
    {
        return "cfg_ctp_" . str_replace(".", "_", $this->value);
    }
}