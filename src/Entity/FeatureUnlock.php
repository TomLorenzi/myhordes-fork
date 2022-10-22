<?php

namespace App\Entity;

use App\Repository\FeatureUnlockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureUnlockRepository::class)]
class FeatureUnlock
{
    const FeatureExpirationNone = 0;
    const FeatureExpirationSeason = 1;
    const FeatureExpirationTimestamp = 2;
    const FeatureExpirationTownCount = 3;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $user;
    #[ORM\ManyToOne(targetEntity: FeatureUnlockPrototype::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $prototype;
    #[ORM\Column(type: 'integer')]
    private $expirationMode;
    #[ORM\ManyToOne(targetEntity: Season::class)]
    private $season;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $timestamp;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $townCount;
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
    public function getPrototype(): ?FeatureUnlockPrototype
    {
        return $this->prototype;
    }
    public function setPrototype(?FeatureUnlockPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }
    public function getExpirationMode(): ?int
    {
        return $this->expirationMode;
    }
    public function setExpirationMode(int $expirationMode): self
    {
        $this->expirationMode = $expirationMode;

        return $this;
    }
    public function getSeason(): ?Season
    {
        return $this->season;
    }
    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }
    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }
    public function setTimestamp(?\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
    public function getTownCount(): ?int
    {
        return $this->townCount;
    }
    public function setTownCount(?int $townCount): self
    {
        $this->townCount = $townCount;

        return $this;
    }
}
