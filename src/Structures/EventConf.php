<?php


namespace App\Structures;

use App\Entity\Citizen;
use App\Entity\Town;
use Symfony\Component\HttpFoundation\Response;

class EventConf extends Conf
{
    const EVENT_CSS = 'css';

    const EVENT_GROUP_DIG = 'group_dig';

    const EVENT_HOOK_WATCHTOWER   = 'hooks.watchtower';
    const EVENT_HOOK_DOOR         = 'hooks.door';
    const EVENT_HOOK_NIGHTLY_PRE  = 'hooks.night_before';
    const EVENT_HOOK_NIGHTLY_POST = 'hooks.night_after';

    const EVENT_HOOK_ENABLE_TOWN     = 'hooks.enable_town';
    const EVENT_HOOK_DISABLE_TOWN    = 'hooks.disable_town';
    const EVENT_HOOK_ENABLE_CITIZEN  = 'hooks.enable_citizen';
    const EVENT_HOOK_DISABLE_CITIZEN = 'hooks.disable_citizen';

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

    public static function Void(): ?object { return null; }
    public static function True(): bool { return true; }

    public function hook_nightly_pre(Town $town): void {
        call_user_func( $this->get(self::EVENT_HOOK_NIGHTLY_PRE, 'App\Structures\EventConf::Void') , $town);
    }

    public function hook_nightly_post(Town $town): void {
        call_user_func( $this->get(self::EVENT_HOOK_NIGHTLY_POST, 'App\Structures\EventConf::Void') , $town);
    }

    public function hook_door(string $action): ?Response {
        return call_user_func( $this->get(self::EVENT_HOOK_DOOR, 'App\Structures\EventConf::Void') , $action);
    }

    public function hook_watchtower_estimations(int &$min, int &$max): void {
        call_user_func( $this->get(self::EVENT_HOOK_WATCHTOWER, 'App\Structures\EventConf::Void') , array(&$min, &$max));
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
}