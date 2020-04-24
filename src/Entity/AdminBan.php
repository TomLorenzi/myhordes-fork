<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AdminBanRepository")
 */
class AdminBan
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="bannings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sourceUser;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Post")
     * @ORM\JoinColumn(nullable=true)
     */
    private $post;

    /**
     * @ORM\Column(type="string")
     */
    private $reason;

    /**
     * @ORM\Column(type="datetimetz")
     */
    private $banStart;

    /**
     * @ORM\Column(type="datetimetz")
     */
    private $banEnd;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $lifted = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $liftUser;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSourceUser(): ?User
    {
        return $this->sourceUser;
    }

    public function setSourceUser(User $sourceUser): self
    {
        $this->sourceUser = $sourceUser;

        return $this;
    }

    public function getBanStart(): ?\DateTimeInterface
    {
        return $this->banStart;
    }

    public function setBanStart(\DateTimeInterface $banStart): self
    {
        $this->banStart = $banStart;

        return $this;
    }

    public function getBanEnd(): ?\DateTimeInterface
    {
        return $this->banEnd;
    }

    public function setBanEnd(\DateTimeInterface $banEnd): self
    {
        $this->banEnd = $banEnd;

        return $this;
    }

    public function getActive(): bool
    {
        if ($this->getBanEnd() > new DateTime("now") && !( $this->getLifted() )) return true;
        return false;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function getLifted(): ?bool
    {
        return $this->lifted;
    }

    public function setLifted(?bool $lifted): self
    {
        $this->lifted = $lifted;

        return $this;
    }

    public function getLiftUser(): ?User
    {
        return $this->liftUser;
    }

    public function setLiftUser(?User $liftUser): self
    {
        $this->liftUser = $liftUser;

        return $this;
    }
}
