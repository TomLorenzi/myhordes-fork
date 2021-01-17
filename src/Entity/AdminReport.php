<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AdminReportRepository")
 */
class AdminReport
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Post", inversedBy="adminReports")
     * @ORM\JoinColumn(nullable=true)
     */
    private $post;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sourceUser;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ts;

    /**
     * @ORM\Column(type="boolean")
     */
    private $seen = false;

    /**
     * @ORM\ManyToOne(targetEntity=PrivateMessage::class, inversedBy="adminReports")
     * @ORM\JoinColumn(nullable=true)
     */
    private $pm;

    /**
     * @ORM\ManyToOne(targetEntity=GlobalPrivateMessage::class, inversedBy="adminReports")
     * @ORM\JoinColumn(nullable=true)
     */
    private $gpm;

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

    public function getSourceUser(): ?User
    {
        return $this->sourceUser;
    }

    public function setSourceUser(?User $sourceUser): self
    {
        $this->sourceUser = $sourceUser;

        return $this;
    }

    public function getTs(): ?\DateTimeInterface
    {
        return $this->ts;
    }

    public function setTs(\DateTimeInterface $ts): self
    {
        $this->ts = $ts;

        return $this;
    }

    public function getSeen(): ?bool
    {
        return $this->seen;
    }

    public function setSeen(bool $seen): self
    {
        $this->seen = $seen;

        return $this;
    }

    public function getPm(): ?PrivateMessage
    {
        return $this->pm;
    }

    public function setPm(?PrivateMessage $pm): self
    {
        $this->pm = $pm;

        return $this;
    }

    public function getGpm(): ?GlobalPrivateMessage
    {
        return $this->gpm;
    }

    public function setGpm(?GlobalPrivateMessage $gpm): self
    {
        $this->gpm = $gpm;

        return $this;
    }
}
