<?php

namespace App\Entity;

use App\Repository\ActionEventLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActionEventLogRepository::class)]
class ActionEventLog
{
    const ActionEventTypeBankTaken = 1;
    const ActionEventTypeBankLock = 2;
    const ActionEventComplaintIssued   = 3;
    const ActionEventComplaintRedacted = 4;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $type;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $citizen;
    #[ORM\Column(type: 'datetime')]
    private $timestamp;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $opt1;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $opt2;
    public function getId(): ?int
    {
        return $this->id;
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
    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }
    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
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
    public function getOpt1(): ?int
    {
        return $this->opt1;
    }
    public function setOpt1(?int $opt1): self
    {
        $this->opt1 = $opt1;

        return $this;
    }
    public function getOpt2(): ?int
    {
        return $this->opt2;
    }
    public function setOpt2(?int $opt2): self
    {
        $this->opt2 = $opt2;

        return $this;
    }
}
