<?php

namespace App\Entity;

use App\Repository\HomeIntrusionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: HomeIntrusionRepository::class)]
#[Table]
#[UniqueConstraint(name: 'home_intrusion_unique', columns: ['intruder_id', 'victim_id'])]
class HomeIntrusion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $intruder;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $victim;
    #[ORM\Column(type: 'boolean')]
    private $steal;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getIntruder(): ?Citizen
    {
        return $this->intruder;
    }
    public function setIntruder(?Citizen $intruder): self
    {
        $this->intruder = $intruder;

        return $this;
    }
    public function getVictim(): ?Citizen
    {
        return $this->victim;
    }
    public function setVictim(?Citizen $victim): self
    {
        $this->victim = $victim;

        return $this;
    }
    public function getSteal(): ?bool
    {
        return $this->steal;
    }
    public function setSteal(bool $steal): self
    {
        $this->steal = $steal;

        return $this;
    }
}
