<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenEscortSettingsRepository")
 */
class CitizenEscortSettings
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Citizen", mappedBy="escortSettings", cascade={"persist"})
     */
    private $citizen;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen", inversedBy="leadingEscorts", cascade={"persist"})
     */
    private $leader;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        // set (or unset) the owning side of the relation if necessary
        $newEscortSettings = null === $citizen ? null : $this;
        if ($citizen && $citizen->getEscortSettings() !== $newEscortSettings) {
            $citizen->setEscortSettings($newEscortSettings);
        }

        return $this;
    }

    public function getLeader(): ?Citizen
    {
        return $this->leader;
    }

    public function setLeader(?Citizen $leader): self
    {
        $this->leader = $leader;

        return $this;
    }
}
