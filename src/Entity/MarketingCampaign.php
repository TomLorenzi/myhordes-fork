<?php

namespace App\Entity;

use App\Repository\MarketingCampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MarketingCampaignRepository::class)]
#[UniqueConstraint(name: 'marketing_campaign_unique_slug', columns: ['slug'])]
class MarketingCampaign
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    private ?string $slug = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $managers;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: MarketingCampaignConversion::class, orphanRemoval: true)]
    private Collection $conversions;

    #[ORM\Column]
    private int $clicks = 0;

    public function __construct()
    {
        $this->managers = new ArrayCollection();
        $this->conversions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(User $manager): self
    {
        if (!$this->managers->contains($manager)) {
            $this->managers->add($manager);
        }

        return $this;
    }

    public function removeManager(User $manager): self
    {
        $this->managers->removeElement($manager);

        return $this;
    }

    /**
     * @return Collection<int, MarketingCampaignConversion>
     */
    public function getConversions(): Collection
    {
        return $this->conversions;
    }

    public function addConversion(MarketingCampaignConversion $conversion): self
    {
        if (!$this->conversions->contains($conversion)) {
            $this->conversions->add($conversion);
            $conversion->setCampaign($this);
        }

        return $this;
    }

    public function removeConversion(MarketingCampaignConversion $conversion): self
    {
        if ($this->conversions->removeElement($conversion)) {
            // set the owning side to null (unless already changed)
            if ($conversion->getCampaign() === $this) {
                $conversion->setCampaign(null);
            }
        }

        return $this;
    }

    public function registerClick(): self {
        $this->setClicks( $this->getClicks() + 1 );
        return $this;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    public function setClicks(int $clicks): static
    {
        $this->clicks = $clicks;

        return $this;
    }
}
