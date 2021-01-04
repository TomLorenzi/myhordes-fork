<?php

namespace App\Entity;

use App\Repository\AnnouncementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnnouncementRepository::class)
 */
class Announcement
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $sender;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, fetch="EXTRA_LAZY")
     */
    private $readBy;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $lang;

    public function __construct()
    {
        $this->readBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

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

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getReadBy(): Collection
    {
        return $this->readBy;
    }

    public function addReadBy(User $readBy): self
    {
        if (!$this->readBy->contains($readBy)) {
            $this->readBy[] = $readBy;
        }

        return $this;
    }

    public function removeReadBy(User $readBy): self
    {
        $this->readBy->removeElement($readBy);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }
}
