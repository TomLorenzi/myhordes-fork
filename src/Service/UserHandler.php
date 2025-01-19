<?php

namespace App\Service;

use App\Entity\AccountRestriction;
use App\Entity\AntiSpamDomains;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\CitizenRankingProxy;
use App\Entity\ConsecutiveDeathMarker;
use App\Entity\FeatureUnlock;
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
use App\Enum\Configuration\MyHordesSetting;
use App\Enum\DomainBlacklistType;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\User\UserCapabilityService;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        if (!empty($user->getEmail())) {
            $existing = $repo->findOneBy( ['type' => DomainBlacklistType::EmailAddress, 'domain' => DomainBlacklistType::EmailAddress->convert( $user->getEmail() )] );
            $this->entity_manager->persist(($existing ?? new AntiSpamDomains())
                ->setType(DomainBlacklistType::EmailAddress)
                ->setDomain(DomainBlacklistType::EmailAddress->convert($user->getEmail()))
                ->setUntil(null)
            );
        }

        if (!empty($user->getEternalID())) {
            $existing = $repo->findOneBy(['type' => DomainBlacklistType::EternalTwinID, 'domain' => DomainBlacklistType::EternalTwinID->convert($user->getEternalID())]);
            $this->entity_manager->persist(($existing ?? new AntiSpamDomains())
                ->setType(DomainBlacklistType::EternalTwinID)
                ->setDomain(DomainBlacklistType::EternalTwinID->convert($user->getEternalID()))
                ->setUntil(null)
            );
        }

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

        foreach ($user->getNotificationSubscriptions() as $subscription) {
            $user->removeNotificationSubscription($subscription);
            $this->entity_manager->remove($subscription);
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
        return ['ROLE_CROW', 'ROLE_SUB_ADMIN', 'ROLE_ADMIN', 'ROLE_SUPER'];
    }

    /**
     * Returns a list of grant-able flags
     * @return string[]
     */
    public function admin_validFlags(): array {
        return ['FLAG_ORACLE', 'FLAG_ANIMAC', 'FLAG_TEAM', 'FLAG_RUFFIAN', 'FLAG_DEV','FLAG_ART'];
    }

    /**
     * Checks if a principal user can perform administrative actions on a specific user account
     * @param User $principal User to perform the administrative action
     * @param User $target User to be administered
     * @return bool
     */
    public function admin_canAdminister( User $principal, User $target ): bool {
        // Only crows and admins can administer
        if (!$this->capability->hasAnyRole( $principal, ['ROLE_CROW','ROLE_SUB_ADMIN','ROLE_ADMIN'] )) return false;

        // Crows / Admins can administer themselves
        if ($principal === $target) return true;

        // Nobody can administer a super admin
        if ($this->capability->hasRole( $target, 'ROLE_SUPER' )) return false;

        // Only super admins can administer admins
        if ($this->capability->hasRole( $target, 'ROLE_ADMIN' ) && !$this->capability->hasRole( $principal, 'ROLE_SUPER')) return false;

        // Only admins can administer sub admins
        if ($this->capability->hasRole( $target, 'ROLE_SUB_ADMIN' ) && !$this->capability->hasRole( $principal, 'ROLE_ADMIN')) return false;

        // Only sub admins can administer crows
        if ($this->capability->hasRole( $target, 'ROLE_CROW' ) && !$this->capability->hasRole( $principal, 'ROLE_SUB_ADMIN')) return false;

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

        // Only super admins can grant admin role
        if ($role === 'ROLE_SUB_ADMIN' && !$this->capability->hasRole( $principal, 'ROLE_ADMIN' )) return false;

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
        $timeout = $this->conf->getGlobalConf()->get(MyHordesSetting::CoalitionMaxInactivityDays) * 86400;

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
	 * @param bool $too_long If the username was rejected because it was too long
	 * @param int $custom_length The max length of the username
	 * @param bool $disable_preg If we should match the username against the pattern /[^\p{L}\w]/u
     * @return bool The validity of the username
     */
    public function isNameValid(string $name, ?bool &$too_long = null, int $custom_length = 16, bool $disable_preg = false): bool {
		// Define banned starting display name
        $invalidNameStarters = [
            'Corvus', 'Corbilla', '_'
        ];

		// If wanted name starts with a banned starter
        foreach ($invalidNameStarters as $starter)
            if (str_starts_with($name, $starter)) return false;

		//Define static forbidden names
        $invalidNames = [
            // The Crow
            'Der Rabe', 'Rabe', 'Le Corbeau', 'Corbeau', 'The Crow', 'Crow', 'El Cuervo', 'Cuervo',

            // Admin & Mod
            'Moderator', 'Admin', 'Administrator', 'Administrateur', 'Administrador', 'Moderador'
        ];

		// Add banned names from DB
        $additional_names = array_map(
            fn(AntiSpamDomains $a) => $a->getType()->convert( $a->getDomain() ),
            $this->entity_manager->getRepository(AntiSpamDomains::class)->findAllActive( DomainBlacklistType::BannedName )
        );

		// Calculate the Levenshtein distance between the wanted name and
		// one from the list
		// We save [distance, match]
        $closestDistance = [PHP_INT_MAX, ''];

        foreach ([...$invalidNames,...$additional_names] as $invalidName) {
			// Remove wildcard chars from the banned name
            $base = str_replace(["'", '*', '?', '[', ']', '!'], '', $invalidName);

			// Match wildcardly
            if (fnmatch(strtolower($invalidName), strtolower($name))) {
				$closestDistance = [0, $base];
			} else {
                // Calculate the levenshtein distance
                $levenshtein = levenshtein(strtolower($name), strtolower($base));
                if ($levenshtein < $closestDistance[0]) {
                    $closestDistance = [$levenshtein, $base];
                }
            }
        }

		$levenshtein_max = mb_strlen( $closestDistance[1] ) <= 5 ? 1 : 2;

        $too_long = mb_strlen($name) > $custom_length;
        return ($disable_preg || !preg_match('/[^\p{LC}]/u', $name)) && mb_strlen($name) >= 3 && !$too_long && $closestDistance[0] > $levenshtein_max;
    }

	public function isEmailValid(string $mail): bool {
		$repo = $this->entity_manager->getRepository(AntiSpamDomains::class);
		if ($repo->findActive( DomainBlacklistType::EmailAddress, $mail ))
			return false;

		$parts = explode('@', $mail, 2);
		if (count($parts) < 2) return false;
        if (str_contains($parts[0], '+')) return false;
		$parts = explode('.', $parts[1]);

		$test = '';
		while (!empty($parts)) {
			$d = array_pop($parts);
			if (empty($d)) continue;
			$test = $d . (empty($test) ? '' : ".{$test}");
			if ($repo->findActive(DomainBlacklistType::EmailDomain, $test)) {
				return false;
			}
		}

        try {
            // Let's check against Debounce.io if the email is disposable
            $ch = curl_init("https://disposable.debounce.io/?email=$mail");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5000);

            $result = json_decode(curl_exec($ch) ?: '[]', true) ?? [];
            curl_close($ch);

            if ($result["disposable"] === 'true') {
                // It is, let's deny it
                // For quicker response, we save the domain in the AntiSpam list
                $domain = substr($mail, stripos($mail, '@') + 1);
                $blackList = $repo->findOneBy( ['type' => DomainBlacklistType::EmailAddress, 'domain' => DomainBlacklistType::EmailAddress->convert( $domain )] ) ?? new AntiSpamDomains();
                $blackList->setType(DomainBlacklistType::EmailDomain)->setDomain(DomainBlacklistType::EmailDomain->convert( $domain ))->setUntil(null);
                $this->entity_manager->persist($blackList);
                $this->entity_manager->flush();
                return false;
            }
        } catch (\Throwable $th) {}

		return true;
	}

    public function confirmNextDeath(User $user, string $lastWords): bool {

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return false;

        $lastWords = $this->isRestricted( $user, AccountRestriction::RestrictionComments ) ? '' : $lastWords;
        $this->proxy->deathConfirmed( $nextDeath, $lastWords, true );

        $this->entity_manager->persist( $nextDeath );
        $this->entity_manager->flush();

        $this->proxy->deathConfirmed( $nextDeath, $lastWords, false );

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