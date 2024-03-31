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
    const EVENT_MOD_CSS = 'mod-css';
    const EVENT_MUTATE_NAME = 'mutate_names';
    const EVENT_MODS_ENABLED = 'mods.enable';

    const EVENT_DIG_DESERT_GROUP  = 'event_dig.desert.group';
    const EVENT_DIG_DESERT_CHANCE = 'event_dig.desert.chance';
    const EVENT_DIG_DESERT_CHANCE_CAP = 'event_dig.desert.chance_cap';
    const EVENT_DIG_RUINS         = 'event_dig.ruins';

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

    public function dropMods(): array {
        $base = [];
        foreach ($this->get( self::EVENT_MODS_ENABLED, [] ) as $modId) $base[] = DropMod::tryFrom( $modId );
        return array_filter( $base, fn(?DropMod $d) => $d !== null );
    }
}