<?php

namespace App\Traits\Actions\ActionResults;
use App\Entity\Town as TownType;

trait Town
{
    private ?TownType $town = null;
    public function town(): ?TownType { return $this->town; }
}