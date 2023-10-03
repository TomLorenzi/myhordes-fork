<?php

namespace App\Event\Game\Citizen;


use App\Entity\Citizen;
use App\Entity\RuinZone;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Enum\ScavengingActionType;

/**
 * @method CitizenWorkshopOptionsData setup(Citizen $citizen)
 */
class CitizenWorkshopOptionsData extends CitizenBaseData
{
    public array $visible_types = [];
    public array $disabled_types = [];

    public array $ap_penalty_types = [];

    public array $section_types = [];

    public array $section_note_types = [];

    public function pushOption( int $option, bool $disabled = false, int $penalty = 0, ?string $section = null, ?string $section_note = null ): void {
        $this->visible_types[] = $option;
        if ($disabled) $this->disabled_types[] = $option;
        else $this->disabled_types = array_filter( $this->disabled_types, fn($v) => $v !== $option );

        $this->ap_penalty_types[$option] = $penalty;
        $this->section_types[$option] = $section;
        $this->section_note_types[$option] = $section_note;

        $this->visible_types = array_values( array_unique( $this->visible_types ) );
        $this->disabled_types = array_values( array_unique( $this->disabled_types ) );
    }
}