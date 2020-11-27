<?php


namespace App\Structures;

use App\Entity\Town;
use Symfony\Component\HttpFoundation\Response;

class EventConf extends Conf
{
    const EVENT_CSS = 'css';

    const EVENT_ITEMS = 'items';

    const EVENT_HOOK_WATCHTOWER   = 'hooks.watchtower';
    const EVENT_HOOK_DOOR         = 'hooks.door';
    const EVENT_HOOK_NIGHTLY_PRE  = 'hooks.night_before';
    const EVENT_HOOK_NIGHTLY_POST = 'hooks.night_after';

    public function active(): bool {
        return !empty($this->raw());
    }

    public static function Void(): ?object { return null; }

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
}