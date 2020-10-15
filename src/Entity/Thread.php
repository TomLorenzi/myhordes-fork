<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ThreadRepository")
 */
class Thread
{

    const SEMANTIC_BANK = 1;
    const SEMANTIC_DAILYVOTE = 2;
    const SEMANTIC_WORKSHOP = 3;
    const SEMANTIC_CONSTRUCTIONS = 4;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Forum", inversedBy="threads")
     * @ORM\JoinColumn(nullable=false)
     */
    private $forum;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     */
    private $translatable = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $owner;

    /**
     * @ORM\Column(type="boolean")
     */
    private $locked = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="thread", cascade={"persist", "remove"})
     */
    private $posts;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastPost;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ThreadReadMarker", mappedBy="thread", cascade={"persist", "remove"})
     */
    private $_readMarkers;

    private $new = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $pinned = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $semantic = 0;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForum(): ?Forum
    {
        return $this->forum;
    }

    public function setForum(?Forum $forum): self
    {
        $this->forum = $forum;

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

    public function getTranslatable(): ?bool
    {
        return $this->translatable;
    }

    public function setTranslatable(bool $translatable): self
    {
        $this->translatable = $translatable;

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

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return Collection|Post[]
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * @return Post[]
     */
    public function visiblePosts(): array
    {
        return array_filter( $this->getPosts()->getValues(), fn(Post $p) => !$p->getHidden() );
    }

    /**
     * @param bool $include_hidden
     * @return null|Post
     */
    public function lastPost(bool $include_hidden = false): ?Post
    {
        if ($this->getPosts()->isEmpty()) return null;
        for ($i = $this->getPosts()->count() - 1; $i > 0; $i--)
            if ($include_hidden || !$this->getPosts()[$i]->getHidden())
                return $this->getPosts()[$i];
        return null;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setThread($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getThread() === $this) {
                $post->setThread(null);
            }
        }

        return $this;
    }

    public function getLastPost(): ?\DateTimeInterface
    {
        return $this->lastPost;
    }

    public function setLastPost(\DateTimeInterface $lastPost): self
    {
        $this->lastPost = $lastPost;

        return $this;
    }

    public function getPinned(): ?bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): self
    {
        $this->pinned = $pinned;

        return $this;
    }

    public function getPages(): int
    {
        $postCount = $this->getPosts()->count();
        $pages = floor(($postCount - 1) / 10) + 1;

        return $pages;
    }

    public function hasReportedPosts(): bool
    {
        foreach ($this->posts as $post) if (count($post->getAdminReports(true)) > 0) return true;
        return false;
    }

    /**
     * @return Collection|Post[]
     */
    public function getUnseenReportedPosts(): Collection
    {
        return $this->posts->filter(fn(Post $p) => !$p->getAdminReports(true)->isEmpty());
    }

    public function isNew(): bool {
        return $this->new;
    }

    public function setNew(): self {
        $this->new = true;
        return $this;
    }

    public function hasAdminAnnounce(): bool {
        foreach ($this->posts as $post){
            if (preg_match("/adminAnnounce/m", $post->getText()))
                return true;
        }

        return false;
    }

    public function hasOracleAnnounce(): bool {
        foreach ($this->posts as $post){
            if (preg_match("/oracleAnnounce/m", $post->getText()))
                return true;
        }

        return false;
    }

    public function getSemantic(): ?int
    {
        return $this->semantic;
    }

    public function setSemantic(int $semantic): self
    {
        $this->semantic = $semantic;

        return $this;
    }
}
