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
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserGroup::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $association;

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
}
