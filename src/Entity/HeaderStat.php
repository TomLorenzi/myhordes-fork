<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;

#[ORM\Entity(repositoryClass: 'App\Repository\HeaderStatRepository')]
#[Table]
class HeaderStat
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $killedCitizens;

    #[ORM\Column(type: 'integer')]
    private $killedZombies;

    #[ORM\Column(type: 'integer')]
    private $cannibalismActs;

    /**
     * @return mixed
     */
    public function getKilledCitizens()
    {
        return $this->killedCitizens;
    }

    /**
     * @param mixed $killedCitizens
     */
    public function setKilledCitizens($killedCitizens): self
    {
        $this->killedCitizens = $killedCitizens;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getKilledZombies()
    {
        return $this->killedZombies;
    }

    /**
     * @param mixed $killedZombies
     */
    public function setKilledZombies($killedZombies): self
    {
        $this->killedZombies = $killedZombies;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCannibalismActs()
    {
        return $this->cannibalismActs;
    }

    /**
     * @param mixed $cannibalismActs
     */
    public function setCannibalismActs($cannibalismActs): self
    {
        $this->cannibalismActs = $cannibalismActs;
        return $this;

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $cannibalismActs
     */
    public function setId($id): self
    {
        $this->id = $id;
        return $this;

    }

    public function __construct() {}

}
