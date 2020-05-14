<?php

namespace App\Entity;

use App\Repository\PrivateMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrivateMessageRepository::class)
 */
class PrivateMessage
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity=PrivateMessageThread::class, inversedBy="messages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $privateMessageThread;

    /**
     * @ORM\ManyToMany(targetEntity=Item::class)
     */
    private $items;

    /**
     * @ORM\ManyToOne(targetEntity=Citizen::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $owner;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPrivateMessageThread(): ?PrivateMessageThread
    {
        return $this->privateMessageThread;
    }

    public function setPrivateMessageThread(?PrivateMessageThread $privateMessageThread): self
    {
        $this->privateMessageThread = $privateMessageThread;

        return $this;
    }

    /**
     * @return Collection|Item[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }

        return $this;
    }

    public function getOwner(): ?Citizen
    {
        return $this->owner;
    }

    public function setOwner(?Citizen $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
