<?php

namespace App\Entity;

use App\Repository\PrivateMessageThreadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrivateMessageThreadRepository::class)]
class PrivateMessageThread
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 255)]
    private $title;
    #[ORM\OneToMany(targetEntity: PrivateMessage::class, mappedBy: 'privateMessageThread', orphanRemoval: true)]
    private $messages;
    #[ORM\Column(type: 'boolean')]
    private $new = false;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private $sender;
    #[ORM\ManyToOne(targetEntity: Citizen::class, inversedBy: 'privateMessageThreads')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $recipient;
    #[ORM\Column(type: 'datetime')]
    private $lastMessage;
    #[ORM\Column(type: 'boolean')]
    private $archived = false;
    #[ORM\Column(type: 'boolean')]
    private $locked;

    #[ORM\Column]
    private ?bool $anonymous = false;
    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
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
    /**
     * @return Collection|PrivateMessage[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }
    public function addMessage(PrivateMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setPrivateMessageThread($this);
        }

        return $this;
    }
    public function removeMessage(PrivateMessage $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getPrivateMessageThread() === $this) {
                $message->setPrivateMessageThread(null);
            }
        }

        return $this;
    }
    public function getNew(): ?bool
    {
        return $this->new;
    }
    public function setNew(bool $new): self
    {
        $this->new = $new;

        return $this;
    }
    public function getSender(): ?Citizen
    {
        return $this->sender;
    }
    public function setSender(?Citizen $sender): self
    {
        $this->sender = $sender;

        return $this;
    }
    public function getRecipient(): ?Citizen
    {
        return $this->recipient;
    }
    public function setRecipient(?Citizen $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }
    public function getLastMessage(): ?\DateTimeInterface
    {
        return $this->lastMessage;
    }
    public function setLastMessage(\DateTimeInterface $lastMessage): self
    {
        $this->lastMessage = $lastMessage;

        return $this;
    }
    public function getArchived(): ?bool
    {
        return $this->archived;
    }
    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }
    public function getLocked(): ?bool
    {
        return $this->locked;
    }
    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }
    public function hasItems(): bool
    {
        foreach ($this->getMessages() as $message) {
            if($message->getItems() != null && count($message->getItems()) > 0)
                return true;
        }

        return false;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): static
    {
        $this->anonymous = $anonymous;

        return $this;
    }
}
