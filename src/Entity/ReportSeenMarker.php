<?php

namespace App\Entity;

use App\Repository\ReportSeenMarkerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: ReportSeenMarkerRepository::class)]
#[Table]
#[UniqueConstraint(name: 'report_seen_unique', columns: ['user_id', 'report_id'])]
class ReportSeenMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $user;
    #[ORM\ManyToOne(targetEntity: AdminReport::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $report;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getReport(): ?AdminReport
    {
        return $this->report;
    }
    public function setReport(?AdminReport $report): self
    {
        $this->report = $report;

        return $this;
    }
}
