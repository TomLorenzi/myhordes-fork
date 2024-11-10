<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\CitizenEscortSettingsRepository')]
class CitizenEscortSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Citizen', mappedBy: 'escortSettings', cascade: ['persist'])]
    private $citizen;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen', inversedBy: 'leadingEscorts', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $leader;
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $allowInventoryAccess = false;
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $forceDirectReturn = false;
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
    public function getAllowInventoryAccess(): ?bool
    {
        return $this->allowInventoryAccess;
    }
    public function setAllowInventoryAccess(?bool $allowInventoryAccess): self
    {
        $this->allowInventoryAccess = $allowInventoryAccess;

        return $this;
    }
    public function getForceDirectReturn(): ?bool
    {
        return $this->forceDirectReturn;
    }
    public function setForceDirectReturn(?bool $forceDirectReturn): self
    {
        $this->forceDirectReturn = $forceDirectReturn;

        return $this;
    }
}
