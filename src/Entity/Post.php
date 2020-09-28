<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 */
class Post
{
    const EditorLocked = 0;
    const EditorTimed = 1;
    const EditorPerpetual = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Thread", inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $thread;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $owner;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $edited;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hidden = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type = "USER";

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AdminReport", mappedBy="post", orphanRemoval=true)
     */
    private $adminReports;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AdminDeletion", mappedBy="post", orphanRemoval=true, cascade={"remove"})
     */
    private $_adminDeletion;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ThreadReadMarker", mappedBy="post", cascade={"remove"})
     */
    private $_readMarkers;

    private $new = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $translate = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $editingMode = 0;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $lastAdminActionBy;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $originalText;

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
        return $this->text;
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
     * @return Collection|AdminReport[]
     */
    public function getAdminReports(?bool $unseen = false): Collection
    {
        if ($unseen) {
            $reports = $this->adminReports;
            foreach ($this->adminReports as $idx => $report) {
                if ($report->getSeen())
                    $reports->remove($idx);
            }
            return $reports;
        }
        else 
            return $this->adminReports;
    }

    public function addAdminReport(AdminReport $adminReport): self
    {
        if (!$this->adminReports->contains($adminReport)) {
            $this->adminReports[] = $adminReport;
            $adminReport->setPost($this);
        }

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

        return $this;
    }

    public function isNew(): bool {
        return $this->new;
    }

    public function setNew(): self {
        $this->new = true;
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
        switch ($this->getEditingMode()) {
            case self::EditorTimed: return (time() - $this->getDate()->getTimestamp()) < 600;
            case self::EditorPerpetual: return true;

            default: return false;
        }
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
        return $this->originalText;
    }

    public function setOriginalText(?string $originalText): self
    {
        $this->originalText = $originalText;

        return $this;
    }
}
