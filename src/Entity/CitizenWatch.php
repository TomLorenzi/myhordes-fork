<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenWatchRepository")
 */
class CitizenWatch
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town", inversedBy="citizenWatches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Citizen", inversedBy="citizenWatch")
     * @ORM\JoinColumn(nullable=true)
     */
    private $citizen;

    /**
     * @ORM\Column(type="integer")
     */
    private $day;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }
}
