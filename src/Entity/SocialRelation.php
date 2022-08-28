<?php

namespace App\Entity;

use App\Repository\SocialRelationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: SocialRelationRepository::class)]
#[Table]
#[UniqueConstraint(name: 'social_relation_unique', columns: ['owner_id', 'related_id', 'type'])]
class SocialRelation
{
    const SocialRelationTypeBlock = 1;
    const SocialRelationTypeNotInterested = 2;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $owner;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $related;
    #[ORM\Column(type: 'integer')]
    private $type;
    public function getId(): ?int
    {
        return $this->id;
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
    public function getRelated(): ?User
    {
        return $this->related;
    }
    public function setRelated(?User $related): self
    {
        $this->related = $related;

        return $this;
    }
    public function getType(): ?int
    {
        return $this->type;
    }
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
