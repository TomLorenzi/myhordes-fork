<?php

namespace App\Service;

use App\Entity\CauseOfDeath;
use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserHandler
{
    private $entity_manager;
    private $roles;
    private $container;

    public function __construct( EntityManagerInterface $em, RoleHierarchyInterface $roles,ContainerInterface $c)
    {
        $this->entity_manager = $em;
        $this->container = $c;
        $this->roles = $roles;
    }

    public function getPoints(User $user){
        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
        $points = 0;

        if($user->getAllSoulPoints() >= 100) {
            $points += 13;
        }
        if($user->getAllSoulPoints() >= 500) {
            $points += 33;
        }
        if($user->getAllSoulPoints() >= 1000) {
            $points += 66;
        }
        if($user->getAllSoulPoints() >= 2000) {
            $points += 132;
        }
        if($user->getAllSoulPoints() >= 3000) {
            $points += 198;
        }

        foreach ($pictos as $picto) {
            switch($picto["name"]){
                case "r_heroac_#00": case "r_explor_#00":
                    if ($picto["c"] >= 15)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_cookr_#00": case "r_cmplst_#00": case "r_camp_#00": case "r_drgmkr_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 25)
                        $points += 6.5;
                    break;
                case "r_animal_#00":
                    if ($picto["c"] >= 30)
                        $points += 3.5;
                    if ($picto["c"] >= 60)
                        $points += 6.5;
                    break;
                case "r_chstxl_#00": case "r_ruine_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    break;
                case "r_build_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 200)
                        $points += 6.5;
                    break;
                case "status_clean_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 75)
                        $points += 6.5;
                    break;
                case "r_ebuild_#00":
                    if ($picto["c"] >= 1)
                        $points += 3.5;
                    if ($picto["c"] >= 3)
                        $points += 6.5;
                    break;
                case "r_digger_#00":
                    if ($picto["c"] >= 50)
                        $points += 3.5;
                    if ($picto["c"] >= 300)
                        $points += 6.5;
                    break;
                case "r_deco_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 250)
                        $points += 6.5;
                    break;
                case "r_explo2_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    break;
                case "r_guide_#00":
                    if ($picto["c"] >= 300)
                        $points += 3.5;
                    if ($picto["c"] >= 1000)
                        $points += 6.5;
                    break;
                case "r_theft_#00": case "r_jtamer_#00": case "r_jrangr_#00": case "r_jguard_#00": case "r_jermit_#00":
                case "r_jtech_#00": case "r_jcolle_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_maso_#00": case "r_guard_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 40)
                        $points += 6.5;
                    break;
                case "r_surlst_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    if ($picto["c"] >= 30)
                        $points += 10;
                    if ($picto["c"] >= 50)
                        $points += 13;
                    if ($picto["c"] >= 100)
                        $points += 16.5;
                    break;
                case "r_suhard_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    if ($picto["c"] >= 20)
                        $points += 10;
                    if ($picto["c"] >= 40)
                        $points += 13;
                    break;
                case "r_doutsd_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    break;
                case "r_door_#00":
                    if($picto["c"] >= 1)
                        $points += 3.5;
                    if($picto["c"] >= 5)
                        $points += 6.5;
                    break;
                case "r_wondrs_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    if($picto["c"] >= 50)
                        $points += 6.5;
                    break;
                case "r_rp_#00":
                    if($picto["c"] >= 5)
                        $points += 3.5;
                    if($picto["c"] >= 10)
                        $points += 6.5;
                    if($picto["c"] >= 20)
                        $points += 10;
                    if($picto["c"] >= 30)
                        $points += 13;
                    if($picto["c"] >= 40)
                        $points += 16.5;
                    if($picto["c"] >= 60)
                        $points += 20;
                    break;
                case "r_winbas_#00":
                    if($picto["c"] >= 2)
                        $points += 13;
                    if($picto["c"] >= 5)
                        $points += 20;
                    break;
                case "r_wintop_#00":
                    if($picto["c"] >= 1)
                        $points += 20;
                    break;
                case "small_zombie_#00":
                    if($picto["c"] >= 100)
                        $points += 3.5;
                    if($picto["c"] >= 200)
                        $points += 6.5;
                    if($picto["c"] >= 300)
                        $points += 10;
                    if($picto["c"] >= 800)
                        $points += 13;
                    break;
            }
        }

        return $points;
    }

    public function hasSkill(User $user, $skill){
        if(is_string($skill)) {
            $skill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findOneBy(['name' => $skill]);
            if($skill === null)
                return false;
        }

        $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($user->getAllHeroDaysSpent());
        return in_array($skill, $skills);
    }

    public function deleteUser(User $user) {
        $user->setEmail("$ deleted <{$user->getId()}>")->setName("$ deleted <{$user->getId()}>")->setPassword(null)->setRightsElevation(0);
        if ($user->getAvatar()) {
            $this->entity_manager->remove($user->getAvatar());
            $user->setAvatar(null);
        }
        $citizen = $user->getActiveCitizen();
        if ($citizen) {
            $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::Headshot, $r );
            foreach ($r as $re) $this->entity_manager->remove($re);
        }
    }

    /**
     * Checks if the given user has specified roles. The relation type is controlled by $any. If $roles is an empty
     * array, this function will return true in AND mode and false in OR mode.
     * @param User $user User to check
     * @param array $roles Roles to check for
     * @param bool $any Set true to check if any of the given roles apply (OR); set false to check if all roles apply
     * (AND).
     * @return bool
     */
    public function hasRoles(User $user, array $roles, bool $any = false): bool {
        $effectiveRoles = $this->roles->getReachableRoleNames( $user->getRoles() );
        foreach ($roles as $role) if ($any === in_array( $role, $effectiveRoles )) return $any;
        return !$any;
    }

    /**
     * Checks if the user has a specific role.
     * @param User $user User to check
     * @param string $role Role to check for
     * @return bool True if the user has the given role; false otherwise.
     */
    public function hasRole(User $user, string $role) {
        return in_array( $role, $this->roles->getReachableRoleNames( $user->getRoles() ) );
    }

    /**
     * Returns a list of grant-able roles
     * @return string[]
     */
    public function admin_validRoles(): array {
        return ['ROLE_ORACLE', 'ROLE_CROW', 'ROLE_ADMIN', 'ROLE_SUPER'];
    }

    /**
     * Checks if a principal user can perform administrative actions on a specific user account
     * @param User $principal User to perform the administrative action
     * @param User $target User to be administered
     * @return bool
     */
    public function admin_canAdminister( User $principal, User $target ): bool {
        // Only crows and admins can administer
        if (!$this->hasRoles( $principal, ['ROLE_CROW','ROLE_ADMIN'], true )) return false;

        // Crows / Admins can administer themselves
        if ($principal === $target) return true;

        // Nobody can administer a super admin
        if ($this->hasRole( $target, 'ROLE_SUPER' )) return false;

        // Only super admins can administer admins
        if ($this->hasRole( $target, 'ROLE_ADMIN' ) && !$this->hasRole( $principal, 'ROLE_SUPER')) return false;

        // Only admins can administer crows
        if ($this->hasRole( $target, 'ROLE_CROW' ) && !$this->hasRole( $principal, 'ROLE_ADMIN')) return false;

        return true;
    }

    /**
     * Checks if the given user can grant a specific role
     * @param User $principal
     * @param string $role
     * @return bool
     */
    public function admin_canGrant( User $principal, string $role ): bool {
        // Only admins can grant roles
        if (!$this->hasRole( $principal, 'ROLE_ADMIN' )) return false;

        // Make sure only valid roles can be granted
        if (!in_array($role, $this->admin_validRoles())) return false;

        // Only super admins can grant admin role
        if ($role === 'ROLE_ADMIN' && !$this->hasRole( $principal, 'ROLE_SUPER' )) return false;

        // Super admin role can be granted by admins only if no super admin exists yet
        if ($role === 'ROLE_SUPER' &&  !$this->hasRole( $principal, 'ROLE_SUPER' ) &&
            $this->entity_manager->getRepository(User::class)->findByLeastElevationLevel(User::ROLE_SUPER)
        ) return false;

        return true;
    }
}