<?php

namespace App\Entity;

use App\Repository\OfficialGroupMessageLinkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfficialGroupMessageLinkRepository::class)]
class OfficialGroupMessageLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: OfficialGroup::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $officialGroup;
    #[ORM\OneToOne(targetEntity: UserGroup::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $messageGroup;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getOfficialGroup(): ?OfficialGroup
    {
        return $this->officialGroup;
    }
    public function setOfficialGroup(?OfficialGroup $officialGroup): self
    {
        $this->officialGroup = $officialGroup;

        return $this;
    }
    public function getMessageGroup(): ?UserGroup
    {
        return $this->messageGroup;
    }
    public function setMessageGroup(UserGroup $messageGroup): self
    {
        $this->messageGroup = $messageGroup;

        return $this;
    }
}
