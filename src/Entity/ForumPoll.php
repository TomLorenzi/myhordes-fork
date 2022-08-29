<?php

namespace App\Entity;

use App\Repository\ForumPollRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumPollRepository::class)]
class ForumPoll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: true)]
    private $post;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $owner;
    #[ORM\Column(type: 'boolean')]
    private $closed;
    #[ORM\OneToMany(targetEntity: ForumPollAnswer::class, mappedBy: 'poll', orphanRemoval: true, cascade: ['persist'])]
    private $answers;
    #[ORM\ManyToMany(targetEntity: User::class)]
    private $participants;
    #[ORM\OneToOne(targetEntity: GlobalPoll::class, mappedBy: 'poll', cascade: ['persist', 'remove'])]
    private $globalPoll;
    public function __construct()
    {
        $this->answers = new ArrayCollection();
        $this->participants = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getPost(): ?Post
    {
        return $this->post;
    }
    public function setPost(?Post $post): self
    {
        $this->post = $post;

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
    public function getClosed(): ?bool
    {
        return $this->closed;
    }
    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }
    /**
     * @return Collection|ForumPollAnswer[]
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }
    public function addAnswer(ForumPollAnswer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setPoll($this);
        }

        return $this;
    }
    public function removeAnswer(ForumPollAnswer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getPoll() === $this) {
                $answer->setPoll(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection|User[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }
    public function addParticipant(User $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
        }

        return $this;
    }
    public function removeParticipant(User $participant): self
    {
        $this->participants->removeElement($participant);

        return $this;
    }
    public function getGlobalPoll(): ?GlobalPoll
    {
        return $this->globalPoll;
    }
    public function setGlobalPoll(GlobalPoll $globalPoll): self
    {
        // set the owning side of the relation if necessary
        if ($globalPoll->getPoll() !== $this) {
            $globalPoll->setPoll($this);
        }

        $this->globalPoll = $globalPoll;

        return $this;
    }
    public function getAllAnswerTags(): array {
        $tags = [];
        foreach ($this->getAnswers() as $answer)
            $tags = array_merge( $tags, $answer->getTagTitles() );
        return array_unique($tags);
    }
}
