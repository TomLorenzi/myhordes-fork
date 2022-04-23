<?php

namespace App\Interfaces;

use Doctrine\Common\Collections\Collection;

interface RandomGroup {

    /**
     * @return Collection<RandomEntry>
     */
    public function getEntries(): Collection;

}