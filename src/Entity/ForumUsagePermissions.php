<?php

namespace App\Entity;

use App\Repository\ForumUsagePermissionsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: ForumUsagePermissionsRepository::class)]
#[Table]
#[UniqueConstraint(name: 'permission_assoc_unique', columns: ['principal_user_id', 'principal_group_id', 'forum_id'])]
class ForumUsagePermissions
{
    const int PermissionNone     = 0;

    // Read Permissions
    const int PermissionListThreads  =  1 << 1;
    const int PermissionReadThreads  =  1 << 2;
    const int PermissionRead         = ForumUsagePermissions::PermissionListThreads |
                                       ForumUsagePermissions::PermissionReadThreads;
    // Write Permissions
    const int PermissionCreatePost   =  1 << 6;
    const int PermissionCreateThread =  1 << 7;
    const int PermissionEditPost     =  1 << 8;
    const int PermissionWrite        = ForumUsagePermissions::PermissionCreatePost   |
                                       ForumUsagePermissions::PermissionCreateThread |
                                       ForumUsagePermissions::PermissionEditPost;
    const int PermissionCreatePostOnClosedThread =  1 << 9;

    // Common
    const int PermissionReadWrite = ForumUsagePermissions::PermissionRead | ForumUsagePermissions::PermissionWrite;
    // Moderation
    const int PermissionModerate     = 1 << 10;
    const int PermissionOwn          = 1 << 11;
    const int PermissionHelp         = 1 << 12;

    // Functionality
    const int PermissionFormattingOracle    = 1 << 16;
    const int PermissionFormattingModerator = 1 << 17;
    const int PermissionFormattingAdmin     = 1 << 18;
    const int PermissionPostAsCrow          = 1 << 19;
    const int PermissionPostAsDev           = 1 << 20;
    const int PermissionPostAsAnim          = 1 << 21;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $principalUser;
    #[ORM\ManyToOne(targetEntity: UserGroup::class)]
    private $principalGroup;
    #[ORM\ManyToOne(targetEntity: Forum::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private $forum;
    #[ORM\Column(type: 'integer')]
    private $permissionsGranted;
    #[ORM\Column(type: 'integer')]
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
