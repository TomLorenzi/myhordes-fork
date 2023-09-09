<?php

namespace App\Event\Game\Citizen;


use App\Entity\Citizen;
use App\Entity\RuinZone;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Enum\ScavengingActionType;

class CitizenDigData extends CitizenBaseData
{

	public readonly ?Zone $zone;
	public readonly ?RuinZone $ruinZone;
	public readonly ?ZonePrototype $prototype;
    public readonly ScavengingActionType $type;
    public readonly int $distance;
    public readonly bool $empty;
    public readonly bool $at_night;

    public float $chance = 0;

    /**
     * @param Citizen $citizen
     * @param ScavengingActionType $type
     * @param Zone|ZonePrototype|RuinZone|null $zone
     * @param int|null $distance
     * @param bool|null $empty
     * @param bool $at_night
     * @return CitizenDigData
     * @noinspection PhpDocSignatureInspection
     */
	public function setup( Citizen $citizen, ScavengingActionType $type = ScavengingActionType::Dig, Zone|ZonePrototype|RuinZone|null $zone = null, ?int $distance = null, ?bool $empty = null, bool $at_night = false): void {
		parent::setup($citizen);
        $this->type = $type;
        $this->zone = is_a( $zone, Zone::class) ? $zone : (is_a( $zone, RuinZone::class ) ? $zone->getZone() : null);
        $this->ruinZone = is_a( $zone, RuinZone::class ) ? $zone : null;
        $this->prototype = is_a( $zone, ZonePrototype::class) ? $zone : ( $this->zone?->getPrototype() ?? null );
        $this->distance = $distance ?? $this->zone?->getDistance() ?? 0;
        $this->empty = $empty ?? (($this->zone?->getDigs() ?? 1) <= 0);
        $this->at_night = $at_night;
	}

}