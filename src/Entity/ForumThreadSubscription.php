<?php

namespace App\Entity;

use App\Repository\ForumThreadSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=ForumThreadSubscriptionRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="forum_sub_unique",columns={"user_id", "thread_id"})
 * })
 */
class ForumThreadSubscription
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="forumThreadSubscriptions")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?User $user;

    /**
     * @ORM\ManyToOne(targetEntity=Thread::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?Thread $thread;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $notify = true;

    /**
     * @ORM\Column(type="integer")
     */
    private int $num = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function getNotify(): ?bool
    {
        return $this->notify;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;

        return $this;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(int $num): self
    {
        $this->num = $num;

        return $this;
    }
}
