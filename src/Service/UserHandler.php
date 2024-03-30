<?php

namespace App\Service;

use App\Entity\AccountRestriction;
use App\Entity\AntiSpamDomains;
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
use App\Interfaces\Entity\PictoRollupInterface;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\User\UserCapabilityService;
use App\Structures\MyHordesConf;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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
        private readonly EntityManagerInterface $entity_manager,
        private readonly ContainerInterface $container,
        private readonly ConfMaster $conf,
        private readonly DoctrineCacheService $doctrineCache,
        private readonly InvalidateTagsInAllPoolsAction $clearCache,
        private readonly UserCapabilityService $capability,
        private readonly EventProxyService $proxy,
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
     * Checks if the user has a specific role.
     * @param User $user User to check
     * @param string $role Role to check for
     * @return bool True if the user has the given role; false otherwise.
     * @deprecated User the hasRole function in UserCapabilityService instead
     */
    public function hasRole(User $user, string $role) {
        return $this->capability->hasRole( $user, $role );
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
        if (!$this->capability->hasAnyRole( $principal, ['ROLE_CROW','ROLE_ADMIN'] )) return false;

        // Crows / Admins can administer themselves
        if ($principal === $target) return true;

        // Nobody can administer a super admin
        if ($this->capability->hasRole( $target, 'ROLE_SUPER' )) return false;

        // Only super admins can administer admins
        if ($this->capability->hasRole( $target, 'ROLE_ADMIN' ) && !$this->capability->hasRole( $principal, 'ROLE_SUPER')) return false;

        // Only admins can administer crows
        if ($this->capability->hasRole( $target, 'ROLE_CROW' ) && !$this->capability->hasRole( $principal, 'ROLE_ADMIN')) return false;

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
        if (!$this->capability->hasRole( $principal, 'ROLE_ADMIN' )) return false;


        // Make sure only valid roles can be granted
        if (str_starts_with($role, 'ROLE_') && !in_array($role, $this->admin_validRoles()))                      return false;
        elseif (str_starts_with($role, 'FLAG_') && !in_array($role, $this->admin_validFlags()))                  return false;
        elseif (str_starts_with($role, '!FLAG_') && !in_array(substr($role,1), $this->admin_validFlags())) return false;

        if (!str_starts_with($role, 'ROLE_') && !str_starts_with($role, 'FLAG_') && !str_starts_with($role, '!FLAG_'))
            return false;

        // Only super admins can grant admin role
        if ($role === 'ROLE_ADMIN' && !$this->capability->hasRole( $principal, 'ROLE_SUPER' )) return false;

        // Super admin role can be granted by admins only if no super admin exists yet
        if ($role === 'ROLE_SUPER' &&  !$this->capability->hasRole( $principal, 'ROLE_SUPER' ) &&
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

        $this->proxy->pictosPersisted( $user, $nextDeath->getTown()->getSeason() );

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