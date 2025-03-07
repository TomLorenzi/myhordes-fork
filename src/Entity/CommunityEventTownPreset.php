<?php

namespace App\Entity;

use App\Repository\CommunityEventTownPresetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CommunityEventTownPresetRepository::class)]
class CommunityEventTownPreset
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column]
    private array $header = [];

    #[ORM\Column]
    private array $rules = [];

    #[ORM\ManyToOne(inversedBy: 'townPresets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CommunityEvent $event = null;

    #[ORM\OneToOne(inversedBy: 'communityEventTownPreset', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Town $town = null;

    #[ORM\Column(name: 'base_id', nullable: true)]
    private ?int $townId = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function setHeader(array $header): self
    {
        $this->header = $header;

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setRules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function getEvent(): ?CommunityEvent
    {
        return $this->event;
    }

    public function setEvent(?CommunityEvent $event): self
    {
        $this->event = $event;

        return $this;
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

    public function getTownId(): ?int
    {
        return $this->townId;
    }

    public function setTownId(?int $townId): self
    {
        $this->townId = $townId;

        return $this;
    }
}
