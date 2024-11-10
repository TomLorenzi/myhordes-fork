<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\PostRepository')]
class Post
{
    const EditorLocked = 0;
    const EditorTimed = 1;
    const EditorPerpetual = 2;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Thread', inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private $thread;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: true)]
    private $owner;
    #[ORM\Column(type: 'text')]
    private $text;
    #[ORM\Column(type: 'text', nullable: true)]
    private $note;
    #[ORM\Column(type: 'datetime')]
    private $date;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $edited;
    #[ORM\Column(type: 'boolean')]
    private $hidden = false;
    #[ORM\Column(type: 'string', length: 255)]
    private $type = "USER";
    #[ORM\OneToMany(targetEntity: 'App\Entity\AdminReport', mappedBy: 'post', orphanRemoval: true)]
    private $adminReports;
    #[ORM\OneToOne(targetEntity: 'App\Entity\AdminDeletion', mappedBy: 'post', orphanRemoval: true, cascade: ['remove'])]
    private $adminDeletion;
    #[ORM\OneToMany(targetEntity: 'App\Entity\ThreadReadMarker', mappedBy: 'post', cascade: ['remove'])]
    private $_readMarkers;
    private bool $new = false;
    private bool $hydrated = false;
    private ?string $hydrated_text = null;
    private ?string $hydrated_prev = null;
    #[ORM\Column(type: 'boolean')]
    private $translate = false;
    #[ORM\Column(type: 'integer')]
    private $editingMode = 0;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $lastAdminActionBy;
    #[ORM\Column(type: 'text', nullable: true)]
    private $originalText;
    #[ORM\Column(type: 'text', nullable: true)]
    private $searchText = null;
    #[ORM\ManyToOne(targetEntity: Forum::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private $searchForum = null;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $reported;

    #[ORM\Column]
    private bool $anonymous = false;

    #[ORM\Column(nullable: true)]
    private ?array $noteIcons = null;
    public function __construct()
    {
        $this->adminReports = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getThread(): ?Thread
    {
        return $this->thread;
    }
    public function setThread(?Thread $thread): self
    {
        $this->thread = $thread;
        $this->setSearchForum( $thread ? $thread->getForum() : null );

        return $this;
    }
    public function getOwner(): ?User
    {
        return $this->owner;
    }
    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
    public function getText(): ?string
    {
        return $this->hydrated_text ?? $this->text;
    }
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
    public function getNote(): ?string
    {
        return $this->note;
    }
    public function setNote(?string $note): self
    {
        $this->note = $note;

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
    public function getEdited(): ?\DateTimeInterface
    {
        return $this->edited;
    }
    public function setEdited(?\DateTimeInterface $edited): self
    {
        $this->edited = $edited;

        return $this;
    }
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }
    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(string $type): self
    {
        $this->type = $type;

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
            $adminReport->setPost($this);
        }

        $this->setReported( $this->getReported() + 1);

        return $this;
    }
    public function removeAdminReport(AdminReport $adminReport): self
    {
        if ($this->adminReports->contains($adminReport)) {
            $this->adminReports->removeElement($adminReport);
            // set the owning side to null (unless already changed)
            if ($adminReport->getPost() === $this) {
                $adminReport->setPost(null);
            }
        }

        $this->setReported( max(0, $this->getReported() + 1));

        return $this;
    }
    /**
     * @return AdminDeletion|null
     */
    public function getAdminDeletion(): ?AdminDeletion
    {
        return $this->adminDeletion;
    }

    /**
     * @param AdminDeletion $adminDeletion
     * @return self
     */
    public function setAdminDeletion(?AdminDeletion $adminDeletion): self
    {
        $this->adminDeletion = $adminDeletion;
        return $this;
    }
    public function isNew(): bool {
        return $this->new;
    }
    public function setNew(): self {
        $this->new = true;
        return $this;
    }
    public function getHydrated(): bool {
        return $this->hydrated;
    }
    public function setHydrated(string $text, ?string $prev = null): self {
        if (!$this->hydrated) {
            $this->hydrated = true;
            $this->hydrated_text = $text;
            $this->hydrated_prev = $prev;
        }
        return $this;
    }
    public function getTranslate(): ?bool
    {
        return $this->translate;
    }
    public function setTranslate(bool $translate): self
    {
        $this->translate = $translate;

        return $this;
    }
    public function getEditingMode(): ?int
    {
        return $this->editingMode;
    }
    public function setEditingMode(int $editingMode): self
    {
        $this->editingMode = $editingMode;

        return $this;
    }
    public function isEditable(): bool {
        if ($this->getTranslate()) return false;
        return match ($this->getEditingMode()) {
            self::EditorTimed => (time() - $this->getDate()->getTimestamp()) < 600,
            self::EditorPerpetual => true,
            default => false,
        };
    }
    public function getLastAdminActionBy(): ?User
    {
        return $this->lastAdminActionBy;
    }
    public function setLastAdminActionBy(?User $lastAdminActionBy): self
    {
        $this->lastAdminActionBy = $lastAdminActionBy;

        return $this;
    }
    public function getOriginalText(): ?string
    {
        return $this->hydrated_prev ?? $this->originalText;
    }
    public function setOriginalText(?string $originalText): self
    {
        $this->originalText = $originalText;

        return $this;
    }
    public function getSearchText(): ?string
    {
        return $this->searchText;
    }
    public function setSearchText(?string $searchText): self
    {
        $this->searchText = $searchText;

        return $this;
    }
    public function getSearchForum(): ?Forum
    {
        return $this->searchForum;
    }
    public function setSearchForum(?Forum $searchForum): self
    {
        $this->searchForum = $searchForum;

        return $this;
    }
    public function getReported(): ?bool
    {
        return $this->reported;
    }
    public function setReported(?bool $reported): self
    {
        $this->reported = $reported;

        return $this;
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

    public function getNoteIcons(): ?array
    {
        return $this->noteIcons;
    }

    public function setNoteIcons(?array $noteIcons): static
    {
        $this->noteIcons = $noteIcons;

        return $this;
    }
}
