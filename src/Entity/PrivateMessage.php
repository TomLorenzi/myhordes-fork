<?php

namespace App\Entity;

use App\Repository\PrivateMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrivateMessageRepository::class)]
class PrivateMessage
{
    const TEMPLATE_CROW_COMPLAINT_ON       = 1;
    const TEMPLATE_CROW_COMPLAINT_OFF      = 2;
    const TEMPLATE_CROW_TERROR             = 3;
    const TEMPLATE_CROW_THEFT              = 4;
    const TEMPLATE_CROW_AGGRESSION_FAIL    = 5;
    const TEMPLATE_CROW_AGGRESSION_SUCCESS = 6;
    const TEMPLATE_CROW_CATAPULT           = 7;
    const TEMPLATE_CROW_AVOID_TERROR       = 8;
    const TEMPLATE_CROW_NIGHTWATCH_WOUND   = 9;
    const TEMPLATE_CROW_NIGHTWATCH_TERROR  = 10;
    const TEMPLATE_CROW_INTRUSION          = 11;
    const TEMPLATE_CROW_BANISHMENT         = 12;
    const TEMPLATE_CROW_REDUCED_AP_REGEN   = 13;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $text;
    #[ORM\Column(type: 'datetime')]
    private $date;
    #[ORM\ManyToOne(targetEntity: PrivateMessageThread::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private $privateMessageThread;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $owner;
    #[ORM\Column(type: 'boolean')]
    private $new;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $recipient;
    #[ORM\Column(type: 'array', nullable: true)]
    private $items = [];
    #[ORM\Column(type: 'integer')]
    private $template = 0;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $foreignID;
    #[ORM\OneToMany(targetEntity: AdminReport::class, mappedBy: 'pm', cascade: ['remove'])]
    private $adminReports;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $hidden = false;
    #[ORM\Column(type: 'text', nullable: true)]
    private $modMessage;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $moderator;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    private $originalRecipient;
    #[ORM\Column(type: 'json', nullable: true)]
    private $additionalData = [];
    public function __construct()
    {
        $this->adminReports = new ArrayCollection();
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
    public function getOwner(): ?Citizen
    {
        return $this->owner;
    }
    public function setOwner(?Citizen $owner): self
    {
        $this->owner = $owner;

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
    public function getRecipient(): ?Citizen
    {
        return $this->recipient;
    }
    public function setRecipient(?Citizen $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }
    public function getItems(): ?array
    {
        return $this->items;
    }
    public function setItems(?array $items): self
    {
        $this->items = $items;

        return $this;
    }
    public function getTemplate(): ?int
    {
        return $this->template;
    }
    public function setTemplate(int $template): self
    {
        $this->template = $template;

        return $this;
    }
    public function getForeignID(): ?int
    {
        return $this->foreignID;
    }
    public function setForeignID(?int $foreignID): self
    {
        $this->foreignID = $foreignID;

        return $this;
    }
    /**
     * @param bool|null $unseen
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
            $adminReport->setPm($this);
        }

        return $this;
    }
    public function removeAdminReport(AdminReport $adminReport): self
    {
        if ($this->adminReports->removeElement($adminReport)) {
            // set the owning side to null (unless already changed)
            if ($adminReport->getPm() === $this) {
                $adminReport->setPm(null);
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
    public function getOriginalRecipient(): ?Citizen
    {
        return $this->originalRecipient;
    }
    public function setOriginalRecipient(?Citizen $originalRecipient): self
    {
        $this->originalRecipient = $originalRecipient;

        return $this;
    }
    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }
    public function setAdditionalData(?array $additionalData): self
    {
        $this->additionalData = $additionalData;

        return $this;
    }
}
