<?php

namespace App\Entity;

use App\Repository\ShoutboxEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShoutboxEntryRepository::class)]
class ShoutboxEntry
{
    const SBEntryTypeDefault = 0;
    const SBEntryTypeChat = 1;
    const SBEntryTypeInvite = 2;
    const SBEntryTypeJoin = 3;
    const SBEntryTypeTown = 4;
    const SBEntryTypeLeave = 5;
    const SBEntryTypeNameChange = 6;
    const SBEntryTypePromote = 7;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $type;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user1;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user2;
    #[ORM\Column(type: 'datetime')]
    private $timestamp;
    #[ORM\Column(type: 'text', nullable: true)]
    private $text;
    #[ORM\ManyToOne(targetEntity: Shoutbox::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $shoutbox;
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
    public function getUser1(): ?User
    {
        return $this->user1;
    }
    public function setUser1(?User $user1): self
    {
        $this->user1 = $user1;

        return $this;
    }
    public function getUser2(): ?User
    {
        return $this->user2;
    }
    public function setUser2(?User $user2): self
    {
        $this->user2 = $user2;

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
    public function getText(): ?string
    {
        return $this->text;
    }
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }
    public function getShoutbox(): ?Shoutbox
    {
        return $this->shoutbox;
    }
    public function setShoutbox(?Shoutbox $shoutbox): self
    {
        $this->shoutbox = $shoutbox;

        return $this;
    }
}
