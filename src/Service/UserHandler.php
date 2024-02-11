<?php

namespace App\Service;

use App\Entity\AccountRestriction;
use App\Entity\AntiSpamDomains;
use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\CitizenRankingProxy;
use App\Entity\ConsecutiveDeathMarker;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\Season;
use App\Entity\Shoutbox;
use App\Entity\ShoutboxEntry;
use App\Entity\SocialRelation;
use App\Entity\TownRankingProxy;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Entity\UserSwapPivot;
use App\Enum\DomainBlacklistType;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Structures\MyHordesConf;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserHandler
{
    const NoError                            = 0;
    const ErrorAvatarBackendUnavailable      = ErrorHelper::BaseAvatarErrors +  1;
    const ErrorAvatarTooLarge                = ErrorHelper::BaseAvatarErrors +  2;
    const ErrorAvatarFormatUnsupported       = ErrorHelper::BaseAvatarErrors +  3;
    const ErrorAvatarImageBroken             = ErrorHelper::BaseAvatarErrors +  4;
    const ErrorAvatarResolutionUnacceptable  = ErrorHelper::BaseAvatarErrors +  5;
    const ErrorAvatarProcessingFailed        = ErrorHelper::BaseAvatarErrors +  6;
    const ErrorAvatarInsufficientCompression = ErrorHelper::BaseAvatarErrors +  7;
    const ErrorAvatarTooManyFrames = ErrorHelper::BaseAvatarErrors + 8;

    public function __construct(
        private EntityManagerInterface $entity_manager,
        private RoleHierarchyInterface $roles,
        private ContainerInterface $container,
        private CrowService $crow,
        private TranslatorInterface $translator,
        private ConfMaster $conf,
        private DoctrineCacheService $doctrineCache,
        private TagAwareCacheInterface $gameCachePool,
        private InvalidateTagsInAllPoolsAction $clearCache,
    )
    { }

    public function fetchSoulPoints(User $user, bool $all = true, bool $useCached = false): int {
        if ($useCached) return $all ? $user->getAllSoulPoints() : $user->getSoulPoints();
        $p_soul = $all ? $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]) : null;
        if ($p_soul) $p_soul =['www.hordes.fr' => 'fr', 'www.die2nite.com' => 'en', 'www.dieverdammten.de' => 'de', 'www.zombinoia.com' => 'es'][$p_soul->getScope()] ?? 'none';
        return array_reduce( array_filter(
            $this->entity_manager->getRepository(CitizenRankingProxy::class)->findBy(['user' => $user, 'confirmed' => true]),
            function(CitizenRankingProxy $c) use ($all,$p_soul) { return !$c->hasDisableFlag(CitizenRankingProxy::DISABLE_SOULPOINTS) && $c->getTown() && !$c->getTown()->hasDisableFlag(TownRankingProxy::DISABLE_SOULPOINTS) && $c->getTown()->getSeason() !== null && ($c->getImportLang() === null || ($all && $c->getImportLang() === $p_soul) ); }
        ), fn(int $carry, CitizenRankingProxy $next) => $carry + ($next->getPoints() ?? 0), 0 );
    }

    public function fetchImportedSoulPoints(User $user): int {
        $p_soul = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);
        if ($p_soul === null) return 0;
        $p_soul = ['www.hordes.fr' => 'fr', 'www.die2nite.com' => 'en', 'www.dieverdammten.de' => 'de', 'www.zombinoia.com' => 'es'][$p_soul->getScope()] ?? 'none';
        return array_reduce( array_filter(
            $this->entity_manager->getRepository(CitizenRankingProxy::class)->findBy(['user' => $user, 'confirmed' => true]),
            function(CitizenRankingProxy $c) use ($p_soul) { return
                !$c->hasDisableFlag(CitizenRankingProxy::DISABLE_SOULPOINTS) &&
                !$c->getLimitedImport() &&
                $c->getTown() && !$c->getTown()->hasDisableFlag(TownRankingProxy::DISABLE_SOULPOINTS) &&
                $c->getImportLang() === $p_soul;
            }
        ), fn(int $carry, CitizenRankingProxy $next) => $carry + ($next->getPoints() ?? 0), 0 );
    }

    public function getPoints(User $user, ?bool $imported = null, ?bool $old = false){
        $sp = $imported === null ? $user->getAllSoulPoints() : ( $imported ? $user->getImportedSoulPoints() : $user->getSoulPoints() );
        if ($old) $sp = 0;
        $pictos = $old
            ? $this->entity_manager->getRepository(Picto::class)->findOldByUser($user)
            : $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user, $imported);
        $points = 0;

        if($sp >= 100)  $points += 13;
        if($sp >= 500)  $points += 33;
        if($sp >= 1000) $points += 66;
        if($sp >= 2000) $points += 132;
        if($sp >= 3000) $points += 198;

        foreach ($pictos as $picto) {
            switch($picto["name"]){
                case "r_heroac_#00": case "r_explor_#00":
                    if ($picto["c"] >= 15)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_cookr_#00": case "r_cmplst_#00": case "r_camp_#00":case "r_drgmkr_#00": case "r_jtamer_#00":
                case "r_jrangr_#00": case "r_jguard_#00": case "r_jermit_#00": case "r_jtech_#00": case "r_jcolle_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 25)
                        $points += 6.5;
                    break;
                case "r_animal_#00": case "r_plundr_#00":
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
                case "r_buildr_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 200)
                        $points += 6.5;
                    break;
                case "r_nodrug_#00":
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
                case "r_theft_#00":
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
                case "r_killz_#00":
                    if($picto["c"] >= 100)
                        $points += 3.5;
                    if($picto["c"] >= 200)
                        $points += 6.5;
                    if($picto["c"] >= 300)
                        $points += 10;
                    if($picto["c"] >= 800)
                        $points += 13;
                    break;
                case "r_cannib_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 40)
                        $points += 6.5;
                    break;
            }
        }

        return $points;
    }

    public function computePictoUnlocks(User $user): void {

        $cache = [];

        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
        foreach ($pictos as $picto)
            $cache[$picto['id']] = $picto['c'];

        $skip_proto = [];
        $remove_awards = [];
        $award_awards = [];

        /** @var Award $award */
        foreach ($user->getAwards() as $award) {
            if ($award->getPrototype()) $skip_proto[] = $award->getPrototype();
            if ($award->getPrototype() && $award->getPrototype()->getAssociatedPicto() &&
                (!isset($cache[$award->getPrototype()->getAssociatedPicto()->getId()]) || $cache[$award->getPrototype()->getAssociatedPicto()->getId()] < $award->getPrototype()->getUnlockQuantity())
            )
                $remove_awards[] = $award;
        }

        foreach ($this->entity_manager->getRepository(AwardPrototype::class)->findAll() as $prototype)
            if (!in_array($prototype,$skip_proto) &&
                (isset($cache[$prototype->getAssociatedPicto()->getId()]) && $cache[$prototype->getAssociatedPicto()->getId()] >= $prototype->getUnlockQuantity())
            ) {
                $user->addAward($award = (new Award())->setPrototype($prototype));
                $this->entity_manager->persist($award_awards[] = $award);
            }

        if (!empty($award_awards))
            $this->entity_manager->persist($this->crow->createPM_titleUnlock($user, $award_awards));


        foreach ($remove_awards as $r) {
            if ($user->getActiveIcon() === $r) $user->setActiveIcon(null);
            if ($user->getActiveTitle() === $r) $user->setActiveTitle(null);
            $user->removeAward($r);
            $this->entity_manager->remove($r);
        }

        try {
            if (!empty($award_awards) || !empty($remove_awards))
                $this->gameCachePool->invalidateTags(["user-{$user->getId()}-emote-unlocks"]);
        } catch (\Throwable $t) {}

    }

    public function hasSkill(User $user, $skill){
        if(is_string($skill)) {
            $skill = $this->doctrineCache->getEntityByIdentifier(HeroSkillPrototype::class, $skill);
            if($skill === null)
                return false;
        }

        $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($user->getAllHeroDaysSpent());
        return in_array($skill, $skills);
    }

    public function hasSeenLatestChangelog(User $user, ?string $fallback_lang): bool {

        $lang = $user->getLanguage() ?? $fallback_lang ?? 'de';
        $latest_cl = $this->entity_manager->getRepository(Changelog::class)->findBy(['lang' => $lang], ['date' => 'DESC'], 1);
        if (empty($latest_cl)) return true;

        $seen_cl = $user->getLatestChangelog();
        if ($seen_cl === null) return false;

        return $latest_cl[0] === $seen_cl;
    }

    public function setSeenLatestChangelog(User $user, ?string $fallback_lang) {

        $lang = $user->getLanguage() ?? $fallback_lang ?? 'de';
        $latest_cl = $this->entity_manager->getRepository(Changelog::class)->findBy(['lang' => $lang], ['date' => 'DESC'], 1);
        if (empty($latest_cl)) return;

        $user->setLatestChangelog($latest_cl[0]);
        $this->entity_manager->persist($user);
    }

    public function deleteUser(User $user): void
    {
        $repo = $this->entity_manager->getRepository(AntiSpamDomains::class);

        if (!empty($user->getEmail()) && !$repo->findOneBy( ['type' => DomainBlacklistType::EmailAddress, 'domain' => DomainBlacklistType::EmailAddress->convert( $user->getEmail() )] ))
            $this->entity_manager->persist( (new AntiSpamDomains())
                ->setType( DomainBlacklistType::EmailAddress )
                ->setDomain( DomainBlacklistType::EmailAddress->convert( $user->getEmail() ) )
            );

        if (!empty($user->getEternalID()) && !$repo->findOneBy( ['type' => DomainBlacklistType::EternalTwinID, 'domain' => DomainBlacklistType::EternalTwinID->convert( $user->getEternalID() )] ))
            $this->entity_manager->persist( (new AntiSpamDomains())
                ->setType( DomainBlacklistType::EternalTwinID )
                ->setDomain( DomainBlacklistType::EternalTwinID->convert( $user->getEternalID() ) )
            );

        $user
            ->setEmail("$ deleted <{$user->getId()}>")->setDisplayName(null)
            ->setName("\${$user->getId()}")
            ->setEternalID(null)
            ->setDeleteAfter(null)
            ->setPassword(null)
            ->setLastActionTimestamp( null )
            ->setRightsElevation(0);

        if ($user->getAvatar()) {
            $this->entity_manager->remove($user->getAvatar());
            ($this->clearCache)("user_avatar_{$user->getId()}");
            $user->setAvatar(null);
        }

         $user_coalitions = $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => [
                    UserGroupAssociation::GroupAssociationTypeCoalitionMember,
                    UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive,
                    UserGroupAssociation::GroupAssociationTypeCoalitionInvitation
                ] ]
        );

        foreach ($user_coalitions as $coalition) {
            $destroy = $coalition->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder;
            if ($destroy) {
                foreach ($this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                    'association' => $coalition->getAssociation()
                ]) as $assoc ) $this->entity_manager->remove($assoc);
                $this->entity_manager->remove( $coalition->getAssociation() );
            } else {
                $this->entity_manager->remove( $coalition );
                /** @var Shoutbox|null $shoutbox */
                if ($shoutbox = $this->getShoutbox($coalition)) {
                    $shoutbox->addEntry(
                        (new ShoutboxEntry())
                            ->setType( ShoutboxEntry::SBEntryTypeLeave )
                            ->setTimestamp( new DateTime() )
                            ->setUser1( $user )
                    );
                    $this->entity_manager->persist($shoutbox);
                }
            }
        }

        $citizen = $user->getActiveCitizen();
        if ($citizen) {
            $r = [];
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
        return ['ROLE_CROW', 'ROLE_ADMIN', 'ROLE_SUPER'];
    }

    /**
     * Returns a list of grant-able flags
     * @return string[]
     */
    public function admin_validFlags(): array {
        return ['FLAG_ORACLE', 'FLAG_ANIMAC', 'FLAG_TEAM', 'FLAG_RUFFIAN', 'FLAG_DEV'];
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
        if (str_starts_with($role, 'ROLE_') && !in_array($role, $this->admin_validRoles()))                      return false;
        elseif (str_starts_with($role, 'FLAG_') && !in_array($role, $this->admin_validFlags()))                  return false;
        elseif (str_starts_with($role, '!FLAG_') && !in_array(substr($role,1), $this->admin_validFlags())) return false;

        if (!str_starts_with($role, 'ROLE_') && !str_starts_with($role, 'FLAG_') && !str_starts_with($role, '!FLAG_'))
            return false;

        // Only super admins can grant admin role
        if ($role === 'ROLE_ADMIN' && !$this->hasRole( $principal, 'ROLE_SUPER' )) return false;

        // Super admin role can be granted by admins only if no super admin exists yet
        if ($role === 'ROLE_SUPER' &&  !$this->hasRole( $principal, 'ROLE_SUPER' ) &&
            $this->entity_manager->getRepository(User::class)->findByLeastElevationLevel(User::USER_LEVEL_SUPER)
        ) return false;

        return true;
    }

    public function getCoalitionMembership(User $user): ?UserGroupAssociation {
        return $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
                'user' => $user,
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive] ]
        );
    }

    /**
     * @param User|UserGroup|UserGroupAssociation $principal
     * @return Shoutbox|null
     */
    public function getShoutbox($principal): ?Shoutbox {

        if (is_a($principal, User::class)) $principal = $this->getCoalitionMembership($principal);
        if (is_a($principal, UserGroupAssociation::class) && in_array($principal->getAssociationType(),
            [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
            )) $principal = $principal->getAssociation();
        if (is_a($principal, UserGroup::class) && $principal->getType() === UserGroup::GroupSmallCoalition)
            return $this->entity_manager->getRepository(Shoutbox::class)->findOneBy(['userGroup' => $principal]);

        return null;
    }

    public function getConsecutiveDeathLock(User $user, bool &$warning = null): bool {
        /** @var ConsecutiveDeathMarker $cdm */
        $cdm = $this->entity_manager->getRepository(ConsecutiveDeathMarker::class)->findOneBy(['user' => $user]);

        $warning = $cdm ? ($cdm->getDeath()->getRef() === CauseOfDeath::Dehydration && $cdm->getNumber() === 2) : false;
        return $cdm ? ($cdm->getDeath()->getRef() === CauseOfDeath::Dehydration && $cdm->getNumber() >= 3 && $cdm->getTimestamp() > (new \DateTime('today - 2week'))) : false;
    }

    /**
     * @param User $user
     * @param int|null $full_member_count
     * @param bool|null $active
     * @return User[]
     */
    public function getAvailableCoalitionMembers(User $user, ?int &$full_member_count = null, ?bool &$active = null): array {
        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
                'user' => $user,
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive] ]
        );

        /** @var UserGroupAssociation[] $all_coalition_members */
        $all_coalition_members = $user_coalition ? $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
            'association' => $user_coalition->getAssociation(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
        ]) : [];

        $full_member_count = count($all_coalition_members);
        $active = false;

        $valid_members = [];
        $timeout = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_COA_MAX_DAYS_INACTIVITY) * 86400;

        foreach ($all_coalition_members as $member)
            if (
                $member->getAssociationType() === UserGroupAssociation::GroupAssociationTypeCoalitionMember &&
                $member->getUser()->getLastActionTimestamp() !== null &&
                ($timeout <= 0 || $member->getUser()->getLastActionTimestamp()->getTimestamp() > (time() - $timeout)) &&
                $member->getUser()->getActiveCitizen() === null &&
                !$this->getConsecutiveDeathLock($member->getUser()) &&
                !$this->isRestricted( $member->getUser(), AccountRestriction::RestrictionGameplay )
            ) {
                if ($member->getUser() === $user) $active = true;
                else $valid_members[] = $member->getUser();
            }


        return $active ? $valid_members : [];
    }

    /**
     * @param User $user
     * @return User[]
     */
    public function getAllOtherCoalitionMembers(User $user): array {
        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
            'user' => $user,
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
        ]);

        /** @var UserGroupAssociation[] $all_coalition_members */
        $all_coalition_members = $user_coalition ? $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
            'association' => $user_coalition->getAssociation(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
        ]) : [];

        return array_filter( array_map( fn(UserGroupAssociation $ua) => $ua->getUser(), $all_coalition_members ), fn(User $u) => $u !== $user );
    }

    public function getActiveRestrictions(User $user): int {
        $r = AccountRestriction::RestrictionNone;

        /** @var QueryBuilder $qb */
        $qb = $this->entity_manager->getRepository(AccountRestriction::class)->createQueryBuilder('a');
        foreach ($qb
                     ->select('a.restriction AS r')
                     ->andWhere('a.user = :user' )->setParameter('user', $user)
                     ->andWhere('(a.active = TRUE AND a.confirmed = true)')
                     ->andWhere('(a.expires IS NULL or a.expires > :now)')->setParameter('now', new DateTime())
                     ->getQuery()->getResult() as $entry)

            $r |= $entry['r'];

        return $r;
    }

    public function getActiveRestrictionExpiration(User $user, ?int $restriction): ?DateTime {
        $dt = null;

        $qb = $this->entity_manager->getRepository(AccountRestriction::class)->createQueryBuilder('a');
        foreach ($qb
                     ->andWhere('a.user = :user' )->setParameter('user', $user)
                     ->andWhere('(a.active = TRUE AND a.confirmed = true)')
                     ->andWhere('a.restriction != :nores')->setParameter('nores', AccountRestriction::RestrictionNone)
                     ->andWhere('(a.expires IS NULL or a.expires > :now)')->setParameter('now', new DateTime())
                     ->getQuery()->getResult() as $entry)
            /** @var AccountRestriction $entry */
            if ( $restriction === null || ($entry->getRestriction() & $restriction) === $restriction ) {
                if ($entry->getExpires() === null) return null;
                if ($dt === null || $entry->getExpires() > $dt)
                    $dt = $entry->getExpires();
            }

        return $dt;
    }

    public function isRestricted(User $user, ?int $restriction = null): bool {
        $r = $this->getActiveRestrictions($user);
        return $restriction === null ? ($r !== AccountRestriction::RestrictionNone) : (($r & $restriction) === $restriction);
    }

    protected array $_relation_cache = [];
    public function checkRelation( User $user, User $relation, int $type, bool $any_direction = false ): bool {
        if ($user === $relation) return false;
        $key = "{$user->getId()}:{$relation->getId()}:{$type}";
        return (
            $this->_relation_cache[$key] ??
            ( $this->_relation_cache[$key] = (bool)$this->entity_manager->getRepository(SocialRelation::class)->findOneBy(['owner' => $user, 'related' => $relation, 'type' => $type]) )
        ) || ($any_direction && $this->checkRelation($relation, $user, $type, false));
    }

    public function checkFeatureUnlock(User $user, $feature, bool $deduct): bool {
        /** @var FeatureUnlock $r */
        $r = $this->entity_manager->getRepository(FeatureUnlock::class)->findOneActiveForUser(
            $user,
            $this->entity_manager->getRepository(Season::class)->findLatest(),
            $feature
        );

        // If we're not in deduct mode or don't have an entity, simply return if an entity was found
        if (!$deduct || $r === null) return $r !== null;

        // If we're in deduct mode and the feature has a town count expiration, reduce town count
        if ($r->getExpirationMode() === FeatureUnlock::FeatureExpirationTownCount)
            $this->entity_manager->persist( $r->setTownCount( max(0,$r->getTownCount() - 1 )) );
        return true;
    }

    /**
     * Tests if the username wanted is valid
     * Uses Levenshtein's algo
     * @param string $name The username to test
     * @return bool The validity of the username
     */
    public function isNameValid(string $name, ?bool &$too_long = null, $custom_length = 16): bool {
        $invalidNames = [
            // The Crow
            'Der Rabe', 'Rabe', 'Le Corbeau', 'Corbeau', 'The Crow', 'Crow', 'El Cuervo', 'Cuervo',

            // Admin & Mod
            'Moderator', 'Admin', 'Administrator', 'Administrateur', 'Administrador', 'Moderador'
        ];

        $invalidNameStarters = [
            'Corvus', 'Corbilla', '_'
        ];

        $closestDistance = [PHP_INT_MAX, ''];
        foreach ($invalidNames as $invalidName) {
            $levenshtein = levenshtein($name, $invalidName);
            if ($levenshtein < $closestDistance[0])
                $closestDistance = [ $levenshtein, $invalidName ];
        }

        foreach ($invalidNameStarters as $starter)
            if (str_starts_with($name, $starter)) return false;

        $levenshtein_max = mb_strlen( $closestDistance[1] ) <= 5 ? 1 : 2;

        $too_long = mb_strlen($name) > $custom_length;
        return !preg_match('/[^\p{L}\w]/u', $name) && mb_strlen($name) >= 3 && !$too_long && $closestDistance[0] > $levenshtein_max;
    }

    public function getMaximumEntryHidden(User $user): int {
        $limit = 0;
        if($this->hasSkill($user, 'manipulator'))
            $limit = 2;

        if($this->hasSkill($user, 'treachery'))
            $limit = 4;

        return $limit;
    }

    public function confirmNextDeath(User $user, string $lastWords): bool {

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return false;

        if ($nextDeath->getCod()->getRef() != CauseOfDeath::Poison && $nextDeath->getCod()->getRef() != CauseOfDeath::GhulEaten)
            $last_words = str_replace(['{','}'], ['(',')'], $lastWords);
        else $last_words = '{gotKilled}';

        if ($nextDeath->getGenerosityBonus() > 0 && !$nextDeath->getDisabled() && !$nextDeath->getTown()->getDisabled()) {

            $generosity = $this->doctrineCache->getEntityByIdentifier(FeatureUnlockPrototype::class, 'f_share');
            /** @var FeatureUnlock $instance */
            $instance = $this->entity_manager->getRepository(FeatureUnlock::class)->findBy([
                'user' => $user, 'expirationMode' => FeatureUnlock::FeatureExpirationTownCount,
                'prototype' =>$this->doctrineCache->getEntityByIdentifier(FeatureUnlockPrototype::class, 'f_share')
            ])[0] ?? null;
            if (!$instance) $instance = (new FeatureUnlock())->setPrototype( $generosity )->setUser( $user )
                ->setExpirationMode( FeatureUnlock::FeatureExpirationTownCount )->setTownCount($nextDeath->getGenerosityBonus());
            else $instance->setTownCount( $instance->getTownCount() + $nextDeath->getGenerosityBonus() );

            $this->entity_manager->persist( $instance );
        }

        // Here, we delete picto with persisted = 0,
        // and definitively validate picto with persisted = 1
        /** @var Picto[] $pendingPictosOfUser */
        $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUserAndTown($user, $nextDeath->getTown());
        foreach ($pendingPictosOfUser as $pendingPicto) {
            if($pendingPicto->getPersisted() == 0)
                $this->entity_manager->remove($pendingPicto);
            else {
                $pendingPicto
                    ->setPersisted(2)
                    ->setDisabled( $nextDeath->hasDisableFlag(CitizenRankingProxy::DISABLE_PICTOS) || $nextDeath->getTown()->hasDisableFlag(TownRankingProxy::DISABLE_PICTOS) );
                $this->entity_manager->persist($pendingPicto);
            }
        }
        if ($active = $nextDeath->getCitizen()) {
            $active->setActive(false);
            $active->setLastWords( $this->isRestricted( $user, AccountRestriction::RestrictionComments ) ? '' : $last_words);
            $nextDeath = CitizenRankingProxy::fromCitizen( $active, true );
            $this->entity_manager->persist( $active );
        }

        $nextDeath->setConfirmed(true)->setLastWords( $this->isRestricted( $user, AccountRestriction::RestrictionComments ) ? '' : $last_words );

        $this->entity_manager->persist( $nextDeath );
        $this->entity_manager->flush();

        $this->computePictoUnlocks($user);
        $this->entity_manager->flush();

        // Update soul points
        $user->setSoulPoints( $this->fetchSoulPoints( $user, false ) );
        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        return true;
    }

    /**
     * @param User $user
     * @param bool $asPrincipal
     * @param bool $asSecondary
     * @return User[]
     */
    public function getAllPivotUserRelationsFor(User $user, bool $asPrincipal = true, bool $asSecondary = true): array {
        $users = [];
        if ($asPrincipal)
            $users = array_merge($users, array_map(fn(UserSwapPivot $p) => $p->getSecondary(), $this->entity_manager->getRepository(UserSwapPivot::class)->findBy(['principal' => $user])));
        if ($asSecondary)
            $users = array_merge($users, array_map(fn(UserSwapPivot $p) => $p->getPrincipal(), $this->entity_manager->getRepository(UserSwapPivot::class)->findBy(['secondary' => $user])));

        return $users;
    }
}