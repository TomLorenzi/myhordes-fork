<?php

namespace App\Traits\Actions\ActionResults;
use App\Entity\Town as TownType;

trait TownResult
{
    use Town;
    public function withTown(TownType $town): self { $this->town = $town; return $this; }
}