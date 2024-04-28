<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[UniqueConstraint(name: 'activity_id_unique', columns: ['ip', 'domain', 'agent', 'user_id', 'block_begin'])]
#[ORM\Index(columns: ['block_begin'], name: 'activity_btb_idx')]
#[ORM\Index(columns: ['block_end'], name: 'activity_bte_idx')]
#[ORM\Index(columns: ['ip'], name: 'activity_ip_idx')]
#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 39)]
    private ?string $ip = null;

    #[ORM\Column(length: 255)]
    private ?string $domain = null;

    #[ORM\Column(length: 255)]
    private ?string $agent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTimeBegin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTimeEnd = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $blockBegin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $blockEnd = null;

    #[ORM\Column]
    private ?int $requests = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getAgent(): ?string
    {
        return $this->agent;
    }

    public function setAgent(string $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getDateTimeBegin(): ?\DateTimeInterface
    {
        return $this->dateTimeBegin;
    }

    public function setDateTimeBegin(\DateTimeInterface $dateTimeBegin): static
    {
        $this->dateTimeBegin = $dateTimeBegin;

        return $this;
    }

    public function getDateTimeEnd(): ?\DateTimeInterface
    {
        return $this->dateTimeEnd;
    }

    public function setDateTimeEnd(\DateTimeInterface $dateTimeEnd): static
    {
        $this->dateTimeEnd = $dateTimeEnd;

        return $this;
    }

    public function getBlockBegin(): ?\DateTimeInterface
    {
        return $this->blockBegin;
    }

    public function setBlockBegin(\DateTimeInterface $blockBegin): static
    {
        $this->blockBegin = $blockBegin;

        return $this;
    }

    public function getBlockEnd(): ?\DateTimeInterface
    {
        return $this->blockEnd;
    }

    public function setBlockEnd(\DateTimeInterface $blockEnd): static
    {
        $this->blockEnd = $blockEnd;

        return $this;
    }

    public function getRequests(): ?int
    {
        return $this->requests;
    }

    public function setRequests(int $requests): static
    {
        $this->requests = $requests;

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
}
