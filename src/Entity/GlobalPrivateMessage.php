<?php

namespace App\Entity;

use App\Repository\GlobalPrivateMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GlobalPrivateMessageRepository::class)
 *
 */
class GlobalPrivateMessage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $receiverUser;

    /**
     * @ORM\ManyToOne(targetEntity=UserGroup::class)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $receiverGroup;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $data = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity=LogEntryTemplate::class)
     */
    private $template;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $seen;

    /**
     * @ORM\OneToMany(targetEntity=AdminReport::class, mappedBy="gpm", cascade={"remove"})
     */
    private $adminReports;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hidden;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $modMessage;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $moderator;

    public function __construct()
    {
        $this->adminReports = new ArrayCollection();
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

    public function getReceiverUser(): ?User
    {
        return $this->receiverUser;
    }

    public function setReceiverUser(?User $receiverUser): self
    {
        $this->receiverUser = $receiverUser;

        return $this;
    }

    public function getReceiverGroup(): ?UserGroup
    {
        return $this->receiverGroup;
    }

    public function setReceiverGroup(?UserGroup $receiverGroup): self
    {
        $this->receiverGroup = $receiverGroup;

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

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

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

    public function getTemplate(): ?LogEntryTemplate
    {
        return $this->template;
    }

    public function setTemplate(?LogEntryTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getSeen(): ?bool
    {
        return $this->seen;
    }

    public function setSeen(?bool $seen): self
    {
        $this->seen = $seen;

        return $this;
    }

    /**
     * @return Collection|AdminReport[]
     */
    public function getAdminReports(?bool $unseen = false): Collection
    {
        return $unseen ? $this->adminReports->filter(fn(AdminReport $a) => !$a->getSeen()) : $this->adminReports;
    }

    public function addAdminReport(AdminReport $adminReport): self
    {
        if (!$this->adminReports->contains($adminReport)) {
            $this->adminReports[] = $adminReport;
            $adminReport->setGpm($this);
        }

        return $this;
    }

    public function removeAdminReport(AdminReport $adminReport): self
    {
        if ($this->adminReports->removeElement($adminReport)) {
            // set the owning side to null (unless already changed)
            if ($adminReport->getGpm() === $this) {
                $adminReport->setGpm(null);
            }
        }

        return $this;
    }

    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(?bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getModMessage(): ?string
    {
        return $this->modMessage;
    }

    public function setModMessage(?string $modMessage): self
    {
        $this->modMessage = $modMessage;

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
}
