<?php

namespace App\Interfaces\Entity;

use App\Entity\PictoPrototype;
use App\Entity\User;

interface PictoRollupInterface
{

    public function getCount(): ?int;
    public function getUser(): ?User;

    public function getPrototype(): ?PictoPrototype;
}
