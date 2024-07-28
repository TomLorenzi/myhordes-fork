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
    case WatchSurvivalBonus = 'props.bonus.watch.survival';
    case ZoneControlBonus = 'props.bonus.zone_control.base';
    case ZoneControlCleanBonus = 'props.bonus.zone_control.clean';
    case ZoneControlHydratedBonus = 'props.bonus.zone_control.hydrated';
    case ZoneControlSoberBonus = 'props.bonus.zone_control.sober';
    case InventorySpaceBonus = 'props.bonus.rucksack_space';
    //</editor-fold>

    //<editor-fold desc="Config Values">
    case Section_Config = '--section--/Config';
    case RevengeItems = 'config.revenge_items';
    //</editor-fold>

    public function abstract(): bool
    {
        return match ($this) {
            self::Section_Features,
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
            self::ZoneControlBonus,
            self::ZoneControlCleanBonus,
            self::ZoneControlHydratedBonus,
            self::ZoneControlSoberBonus,
            self::InventorySpaceBonus,
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

            self::TownDefense                    => 5,
            self::AnonymousMessageLimit          => 0,
            self::AnonymousPostLimit             => 0,
            self::ComplaintLimit                 => 4,
            self::LogManipulationLimit           => 0,
            self::ZoneControlBonus               => 0,
            self::ZoneControlCleanBonus          => 0,
            self::ZoneControlHydratedBonus       => 0,
            self::ZoneControlSoberBonus          => 0,
            self::WatchSurvivalBonus             => 0.0,
            self::InventorySpaceBonus            => 0,

            self::RevengeItems                   => [],

            default => null,
        };
    }

    public function fallback(): array
    {
        return [];
    }
}