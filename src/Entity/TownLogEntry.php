<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\TownLogEntryRepository')]
#[ORM\Table]
#[ORM\Index(name: 'logs_by_town_day_zone_idx', columns: ['town_id', 'day', 'zone_id'])]
#[ORM\Index(name: 'logs_by_type_idx', columns: ['log_entry_template_id'])]
class TownLogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $timestamp;
    #[ORM\Column(type: 'integer')]
    private ?int $day;
    #[ORM\ManyToOne(targetEntity: Town::class, inversedBy: '_townLogEntries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Town $town;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    private ?Citizen $citizen;
    #[ORM\Column(type: 'boolean')]
    private bool $hidden = false;
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    private ?Zone $zone;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    private ?Citizen $secondaryCitizen;
    #[ORM\ManyToOne(targetEntity: LogEntryTemplate::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?LogEntryTemplate $logEntryTemplate;
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $variables = null;
    #[ORM\Column(type: 'boolean')]
    private bool $adminOnly = false;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    private ?Citizen $hiddenBy;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }
    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

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
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }
    public function getZone(): ?Zone
    {
        return $this->zone;
    }
    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }
    public function getSecondaryCitizen(): ?Citizen
    {
        return $this->secondaryCitizen;
    }
    public function setSecondaryCitizen(?Citizen $secondaryCitizen): self
    {
        $this->secondaryCitizen = $secondaryCitizen;

        return $this;
    }
    public function getLogEntryTemplate(): ?LogEntryTemplate
    {
        return $this->logEntryTemplate;
    }
    public function setLogEntryTemplate(?LogEntryTemplate $logEntryTemplate): self
    {
        $this->logEntryTemplate = $logEntryTemplate;

        return $this;
    }
    public function getVariables(): ?array
    {
        return $this->variables;
    }
    public function setVariables(?array $variables): self
    {
        $this->variables = $variables;

        return $this;
    }
    public function getAdminOnly(): ?bool
    {
        return $this->adminOnly;
    }
    public function setAdminOnly(bool $adminOnly): self
    {
        $this->adminOnly = $adminOnly;

        return $this;
    }
    public function getHiddenBy(): ?Citizen
    {
        return $this->hiddenBy;
    }
    public function setHiddenBy(?Citizen $hiddenBy): self
    {
        $this->hiddenBy = $hiddenBy;

        return $this;
    }
}
