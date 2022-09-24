<?php

namespace App\Entity;

use App\Repository\CouncilEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: CouncilEntryRepository::class)]
#[Table]
#[UniqueConstraint(name: 'council_entry_ord_unique', columns: ['town_id', 'ord', 'day'])]
class CouncilEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $ord;
    #[ORM\ManyToOne(targetEntity: CouncilEntryTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $template;
    #[ORM\ManyToOne(targetEntity: Town::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $town;
    #[ORM\Column(type: 'integer')]
    private $day;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $citizen;
    #[ORM\Column(type: 'array', nullable: true)]
    private $variables = [];
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getOrd(): ?int
    {
        return $this->ord;
    }
    public function setOrd(int $ord): self
    {
        $this->ord = $ord;

        return $this;
    }
    public function getTemplate(): ?CouncilEntryTemplate
    {
        return $this->template;
    }
    public function setTemplate(?CouncilEntryTemplate $template): self
    {
        $this->template = $template;

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
    public function getDay(): ?int
    {
        return $this->day;
    }
    public function setDay(int $day): self
    {
        $this->day = $day;

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
