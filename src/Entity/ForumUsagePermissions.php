<?php

namespace App\Entity;

use App\Repository\ForumUsagePermissionsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=ForumUsagePermissionsRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="permission_assoc_unique",columns={"principal_user_id", "principal_group_id", "forum_id"})
 * })
 */
class ForumUsagePermissions
{
    const PermissionNone     = 0;
    const PermissionRead     = 1 << 0;
    const PermissionWrite    = 1 << 1;
    const PermissionModerate = 1 << 2;
    const PermissionOwn      = 1 << 3;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $principalUser;

    /**
     * @ORM\ManyToOne(targetEntity=UserGroup::class)
     */
    private $principalGroup;

    /**
     * @ORM\ManyToOne(targetEntity=Forum::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $forum;

    /**
     * @ORM\Column(type="integer")
     */
    private $permissionsGranted;

    /**
     * @ORM\Column(type="integer")
     */
    private $permissionsDenied;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrincipalUser(): ?User
    {
        return $this->principalUser;
    }

    public function setPrincipalUser(?User $principalUser): self
    {
        $this->principalUser = $principalUser;

        return $this;
    }

    public function getPrincipalGroup(): ?UserGroup
    {
        return $this->principalGroup;
    }

    public function setPrincipalGroup(?UserGroup $principalGroup): self
    {
        $this->principalGroup = $principalGroup;

        return $this;
    }

    public function getForum(): ?Forum
    {
        return $this->forum;
    }

    public function setForum(?Forum $forum): self
    {
        $this->forum = $forum;

        return $this;
    }

    public function getPermissionsGranted(): ?int
    {
        return $this->permissionsGranted;
    }

    public function setPermissionsGranted(int $permissionsGranted): self
    {
        $this->permissionsGranted = $permissionsGranted;

        return $this;
    }

    public function getPermissionsDenied(): ?int
    {
        return $this->permissionsDenied;
    }

    public function setPermissionsDenied(int $permissionsDenied): self
    {
        $this->permissionsDenied = $permissionsDenied;

        return $this;
    }
}
