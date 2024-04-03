<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\ForumRepository')]
class Forum
{
    const ForumTypeDefault = 0;
    const ForumTypeElevated = 1;
    const ForumTypeMods = 2;
    const ForumTypeAdmins = 3;
    const ForumTypeCustom = 4;
    const ForumTypeAnimac = 5;
    const ForumTypeDev = 6;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Town', inversedBy: 'forum', cascade: ['persist'])]
    private $town;
    #[ORM\Column(type: 'string', length: 128)]
    private $title;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Thread', mappedBy: 'forum', cascade: ['persist', 'remove'])]
    private $threads;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $type;
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;
    #[ORM\Column(type: 'string', length: 190, nullable: true)]
    private $icon;
    #[ORM\ManyToMany(targetEntity: ThreadTag::class)]
    private $allowedTags;
    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    private $worldForumLanguage;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $worldForumSorting;

    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: ForumTitle::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $titles;
    public function __construct()
    {
        $this->threads = new ArrayCollection();
        $this->allowedTags = new ArrayCollection();
        $this->titles = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTown(): ?Town
    {
        return $this->town;
    }
    public function setTown(?Town $town): self
    {
        $this->town = $town;

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

    public function getLocalizedTitle(string $lang): ?string {
        $entity = $this->getTitles()->matching( (new Criteria())
            ->where( new Comparison( 'language', Comparison::EQ, $lang )  )
        )->first();
        return $entity ? $entity->getTitle() : $this->getTitle();
    }

    public function getLocalizedSlug(string $lang): ?string {
        $base = $this->getLocalizedTitle($lang);
        $slug = implode('',
            array_map(
                fn(string $s) => mb_substr($s, 0, 1),
                explode( ' ', $base )
            )
        );

        return mb_strlen($slug) === 1 ? mb_substr( $base, 0, 3 ) : $slug;
    }

    public function getLocalizedDescription(string $lang): ?string {
        $entity = $this->getTitles()->matching( (new Criteria())
            ->where( new Comparison( 'language', Comparison::EQ, $lang )  )
        )->first();
        return $entity ? $entity->getDescription() : $this->getDescription();
    }

    /**
     * @return Collection|Thread[]
     */
    public function getThreads(): Collection
    {
        return $this->threads;
    }
    public function addThread(Thread $thread): self
    {
        if (!$this->threads->contains($thread)) {
            $this->threads[] = $thread;
            $thread->setForum($this);
        }

        return $this;
    }
    public function removeThread(Thread $thread): self
    {
        if ($this->threads->contains($thread)) {
            $this->threads->removeElement($thread);
            // set the owning side to null (unless already changed)
            if ($thread->getForum() === $this) {
                $thread->setForum(null);
            }
        }

        return $this;
    }
    public function getType(): ?int
    {
        return $this->type;
    }
    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    /**
     * @return Collection|ThreadTag[]
     */
    public function getAllowedTags(): Collection
    {
        return $this->allowedTags;
    }
    public function addAllowedTag(ThreadTag $allowedTag): self
    {
        if (!$this->allowedTags->contains($allowedTag)) {
            $this->allowedTags[] = $allowedTag;
        }

        return $this;
    }
    public function removeAllowedTag(ThreadTag $allowedTag): self
    {
        $this->allowedTags->removeElement($allowedTag);

        return $this;
    }
    public function getWorldForumLanguage(): ?string
    {
        return $this->worldForumLanguage;
    }
    public function setWorldForumLanguage(?string $worldForumLanguage): self
    {
        $this->worldForumLanguage = $worldForumLanguage;

        return $this;
    }
    public function getWorldForumSorting(): ?int
    {
        return $this->worldForumSorting;
    }
    public function setWorldForumSorting(?int $worldForumSorting): self
    {
        $this->worldForumSorting = $worldForumSorting;

        return $this;
    }

    /**
     * @return Collection<int, ForumTitle>
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function addTitle(ForumTitle $title): static
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->setForum($this);
        }

        return $this;
    }

    public function removeTitle(ForumTitle $title): static
    {
        if ($this->titles->removeElement($title)) {
            // set the owning side to null (unless already changed)
            if ($title->getForum() === $this) {
                $title->setForum(null);
            }
        }

        return $this;
    }
}
