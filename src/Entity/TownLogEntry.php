<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TownLogEntryRepository")
 */
class TownLogEntry
{
    const TypeVarious      =  0;
    const TypeCrimes       =  1;
    const TypeBank         =  2;
    const TypeDump         =  3;
    const TypeConstruction =  4;
    const TypeWorkshop     =  5;
    const TypeDoor         =  6;
    const TypeWell         =  7;
    const TypeCitizens     =  8;
    const TypeNightly      =  9;
    const TypeHome         = 10;
    const TypeChat         = 11;

    const ClassNone     = 0;
    const ClassWarning  = 1;
    const ClassCritical = 2;
    const ClassInfo     = 3;
    const ClassChat     = 4;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="integer")
     */
    private $day;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen")
     */
    private $citizen;

    /**
     * @ORM\Column(type="integer")
     */
    private $type = self::TypeVarious;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hidden = false;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="integer")
     */
    private $class = self::ClassNone;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Zone")
     */
    private $zone;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $secondaryType;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen")
     */
    private $secondaryCitizen;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogEntryTemplate")
     * @ORM\JoinColumn(nullable=false)
     */
    private $logEntryTemplate;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $variables = [];

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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getClass(): ?int
    {
        return $this->class;
    }

    public function setClass(int $class): self
    {
        $this->class = $class;

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

    public function getSecondaryType(): ?int
    {
        return $this->secondaryType;
    }

    public function setSecondaryType(?int $secondaryType): self
    {
        $this->secondaryType = $secondaryType;

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
}
