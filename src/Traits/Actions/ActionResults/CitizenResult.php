<?php

namespace App\Traits\Actions\ActionResults;
use App\Entity\Citizen as CitizenType;

trait CitizenResult
{
    use Citizen;
    public function withCitizen(CitizenType $citizen): self { $this->citizen = $citizen; return $this; }
}