<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\AttackScheduleRepository')]
class AttackSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column()]
    private ?\DateTimeImmutable $timestamp;

    #[ORM\Column(type: 'boolean')]
    private bool $completed = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private int $failures = 0;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }
    public function setTimestamp(\DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
    public function getCompleted(): ?bool
    {
        return $this->completed;
    }
    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getFailures(): ?int
    {
        return $this->failures;
    }

    public function setFailures(int $failures): static
    {
        $this->failures = $failures;

        return $this;
    }
}
