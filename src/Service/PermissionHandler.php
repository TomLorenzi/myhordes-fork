<?php

namespace App\Service;

use App\Entity\AccountRestriction;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;

class PermissionHandler
{

    private EntityManagerInterface $entity_manager;
    private UserHandler $user_handler;

    public function __construct( EntityManagerInterface $em, UserHandler $uh)
    {
        $this->entity_manager = $em;
        $this->user_handler = $uh;
    }

    private function get_assoc( User $user, UserGroup $group): ?UserGroupAssociation {
        return $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $user, 'association' => $group]);
    }

    /**
     * @param User $user
     * @return UserGroup[]
     */
    public function userGroups( User $user ): array {
        return array_map(
            fn( UserGroupAssociation $a ) => $a->getAssociation(),
            $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy(['user' => $user])
        );
    }

    /**
     * @param UserGroup $group
     * @return User[]
     */
    public function usersInGroup( UserGroup $group ): array {
        return array_map(
            fn( UserGroupAssociation $a ) => $a->getUser(),
            $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy(['association' => $group])
        );
    }

    public function getDefaultGroup( int $sem ): ?UserGroup {
        return $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => $sem]);
    }

    public function userInGroup( User $user, UserGroup $group): bool {
        return $this->get_assoc($user,$group) !== null;
    }

    public function associate( User $user, UserGroup $group,
                               int $type = UserGroupAssociation::GroupAssociationTypeDefault,
                               int $level = UserGroupAssociation::GroupAssociationLevelDefault,
                               int $ref1 = null ): UserGroupAssociation {
        $existing_assoc = $this->get_assoc( $user, $group );
        if (!$existing_assoc) $this->entity_manager->persist(
            $existing_assoc = (new UserGroupAssociation())
                ->setUser($user)
                ->setAssociation($group)
                ->setAssociationType($type)
                ->setAssociationLevel($level)
                ->setRef1($ref1)
        );
        return $existing_assoc;
    }

    public function disassociate( User $user, UserGroup $group): bool {
        $a = $this->get_assoc($user,$group);
        if ($a) $this->entity_manager->remove($a);
        return $a !== null;
    }

    /**
     * @param User $user
     * @param int $permission
     * @return array
     */
    public function getForumsWithPermission(User $user, int $permission = ForumUsagePermissions::PermissionRead ): array {
        $groups = $this->userGroups($user);

        $grant = $deny = 0;

        /** @var QueryBuilder $qb */
        $qb = $this->entity_manager->getRepository(ForumUsagePermissions::class)->createQueryBuilder('p');
        foreach ($qb
            ->andWhere('p.forum IS NULL')
            ->andWhere('(p.principalUser = :user AND p.principalGroup IS NULL) OR (p.principalUser IS NULL AND p.principalGroup IN (:groups))')
            ->setParameter('user', $user)->setParameter('groups', $groups)
            ->getQuery()->getResult() as $entry) {

            /** @var $entry ForumUsagePermissions */
            $grant |= $entry->getPermissionsGranted();
            $deny |= $entry->getPermissionsDenied();
        }

        $match = ($grant & (~$deny)) & $permission;
        if (($match === $permission) || (($grant & (~$deny)) & ForumUsagePermissions::PermissionOwn)) {

            $matched_by_owning = ($match !== $permission);

            // Default permissions granted to all forums; we need to look for forums where the permission is
            // explicitly denied and reverse the list
            $denied_forums = [];

            /** @var QueryBuilder $qb */
            $qb = $this->entity_manager->getRepository(ForumUsagePermissions::class)->createQueryBuilder('p');
            foreach ($qb
                ->select('IDENTITY(p.forum) AS fid', 'p.permissionsDenied AS pd')
                ->andWhere( 'p.forum IS NOT NULL' )
                ->andWhere( '(p.principalUser = :user AND p.principalGroup IS NULL) OR (p.principalUser IS NULL AND p.principalGroup IN (:groups))')
                ->setParameter('user', $user)->setParameter('groups', $groups)
                ->getQuery()->getResult() as $entry) {

                if ($matched_by_owning && ($entry['pd'] & ForumUsagePermissions::PermissionOwn))  $denied_forums[] = $entry['fid'];
                elseif ($permission & $entry['pd']) $denied_forums[] = $entry['fid'];
            }

            if (empty($denied_forums)) return $this->entity_manager->getRepository(Forum::class)->findAll();

            /** @var QueryBuilder $qb */
            $qb = $this->entity_manager->getRepository(Forum::class)->createQueryBuilder('f');
            $qb->andWhere('f.id NOT IN (:denied)')->setParameter('denied', $denied_forums);
            if ($this->user_handler->isRestricted($user, AccountRestriction::RestrictionGameplay))
                $qb->andWhere('f.town IS NULL');
            return $qb->getQuery()->getResult();

        } else {

            // Default permissions do not grant access to all forums; we need to look for forums where the permission is
            // explicitly granted
            $granted_forums = [];
            $forum_perms = [];

            /** @var QueryBuilder $qb */
            $qb = $this->entity_manager->getRepository(ForumUsagePermissions::class)->createQueryBuilder('p');
            foreach ($qb
                ->select('IDENTITY(p.forum) AS fid', 'p.permissionsGranted AS pg', 'p.permissionsDenied AS pd')
                ->andWhere( 'p.forum IS NOT NULL' )
                ->andWhere( '(p.principalUser = :user AND p.principalGroup IS NULL) OR (p.principalUser IS NULL AND p.principalGroup IN (:groups))')
                ->setParameter('user', $user)->setParameter('groups', $groups)
                ->getQuery()->getScalarResult() as $entry) {

                if (!isset( $forum_perms[$entry['fid']] )) $forum_perms[$entry['fid']] = [0,0];
                $forum_perms[$entry['fid']][0] |= $entry['pg'];
                $forum_perms[$entry['fid']][1] |= $entry['pd'];
            }

            foreach ($forum_perms as $fid => list($granted, $denied))
                if ( ($permission & $granted & (~$denied)) === $permission ) $granted_forums[] = $fid;

            /** @var QueryBuilder $qb */
            $qb = $this->entity_manager->getRepository(Forum::class)->createQueryBuilder('f');
            return $qb->where('f.id IN (:granted)')->setParameter('granted', $granted_forums)->getQuery()->getResult();

        }


    }

    public function getEffectiveUserPermissions( User $user, Forum $forum ) {
        $grant = $deny = 0;

        /** @var QueryBuilder $qb */
        $qb = $this->entity_manager->getRepository(ForumUsagePermissions::class)->createQueryBuilder('p');
        foreach ($qb
            ->select('p.permissionsGranted AS pg', 'p.permissionsDenied AS pd')
            ->andWhere( 'p.principalUser = :user')->setParameter('user', $user)
            ->andWhere( 'p.principalGroup IS NULL')
            ->andWhere( 'p.forum = :forum OR p.forum IS NULL' )->setParameter('forum', $forum)
            ->getQuery()->getResult() as $entry) {

            $grant |= $entry['pg'];
            $deny  |= $entry['pd'];
        }

        return $grant & (~$deny);
    }

    public function getEffectiveGroupPermissions( UserGroup $group, Forum $forum ): int {
        $grant = $deny = 0;

        /** @var QueryBuilder $qb */
        $qb = $this->entity_manager->getRepository(ForumUsagePermissions::class)->createQueryBuilder('p');
        foreach ($qb
            ->select('p.permissionsGranted AS pg', 'p.permissionsDenied AS pd')
            ->andWhere( 'p.principalUser IS NULL')
            ->andWhere( 'p.principalGroup = :group')->setParameter('group', $group)
            ->andWhere( 'p.forum = :forum OR p.forum IS NULL' )->setParameter('forum', $forum)
            ->getQuery()->getResult() as $entry) {

            $grant |= $entry['pg'];
            $deny  |= $entry['pd'];
        }

        return $grant & (~$deny);
    }

    public function getEffectivePermissions( User $user, ?Forum $forum ): int {
        $grant = $deny = 0;
        if ($forum === null) return ForumUsagePermissions::PermissionNone;

        if ($forum->getTown() && $this->user_handler->isRestricted($user, AccountRestriction::RestrictionGameplay))
            return ForumUsagePermissions::PermissionNone;

        /** @var QueryBuilder $qb */
        $qb = $this->entity_manager->getRepository(ForumUsagePermissions::class)->createQueryBuilder('p');
        foreach ($qb
            ->select('p.permissionsGranted AS pg', 'p.permissionsDenied AS pd')
            ->andWhere( 'p.forum = :forum OR p.forum IS NULL' )->setParameter('forum', $forum)
            ->andWhere( '(p.principalUser = :user AND p.principalGroup IS NULL) OR (p.principalUser IS NULL AND p.principalGroup IN (:groups))')
            ->setParameter('user', $user)->setParameter('groups', $this->userGroups($user))
            ->getQuery()->getResult() as $entry) {

            $grant |= $entry['pg'];
            $deny  |= $entry['pd'];
        }

        return $grant & (~$deny);
    }

    public function isPermitted( int $effectivePermissions, int $permission ): bool {
        return (($effectivePermissions & $permission) === $permission) || ($effectivePermissions & ForumUsagePermissions::PermissionOwn);
    }

    public function isAnyPermitted( int $effectivePermissions, array $permissions ): bool {
        foreach ($permissions as $permission) if ($this->isPermitted($effectivePermissions, $permission)) return true;
        return false;
    }

    public function checkEffectivePermissions( User $user, Forum $forum, int $perm ): bool {
        return $this->isPermitted( $this->getEffectivePermissions($user, $forum), $perm );
    }

    public function checkAnyEffectivePermissions( User $user, Forum $forum, array $perm ): bool {
        return $this->isAnyPermitted( $this->getEffectivePermissions($user, $forum), $perm );
    }
}