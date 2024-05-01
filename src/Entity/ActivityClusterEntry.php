<?php

namespace App\Entity;

use App\Repository\ActivityClusterEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[UniqueConstraint(name: 'activity_cluster_entry_id_unique', columns: ['user_id','cluster_id','cutoff'])]
#[ORM\Entity(repositoryClass: ActivityClusterEntryRepository::class)]
class ActivityClusterEntry
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ActivityCluster $cluster = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $ownBlocks = null;

    #[ORM\Column]
    private ?int $foreignBlocks = null;

    #[ORM\Column]
    private ?int $totalOverlap = null;

    #[ORM\Column]
    private ?float $averageOverlap = null;

    #[ORM\Column]
    private ?int $overlappingUsers = null;

    #[ORM\Column]
    private ?float $overlappingUA = null;

    #[ORM\Column]
    private ?int $cutoff = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $firstSeen = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastSeen = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCluster(): ?ActivityCluster
    {
        return $this->cluster;
    }

    public function setCluster(?ActivityCluster $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
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

    public function getOwnBlocks(): ?int
    {
        return $this->ownBlocks;
    }

    public function setOwnBlocks(int $ownBlocks): static
    {
        $this->ownBlocks = $ownBlocks;

        return $this;
    }

    public function getForeignBlocks(): ?int
    {
        return $this->foreignBlocks;
    }

    public function setForeignBlocks(int $foreignBlocks): static
    {
        $this->foreignBlocks = $foreignBlocks;

        return $this;
    }

    public function getTotalOverlap(): ?int
    {
        return $this->totalOverlap;
    }

    public function setTotalOverlap(int $totalOverlap): static
    {
        $this->totalOverlap = $totalOverlap;

        return $this;
    }

    public function getAverageOverlap(): ?float
    {
        return $this->averageOverlap;
    }

    public function setAverageOverlap(float $averageOverlap): static
    {
        $this->averageOverlap = $averageOverlap;

        return $this;
    }

    public function getOverlappingUsers(): ?int
    {
        return $this->overlappingUsers;
    }

    public function setOverlappingUsers(int $overlappingUsers): static
    {
        $this->overlappingUsers = $overlappingUsers;

        return $this;
    }

    public function getOverlappingUA(): ?float
    {
        return $this->overlappingUA;
    }

    public function setOverlappingUA(float $overlappingUA): static
    {
        $this->overlappingUA = $overlappingUA;

        return $this;
    }

    public function getCutoff(): ?int
    {
        return $this->cutoff;
    }

    public function setCutoff(int $cutoff): static
    {
        $this->cutoff = $cutoff;

        return $this;
    }

    public function getFirstSeen(): ?\DateTimeInterface
    {
        return $this->firstSeen;
    }

    public function setFirstSeen(\DateTimeInterface $firstSeen): static
    {
        $this->firstSeen = $firstSeen;

        return $this;
    }

    public function getLastSeen(): ?\DateTimeInterface
    {
        return $this->lastSeen;
    }

    public function setLastSeen(\DateTimeInterface $lastSeen): static
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }
}
