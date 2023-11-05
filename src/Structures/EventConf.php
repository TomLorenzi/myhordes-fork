<?php


namespace App\Structures;

use App\Entity\Citizen;
use App\Entity\Town;
use App\Enum\DropMod;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EventConf extends Conf
{
    const EVENT_NAME = 'name';
    const EVENT_PRIORITY = 'priority';

    const EVENT_CSS = 'css';
    const EVENT_MUTATE_NAME = 'mutate_names';
    const EVENT_MODS_ENABLED = 'mods.enable';

    const EVENT_DIG_DESERT_GROUP  = 'event_dig.desert.group';
    const EVENT_DIG_DESERT_CHANCE = 'event_dig.desert.chance';
    const EVENT_DIG_DESERT_CHANCE_CAP = 'event_dig.desert.chance_cap';
    const EVENT_DIG_RUINS         = 'event_dig.ruins';

    const EVENT_HOOK_WATCHTOWER   = 'hooks.watchtower';
    const EVENT_HOOK_DASHBOARD    = 'hooks.dashboard';
    const EVENT_HOOK_DOOR         = 'hooks.door';
    const EVENT_HOOK_NIGHTLY_PRE  = 'hooks.night_before';
    const EVENT_HOOK_NIGHTLY_POST = 'hooks.night_after';
    const EVENT_HOOK_NIGHTLY_NONE = 'hooks.night_none';

    const EVENT_HOOK_ENABLE_TOWN     = 'hooks.enable_town';
    const EVENT_HOOK_DISABLE_TOWN    = 'hooks.disable_town';
    const EVENT_HOOK_ENABLE_CITIZEN  = 'hooks.enable_citizen';
    const EVENT_HOOK_DISABLE_CITIZEN = 'hooks.disable_citizen';

    const EVENT_DISPATCH_WATCHTOWER   = 'dispatch.watchtower';
    const EVENT_DISPATCH_DASHBOARD    = 'dispatch.dashboard';
    const EVENT_DISPATCH_DOOR         = 'dispatch.door';
    const EVENT_DISPATCH_NIGHTLY_PRE  = 'dispatch.night_before';
    const EVENT_DISPATCH_NIGHTLY_POST = 'dispatch.night_after';
    const EVENT_DISPATCH_NIGHTLY_NONE = 'dispatch.night_none';

    const EVENT_DISPATCH_ENABLE_TOWN     = 'dispatch.enable_town';
    const EVENT_DISPATCH_DISABLE_TOWN    = 'dispatch.disable_town';
    const EVENT_DISPATCH_ENABLE_CITIZEN  = 'dispatch.enable_citizen';
    const EVENT_DISPATCH_DISABLE_CITIZEN = 'dispatch.disable_citizen';



    private ?string $eventName;

    public function __construct(?string $name = null, array $data = [])
    {
        $this->eventName = $name;
        parent::__construct($data);
    }

    public function active(): bool {
        return !empty($this->raw()) && $this->eventName !== null;
    }

    public function name(): ?string {
        return $this->eventName;
    }

    public function priority(): int {
        return (int)$this->get(self::EVENT_PRIORITY, 0);
    }

    public static function Void(): ?object { return null; }
    public static function True(): bool { return true; }

    public function hook_door(string $action): Response|JsonResponse|null {
        return call_user_func( $this->get(self::EVENT_HOOK_DOOR, 'App\Structures\EventConf::Void') , $action);
    }

    public function hook_watchtower_estimations(int &$min, int &$max, Town $town, int $dayOffset, float $quality, ?string &$message = null ): void {
        call_user_func( $this->get(self::EVENT_HOOK_WATCHTOWER, 'App\Structures\EventConf::Void') , array(&$min, &$max, $town, $dayOffset, $quality, &$message));
    }

    public function hook_dashboard(Town $town, ?array &$additional_bullets = null, ?array &$additional_situation = null ): void {
        if ($additional_bullets === null) $additional_bullets = [];
        if ($additional_situation === null) $additional_situation = [];
        call_user_func( $this->get(self::EVENT_HOOK_DASHBOARD, 'App\Structures\EventConf::Void') , array($town, &$additional_bullets, &$additional_situation));
    }


    public function hook_enable_town(Town $town): bool {
        return call_user_func( $this->get(self::EVENT_HOOK_ENABLE_TOWN, 'App\Structures\EventConf::True'), $town);
    }

    public function hook_disable_town(Town $town): bool {
        return call_user_func( $this->get(self::EVENT_HOOK_DISABLE_TOWN, 'App\Structures\EventConf::True'), $town);
    }

    public function hook_enable_citizen(Citizen $citizen): bool {
        return call_user_func( $this->get(self::EVENT_HOOK_ENABLE_CITIZEN, 'App\Structures\EventConf::True'), $citizen);
    }

    public function hook_disable_citizen(Citizen $citizen): bool {
        return call_user_func( $this->get(self::EVENT_HOOK_DISABLE_CITIZEN, 'App\Structures\EventConf::True'), $citizen);
    }

    public function dropMods(): array {
        $base = [];
        foreach ($this->get( self::EVENT_MODS_ENABLED, [] ) as $modId) $base[] = DropMod::tryFrom( $modId );
        return array_filter( $base, fn(?DropMod $d) => $d !== null );
    }
}