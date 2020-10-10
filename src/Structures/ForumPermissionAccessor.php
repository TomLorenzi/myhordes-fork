<?php


namespace App\Structures;

use App\Entity\ForumUsagePermissions;
use App\Service\PermissionHandler;

class ForumPermissionAccessor
{
    private int $permissions;
    private PermissionHandler $perm;

    public function __construct(int $permissions, PermissionHandler $permissionHandler)
    {
        $this->permissions = $permissions;
        $this->perm = $permissionHandler;
    }

    public function format_admin():  bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionFormattingAdmin ); }
    public function format_mod():    bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionFormattingModerator ); }
    public function format_oracle(): bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionFormattingOracle ); }

    public function post_as_crow(): bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionPostAsCrow ); }
    public function post_as_dev():  bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionPostAsDev ); }

    public function create_thread():  bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionCreateThread ); }
    public function create_post():    bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionCreatePost ); }

    public function moderate(): bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionModerate ); }
    public function own():      bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionOwn ); }
}