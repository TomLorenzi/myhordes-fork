<?php

namespace App\Entity;

use App\Repository\UserGroupAssociationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=UserGroupAssociationRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="user_group_assoc_unique",columns={"user_id", "association_id"})
 * })
 */
class UserGroupAssociation
{
    const GroupAssociationTypeDefault   = 0;
    const GroupAssociationTypeCoalitionInvitation     = 1000;
    const GroupAssociationTypeCoalitionMember         = 1001;
    const GroupAssociationTypeCoalitionMemberInactive = 1002;

    const GroupAssociationTypePrivateMessageMember         = 2000;
    const GroupAssociationTypePrivateMessageMemberInactive = 2001;

    const GroupAssociationLevelDefault = 0;
    const GroupAssociationLevelFounder = 100;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity=UserGroup::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?UserGroup $association = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $associationType = self::GroupAssociationTypeDefault;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $associationLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ref1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ref2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ref3;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ref4;


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

    public function getAssociation(): ?UserGroup
    {
        return $this->association;
    }

    public function setAssociation(UserGroup $association): self
    {
        $this->association = $association;

        return $this;
    }

    public function getAssociationType(): ?int
    {
        return $this->associationType;
    }

    public function setAssociationType(?int $associationType): self
    {
        $this->associationType = $associationType;

        return $this;
    }

    public function getAssociationLevel(): ?int
    {
        return $this->associationLevel;
    }

    public function setAssociationLevel(?int $associationLevel): self
    {
        $this->associationLevel = $associationLevel;

        return $this;
    }

    public function getRef1(): ?int
    {
        return $this->ref1;
    }

    public function setRef1(?int $ref1): self
    {
        $this->ref1 = $ref1;

        return $this;
    }

    public function getRef2(): ?int
    {
        return $this->ref2;
    }

    public function setRef2(?int $ref2): self
    {
        $this->ref2 = $ref2;

        return $this;
    }

    public function getRef3(): ?int
    {
        return $this->ref3;
    }

    public function setRef3(?int $ref3): self
    {
        $this->ref3 = $ref3;

        return $this;
    }

    public function getRef4(): ?int
    {
        return $this->ref4;
    }

    public function setRef4(?int $ref4): self
    {
        $this->ref4 = $ref4;

        return $this;
    }
}
