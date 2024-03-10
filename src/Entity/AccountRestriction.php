<?php

namespace App\Entity;

use App\Repository\AccountRestrictionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRestrictionRepository::class)]
class AccountRestriction
{
    // Social restrictions
    const RestrictionNone = 0;
    const RestrictionForum               = 1 << 1;
    const RestrictionGlobalCommunication = 1 << 3;
    const RestrictionComments            = 1 << 4;
    const RestrictionOrganization        = 1 << 5;
    const RestrictionBlackboard          = 1 << 6;
    const RestrictionTownCommunication   = 1 << 2 | AccountRestriction::RestrictionBlackboard;
    const RestrictionSocial              = AccountRestriction::RestrictionForum |
                                           AccountRestriction::RestrictionTownCommunication |
                                           AccountRestriction::RestrictionGlobalCommunication |
                                           AccountRestriction::RestrictionComments |
                                           AccountRestriction::RestrictionOrganization;
    // Gameplay restrictions
    const RestrictionGameplay            = 1 << 10;
    const RestrictionGameplayLang        = 1 << 11;

    // Profile restrictions
    const RestrictionProfileAvatar       = 1 << 20;
    const RestrictionProfileDescription  = 1 << 21;
    const RestrictionProfileTitle        = 1 << 22;
    const RestrictionProfileDisplayName  = 1 << 23;
    const RestrictionProfile             = AccountRestriction::RestrictionProfileAvatar |
                                           AccountRestriction::RestrictionProfileDescription |
                                           AccountRestriction::RestrictionProfileTitle |
                                           AccountRestriction::RestrictionProfileDisplayName;

    // Misc
    const RestrictionReportToGitlab      = 1 << 27;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $moderator;
    #[ORM\Column(type: 'integer')]
    private $restriction;
    #[ORM\Column(type: 'datetime')]
    private $created;
    #[ORM\Column(type: 'integer')]
    private $originalDuration;
    #[ORM\Column(type: 'boolean')]
    private $active;
    #[ORM\Column(type: 'boolean')]
    private $confirmed;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $expires;
    #[ORM\ManyToMany(targetEntity: User::class)]
    private $confirmedBy;
    #[ORM\Column(type: 'text')]
    private $publicReason;
    #[ORM\Column(type: 'text', nullable: true)]
    private $internalReason;
    public function __construct()
    {
        $this->confirmedBy = new ArrayCollection();
    }
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
    public function getModerator(): ?User
    {
        return $this->moderator;
    }
    public function setModerator(?User $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }
    public function getRestriction(): ?int
    {
        return $this->restriction;
    }
    public function setRestriction(int $restriction): self
    {
        $this->restriction = $restriction;

        return $this;
    }
    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }
    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }
    public function getOriginalDuration(): ?int
    {
        return $this->originalDuration;
    }
    public function setOriginalDuration(int $originalDuration): self
    {
        $this->originalDuration = $originalDuration;

        return $this;
    }
    public function getActive(): ?bool
    {
        return $this->active;
    }
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
    public function getConfirmed(): ?bool
    {
        return $this->confirmed;
    }
    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;

        return $this;
    }
    public function getExpires(): ?\DateTimeInterface
    {
        return $this->expires;
    }
    public function setExpires(?\DateTimeInterface $expires): self
    {
        $this->expires = $expires;

        return $this;
    }
    /**
     * @return Collection|User[]
     */
    public function getConfirmedBy(): Collection
    {
        return $this->confirmedBy;
    }
    public function addConfirmedBy(User $confirmedBy): self
    {
        if (!$this->confirmedBy->contains($confirmedBy)) {
            $this->confirmedBy[] = $confirmedBy;
        }

        return $this;
    }
    public function removeConfirmedBy(User $confirmedBy): self
    {
        $this->confirmedBy->removeElement($confirmedBy);

        return $this;
    }
    public function getPublicReason(): ?string
    {
        return $this->publicReason;
    }
    public function setPublicReason(string $publicReason): self
    {
        $this->publicReason = $publicReason;

        return $this;
    }
    public function getInternalReason(): ?string
    {
        return $this->internalReason;
    }
    public function setInternalReason(?string $internalReason): self
    {
        $this->internalReason = $internalReason;

        return $this;
    }
}
