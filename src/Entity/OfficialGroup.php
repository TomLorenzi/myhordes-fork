<?php

namespace App\Entity;

use App\Repository\OfficialGroupRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: OfficialGroupRepository::class)]
class OfficialGroup
{
    const SEMANTIC_NONE = 0;
    const SEMANTIC_SUPPORT = 1;
    const SEMANTIC_MODERATION = 2;
    const SEMANTIC_ANIMACTION = 3;
    const SEMANTIC_ORACLE = 4;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 5)]
    private $lang;
    #[ORM\Column(type: 'text')]
    private $description;
    #[ORM\Column(type: 'blob')]
    private $icon;
    #[ORM\Column(type: 'boolean')]
    private $anon;
    #[ORM\OneToOne(targetEntity: UserGroup::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $usergroup;
    #[ORM\Column(type: 'string', length: 32)]
    private $avatarName;
    #[ORM\Column(type: 'string', length: 9)]
    private $avatarExt;
    #[ORM\Column(type: 'integer')]
    private $semantic = 0;

    #[ORM\Column]
    private bool $ticketStyleReadMarkers = false;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLang(): ?string
    {
        return $this->lang;
    }
    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
    public function getIcon()
    {
        return $this->icon;
    }
    public function setIcon($icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    public function getAnon(): ?bool
    {
        return $this->anon;
    }
    public function setAnon(bool $anon): self
    {
        $this->anon = $anon;

        return $this;
    }
    public function getUsergroup(): ?UserGroup
    {
        return $this->usergroup;
    }
    public function setUsergroup(UserGroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }
    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }
    public function setAvatarName(string $avatarName): self
    {
        $this->avatarName = $avatarName;

        return $this;
    }
    public function getAvatarExt(): ?string
    {
        return $this->avatarExt;
    }
    public function setAvatarExt(string $avatarExt): self
    {
        $this->avatarExt = $avatarExt;

        return $this;
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

    public function isTicketStyleReadMarkers(): bool
    {
        return $this->ticketStyleReadMarkers;
    }

    public function setTicketStyleReadMarkers(bool $ticketStyleReadMarkers): static
    {
        $this->ticketStyleReadMarkers = $ticketStyleReadMarkers;

        return $this;
    }
}
