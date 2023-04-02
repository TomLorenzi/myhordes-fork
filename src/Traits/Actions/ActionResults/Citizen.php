<?php

namespace App\Traits\Actions\ActionResults;
use App\Entity\Citizen as CitizenType;

trait Citizen
{
    private ?CitizenType $citizen = null;
    public function citizen(): ?CitizenType { return $this->citizen; }
}