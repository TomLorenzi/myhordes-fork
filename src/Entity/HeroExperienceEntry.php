<?php

namespace App\Entity;

use App\Enum\HeroXPType;
use App\Repository\HeroExperienceEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroExperienceEntryRepository::class)]
#[ORM\Index(columns: ['type'], name: 'hxp_type')]
#[ORM\Index(columns: ['subject'], name: 'hxp_subject')]
class HeroExperienceEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer', nullable: false, enumType: HeroXPType::class)]
    private HeroXPType $type = HeroXPType::Legacy;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?TownRankingProxy $town = null;

    #[ORM\Column]
    private ?int $value = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?CitizenRankingProxy $citizen = null;

    #[ORM\Column(length: 196, nullable: true)]
    private ?string $subject = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?LogEntryTemplate $logEntryTemplate = null;

    #[ORM\Column(nullable: true)]
    private ?array $variables = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\ManyToOne]
    private ?Season $season = null;

    #[ORM\Column]
    private bool $disabled = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): HeroXPType
    {
        return $this->type;
    }

    public function setType(HeroXPType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTown(): ?TownRankingProxy
    {
        return $this->town;
    }

    public function setTown(?TownRankingProxy $town): static
    {
        $this->town = $town;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getCitizen(): ?CitizenRankingProxy
    {
        return $this->citizen;
    }

    public function setCitizen(?CitizenRankingProxy $citizen): static
    {
        $this->citizen = $citizen;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getLogEntryTemplate(): ?LogEntryTemplate
    {
        return $this->logEntryTemplate;
    }

    public function setLogEntryTemplate(?LogEntryTemplate $logEntryTemplate): static
    {
        $this->logEntryTemplate = $logEntryTemplate;

        return $this;
    }

    public function getVariables(): ?array
    {
        return $this->variables;
    }

    public function setVariables(?array $variables): static
    {
        $this->variables = $variables;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }
}
