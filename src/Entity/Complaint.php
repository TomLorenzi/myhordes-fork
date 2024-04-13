<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: 'App\Repository\ComplaintRepository')]
#[Table]
#[UniqueConstraint(name: 'assoc_unique', columns: ['autor_id', 'culprit_id'])]
class Complaint
{
    const SeverityNone = 0;
    const SeverityBanish = 1;
    const SeverityKill = 2;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $autor;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen', inversedBy: 'complaints')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $culprit;
    #[ORM\Column(type: 'integer')]
    private $count;
    #[ORM\Column(type: 'integer')]
    private $severity;
    #[ORM\Column(type: 'text', nullable: true)]
    private $reason;
    #[ORM\ManyToOne(targetEntity: ComplaintReason::class)]
    private $linked_reason;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getAutor(): ?Citizen
    {
        return $this->autor;
    }
    public function setAutor(?Citizen $autor): self
    {
        $this->autor = $autor;

        return $this;
    }
    public function getCulprit(): ?Citizen
    {
        return $this->culprit;
    }
    public function setCulprit(?Citizen $culprit): self
    {
        $this->culprit = $culprit;

        return $this;
    }
    public function getCount(): ?int
    {
        return $this->count;
    }
    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }
    public function getSeverity(): ?int
    {
        return $this->severity;
    }
    public function setSeverity(int $severity): self
    {
        $this->severity = $severity;

        return $this;
    }
    public function getReason(): ?string
    {
        return $this->reason;
    }
    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
    public function getLinkedReason(): ?ComplaintReason
    {
        return $this->linked_reason;
    }
    public function setLinkedReason(?ComplaintReason $linked_reason): self
    {
        $this->linked_reason = $linked_reason;

        return $this;
    }
}
