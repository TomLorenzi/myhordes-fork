<?php

namespace App\Entity;

use App\Repository\MarketingCampaignConversionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarketingCampaignConversionRepository::class)]
class MarketingCampaignConversion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time = null;

    #[ORM\ManyToOne(inversedBy: 'conversions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MarketingCampaign $campaign = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getCampaign(): ?MarketingCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(?MarketingCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }
}
