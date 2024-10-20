<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\AdminReportSpecification;

#[ORM\Entity(repositoryClass: 'App\Repository\AdminReportRepository')]
class AdminReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Post', inversedBy: 'adminReports')]
    #[ORM\JoinColumn(nullable: true)]
    private $post;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    private $sourceUser;
    #[ORM\Column(type: 'datetime')]
    private $ts;
    #[ORM\Column(type: 'boolean')]
    private $seen = false;
    #[ORM\ManyToOne(targetEntity: PrivateMessage::class, inversedBy: 'adminReports')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private $pm;
    #[ORM\ManyToOne(targetEntity: GlobalPrivateMessage::class, inversedBy: 'adminReports')]
    #[ORM\JoinColumn(nullable: true)]
    private $gpm;
    #[ORM\Column(type: 'integer')]
    private $reason = 0;
    #[ORM\Column(type: 'text', nullable: true)]
    private $details = null;
    #[ORM\ManyToOne(targetEntity: BlackboardEdit::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private $blackBoard;
    #[ORM\ManyToOne(targetEntity: CitizenRankingProxy::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private $citizen;
    #[ORM\Column(type: 'integer', enumType: AdminReportSpecification::class)]
    private AdminReportSpecification $specification = AdminReportSpecification::None;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;
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
    public function getReason(): ?int
    {
        return $this->reason;
    }
    public function setReason(int $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
    public function getDetails(): ?string
    {
        return $this->details;
    }
    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }
    public function getBlackBoard(): ?BlackboardEdit
    {
        return $this->blackBoard;
    }
    public function setBlackBoard(?BlackboardEdit $blackBoard): self
    {
        $this->blackBoard = $blackBoard;

        return $this;
    }
    public function getCitizen(): ?CitizenRankingProxy
    {
        return $this->citizen;
    }
    public function setCitizen(?CitizenRankingProxy $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }
    public function getSpecification(): ?AdminReportSpecification
    {
        return $this->specification;
    }
    public function setSpecification(AdminReportSpecification $specification): self
    {
        $this->specification = $specification;

        return $this;
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
}
