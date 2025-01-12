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

    public function alt_post_mode(): bool { return $this->post_as_animacteur() || $this->post_as_crow() || $this->post_as_dev(); }
    public function post_as_animacteur(): bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionPostAsAnim ); }
    public function post_as_crow():       bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionPostAsCrow ); }
    public function post_as_dev():        bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionPostAsDev ); }

    public function create_thread():  bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionCreateThread ); }
    public function create_post():    bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionCreatePost ); }
    public function create_post_on_closed_thread():    bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionCreatePostOnClosedThread ); }

    public function moderate(): bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionModerate ); }
    public function help():     bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionHelp ); }
    public function own():      bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionOwn ); }

    public function only_mod_access(): bool { return $this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionModerate ) && !$this->perm->isPermitted( $this->permissions, ForumUsagePermissions::PermissionListThreads ); }
}