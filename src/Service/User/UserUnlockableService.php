<?php

namespace App\Service\User;

use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\HeroExperienceEntry;
use App\Entity\HeroSkillPrototype;
use App\Entity\HeroSkillUnlock;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\Configuration\MyHordesSetting;
use App\Enum\HeroXPType;
use App\EventListener\ContainerTypeTrait;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\ConfMaster;
use ArrayHelpers\Arr;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class UserUnlockableService implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly TagAwareCacheInterface $gameCachePool
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            InvalidateTagsInAllPoolsAction::class,
            ConfMaster::class
        ];
    }

    /**
     * Generates default query builder to query for hxp entries
     * @param User $user
     * @param bool $ignore_reset
     * @return QueryBuilder
     */
    private function generateDefaultQuery(User $user, bool $ignore_reset = false): QueryBuilder {
        $qb = $this->getService(EntityManagerInterface::class)->createQueryBuilder()
            ->from(HeroExperienceEntry::class, 'x')
            // Join ranking proxies so we can observe their DISABLED state
            ->leftJoin(TownRankingProxy::class, 't', 'WITH', 'x.town = t.id')
            ->leftJoin(CitizenRankingProxy::class, 'c', 'WITH', 'x.citizen = c.id')
            // Scope to given user
            ->where('x.user = :user')->setParameter('user', $user)
            // Disregard disabled entries
            ->andWhere('x.disabled = 0')
            ->andWhere('(t.disabled = 0 OR t.disabled IS NULL)')
            ->andWhere('(c.disabled = 0 OR c.disabled IS NULL)');

        if (!$ignore_reset) $qb->andWhere('x.reset = 0');
        return $qb;
    }

    /**
     * Returns the heroic xp a user has accumulated
     * @note The return value of this function is cached
     * @param User $user User
     * @param Season|bool|null $season Season; can be an explicit Season object, true (current season), false (no season) or null (any season or no season)
     * @param string|null $subject Restrict to specific subject in addition to season
     * @param bool $include_legacy
     * @param bool $include_deductions
     * @param bool $include_outdated
     * @return int
     * @throws \Exception
     */
    public function getHeroicExperience(User $user, Season|bool|null $season = null, ?string $subject = null, bool $include_legacy = false, bool $include_deductions = true, bool $include_outdated = false): int {

        $season = $season === true
            ? $this->getService(EntityManagerInterface::class)->getRepository(Season::class)->findOneBy(['current' => true])
            : $season;

        $key = match (true) {
            $season === false   => "hxp_{$user->getId()}_ns",
            $season === null    => "hxp_{$user->getId()}_all",
            default             => "hxp_{$user->getId()}_s{$season->getId()}",
        };

        if ($subject !== null) $key .= "_$subject";
        if ($include_legacy) $key .= "_lgc";
        if (!$include_deductions) $key .= '_pos';
        if ($include_outdated) $key .= '_od';

        try {
            $value = $this->gameCachePool->get($key, function (ItemInterface $item) use ($user, $season, $subject, $include_legacy, $include_deductions, $include_outdated) {
                $item->expiresAfter(86400)->tag(["user-{$user->getId()}-hxp",'hxp']);

                $qb = $this->generateDefaultQuery($user, ignore_reset: true)
                    ->select('SUM(x.value)');

                if (!$include_legacy)
                    $qb->andWhere('x.type != :legacy')->setParameter('legacy', HeroXPType::Legacy->value);

                if (!$include_deductions)
                    $qb->andWhere('x.value > 0');

                if (!$include_outdated)
                    $qb->andWhere('x.outdated = false');

                if ($season === false)
                    $qb->andWhere('x.season IS NULL');
                elseif ($season !== null)
                    $qb->andWhere('x.season = :season')->setParameter('season', $season);

                if ($subject !== null)
                    $qb->andWhere('x.subject = :subject')->setParameter('subject', $subject);

                return ($qb->getQuery()->getSingleScalarResult() ?? 0);
            });


            return max(0, $value + ($this->getService(ConfMaster::class)->getGlobalConf()->get(MyHordesSetting::StagingSettingsEnabled)
                ? $this->getService(ConfMaster::class)->getGlobalConf()->get(MyHordesSetting::StagingProtoHxp)
                : 0
            ));
        } catch (InvalidArgumentException $t) {
            return 0;
        }

    }

    /**
     * Returns the amount of resets the user has done
     * @param User $user User
     * @param \DateTimeImmutable|null $end
     * @return int
     */
    public function getResetPackPoints(User $user, ?\DateTimeImmutable &$end = null): int {
        $end = null;
        $logs = $this->generateDefaultQuery($user, true)
            ->select('COUNT(x.id)', 'MIN(x.created)')
            ->andWhere('x.type != :legacy')->setParameter('legacy', HeroXPType::Legacy->value)
            ->andWhere('x.created >= :cutoff')->setParameter('cutoff', new \DateTime('now - 120days'))
            ->andWhere('x.subject = :subject')->setParameter('subject', 'paid_skill_reset')
            ->getQuery()->getOneOrNullResult();

        if ($logs[1] === 0) return 0;
        $end = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $logs[2] )->modify('+ 120days');

        return $logs[1];
    }

    public function getTemporaryPackPoints(User $user, ?\DateTimeImmutable &$end = null): int {
        try {
            /** @var CitizenRankingProxy $latest_citizen */
            $latest_citizen = $this->getService(EntityManagerInterface::class)->getRepository(CitizenRankingProxy::class)->createQueryBuilder('c')
                ->where('c.user = :user')->setParameter('user', $user)
                ->andWhere('c.end IS NOT NULL')
                ->andWhere('c.end >= :cutoff')
                ->setParameter('cutoff', new \DateTime('now - 3days'))
                ->orderBy('c.end', 'ASC')
                ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $t) {
            $latest_citizen = null;
        }

        if (!$latest_citizen) return 0;

        $lms_picto = $this->getService(EntityManagerInterface::class)->getRepository(Picto::class)->createQueryBuilder('p')
            ->where('p.user = :user')->setParameter('user', $user)
            ->andWhere('p.townEntry = :town')->setParameter('town', $latest_citizen->getTown())
            ->andWhere('p.count > 0')
            ->andWhere('p.persisted = 2')
            ->andWhere('p.disabled = 0')
            ->andWhere('p.prototype IN (:lms)')->setParameter('lms',
                $this->getService(EntityManagerInterface::class)->getRepository(PictoPrototype::class)->findBy(['name' => ['r_surlst_#00', 'r_suhard_#00']])
            )
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if ($lms_picto) {
            $end = \DateTimeImmutable::createFromInterface( $latest_citizen->getEnd() )->modify('+3 days');
            return 1;
        } else return 0;
    }

    public function getBasePackPoints(User $user): int {
        $sp = $user->getAllSoulPoints();
        return
            (($sp >= 100) ? 2 : 0) +
            (($sp >= 200) ? 2 : 0);
    }

    /**
     * Returns the amount of pack points a user has
     * @note The return value of this function is partially cached
     * @param User $user User
     * @return int
     * @throws \Exception
     */
    public function getPackPoints(User $user): int {
        return
            $this->getResetPackPoints($user) +
            $this->getTemporaryPackPoints($user) +
            $this->getBasePackPoints($user);
    }

    /**
     * Helper function to query specifically for legacy hero xp (hero days spent)
     * @note Calls getHeroicExperience internally
     * @param User $user User
     * @param bool|null $imported True to query for imported xp, false for MH xp or null for both
     * @return int
     * @throws \Exception
     * @see getHeroicExperience
     */
    public function getLegacyHeroDaysSpent(User $user, ?bool $imported = null): int {
        return match ($imported) {
            true => $this->getHeroicExperience( $user, false, 'legacy_heroDays_imported', include_legacy: true ),
            false => $this->getHeroicExperience( $user, false, 'legacy_heroDays', include_legacy: true ) + $this->getHeroicExperience( $user, false, 'legacy_heroDays_bonus', include_legacy: true ),
            null => $this->getLegacyHeroDaysSpent($user, true) + $this->getLegacyHeroDaysSpent($user, false)
        };
    }

    /**
     * Helper function to set legacy hero xp (hero days spent). Will create a new entry or update an existing one
     * @param User $user User
     * @param bool|null $imported True to set imported hero days, false for MH hero days and null for bonus hero days
     * @param bool $cumulative If set to true, the given points will be added to the current ones instead of replacing them
     * @throws \Exception
     */
    public function setLegacyHeroDaysSpent(User $user, bool|null $imported, int $value, bool $cumulative = false): void {
        $subject = match ($imported) {
            true => 'legacy_heroDays_imported',
            false => 'legacy_heroDays',
            null => 'legacy_heroDays_bonus'
        };

        $existing = $this->getService(EntityManagerInterface::class)->getRepository(HeroExperienceEntry::class)
            ->findOneBy(['user' => $user, 'subject' => $subject]);
        if ($existing) $this->getService(EntityManagerInterface::class)->persist($existing->setValue($cumulative ? ($value + $existing->getValue()) : $value ));
        else $this->recordHeroicExperience( $user, HeroXPType::Legacy, $value, subject: $subject );

        ($this->getService(InvalidateTagsInAllPoolsAction::class))("user-{$user->getId()}-hxp");
    }

    public function hasRecordedHeroicExperienceFor(
        User $user,
        LogEntryTemplate|string|null $template = null,
        ?string $subject = null,
        Season|true $season = null,
        ?int &$total = null,
    ): bool {
        if ($season === true) $season = $this->getService(EntityManagerInterface::class)->getRepository(Season::class)->findOneBy(['current' => true]);
        if (is_string( $template )) {
            $template = $this->getService(EntityManagerInterface::class)
                ->getRepository(LogEntryTemplate::class)
                ->findOneBy(['name' => $template]);
            if (!$template) throw new \Exception("HXP template '{$template}' not found.");
        }

        $qb = $this->generateDefaultQuery($user)
            ->select('COUNT(x.id)', 'SUM(x.value)')
            ->andWhere('x.season = :season')->setParameter('season', $season)
            ->andWhere('x.reset = 0');

        if ($template !== null)
            $qb->andWhere('x.logEntryTemplate = :template')->setParameter('template', $template);
        if ($subject !== null)
            $qb->andWhere('x.subject = :subject')->setParameter('subject', $subject);

        $data = $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        $total = (int)($data[2] ?? 0);
        return $data[1] > 0;
    }

    /**
     * @throws \Exception
     */
    public function recordHeroicExperience(
        User $user,
        HeroXPType $type,
        int $value,
        LogEntryTemplate|string|null $template = null,
        ?string $subject = null,
        string|array $variables = [],
        Town|TownRankingProxy|null $town = null,
        Citizen|CitizenRankingProxy|null $citizen = null,
        Season|true|null $season = null,
        ?\DateTimeInterface $date = null,
    ): bool {

        if ($value === 0) return false;

        if (is_a($town, Town::class)) $town = $town->getRankingEntry();
        if (is_a($citizen, Citizen::class)) $citizen = $citizen->getRankingEntry();

        if ($season === true) $season = $this->getService(EntityManagerInterface::class)->getRepository(Season::class)->findOneBy(['current' => true]);
        $season ??= $town?->getSeason() ?? $citizen?->getTown()?->getSeason();

        if ( $type === HeroXPType::Seasonal && (!$season || !$subject) )
            throw new \Exception('Cannot record seasonal HXP without a season or subject.');

        if (is_string( $template )) {
            $template = $this->getService(EntityManagerInterface::class)
                ->getRepository(LogEntryTemplate::class)
                ->findOneBy(['name' => $template]);
            if (!$template) throw new \Exception("HXP template '{$template}' not found.");
        }

        if (is_string($variables)) $variables = ['message' => $variables];

        if ( $type === HeroXPType::Seasonal ) {
            $prev = $this->generateDefaultQuery($user)
                ->select('COUNT(x.id)')
                ->andWhere('x.season = :season')->setParameter('season', $season)
                ->andWhere('x.type = :type')->setParameter('type', $type)
                ->andWhere('x.subject = :subject')->setParameter('subject', $subject)
                ->andWhere('x.reset = 0')
                ->getQuery()->getSingleScalarResult();

            if ($prev > 0) return false;
        }

        $outdated = $season && ($season->getNumber() < $this->getService(ConfMaster::class)->getGlobalConf()->get(MyHordesSetting::HxpFirstSeason));
        $this->getService(EntityManagerInterface::class)
            ->persist( (new HeroExperienceEntry())
                ->setUser($user)
                ->setSeason($season)
                ->setTown($town)
                ->setCitizen($citizen)
                ->setType($type)
                ->setValue($value)
                ->setSubject($subject)
                ->setLogEntryTemplate($template)
                ->setVariables($variables)
                ->setOutdated($outdated)
                ->setCreated($date ?? new \DateTime())
            );

        ($this->getService(InvalidateTagsInAllPoolsAction::class))("user-{$user->getId()}-hxp");
        return true;
    }

    /**
     * @param User $user
     * @return array<HeroSkillPrototype>
     * @throws \Exception
     */
    public function getUnlockedLegacyHeroicPowersByUser(User $user): array {
        // Get skills unlocked by default
        $xp = $this->getLegacyHeroDaysSpent( $user );
        return $this->getService(EntityManagerInterface::class)->getRepository(HeroSkillPrototype::class)->matching(
            (new Criteria())
                ->andWhere( new Comparison( 'enabled', Comparison::EQ, true )  )
                ->andWhere( new Comparison( 'legacy', Comparison::EQ, true )  )
                ->andWhere( new Comparison( 'daysNeeded', Comparison::LTE, $xp )  )
                ->orderBy([ 'daysNeeded' => Order::Ascending ])
        )->toArray();
    }

    /**
     * @param User $user
     * @return array<HeroSkillPrototype>
     * @throws \Exception
     */
    public function getUnlockableLegacyHeroicPowersByUser(User $user): array {
        // Get skills unlocked by default
        $xp = $this->getLegacyHeroDaysSpent( $user );
        return $this->getService(EntityManagerInterface::class)->getRepository(HeroSkillPrototype::class)->matching(
            (new Criteria())
                ->andWhere( new Comparison( 'enabled', Comparison::EQ, true )  )
                ->andWhere( new Comparison( 'legacy', Comparison::EQ, true )  )
                ->andWhere( new Comparison( 'daysNeeded', Comparison::GT, $xp )  )
                ->orderBy([ 'daysNeeded' => Order::Ascending ])
        )->toArray();
    }

    /**
     * Returns all heroic skills unlocked by a particular user. This includes skills that are
     * unlocked by default.
     * @param User $user The user to check
     * @return array<HeroSkillPrototype>
     */
    public function getUnlockedHeroicSkillsByUser(User $user): array {

        // Get skills unlocked by default
        $defaultSkills = $this->getService(EntityManagerInterface::class)->getRepository(HeroSkillPrototype::class)->matching(
            (new Criteria())
                ->andWhere( new Comparison( 'enabled', Comparison::EQ, true )  )
                ->andWhere( new Comparison( 'legacy', Comparison::EQ, false )  )
                ->andWhere( new Comparison( 'daysNeeded', Comparison::EQ, 0 )  )
        );

        // Unlocked skills
        $unlockedSkills = $this->getService(EntityManagerInterface::class)->getRepository(HeroSkillUnlock::class)->matching(
            (new Criteria())
                ->andWhere( new Comparison( 'user', Comparison::EQ, $user )  )
        )->map( fn(HeroSkillUnlock $skill) => $skill->getSkill() );

        $mergedCollection = new ArrayCollection( $defaultSkills->toArray() );
        foreach ($unlockedSkills as $skill)
            if (!$mergedCollection->contains($skill))
                $mergedCollection->add($skill);

        $list = $mergedCollection->toArray();
        usort($list, fn(HeroSkillPrototype $a, HeroSkillPrototype $b) =>
            $a->getSort() <=> $b->getSort() ?: $a->getLevel() <=> $b->getLevel() ?: $a->getId() <=> $b->getId()
        );
        return $list;
    }

    /**
     * Returns heroic skills that are not yet unlocked by the given user. If $limitToCurrent is set,
     * only those that can be unlocked right now based on their level are returned.
     * @param User $user The user to check
     * @param bool $limitToCurrent Only return skills that can be unlocked right now
     * @return array<HeroSkillPrototype>
     */
    public function getUnlockableHeroicSkillsByUser(User $user, bool $limitToCurrent = true): array {
        $unlockedSkills = $this->getUnlockedHeroicSkillsByUser($user);

        // Calculate the current unlock level by skill group
        $g = [];
        foreach ($unlockedSkills as $skill)
            Arr::set( $g, $skill->getGroupIdentifier(), max( $skill->getLevel(), Arr::get( $g, $skill->getGroupIdentifier(), 0 ) ) );

        $lockedSkills = $this->getService(EntityManagerInterface::class)->getRepository(HeroSkillPrototype::class)->matching(
            (new Criteria())
                ->andWhere( new Comparison( 'enabled', Comparison::EQ, true )  )
                ->andWhere( new Comparison( 'legacy', Comparison::EQ, false )  )
                ->andWhere( new Comparison( 'id', Comparison::NIN, array_map( fn(HeroSkillPrototype $skill) => $skill->getId(), $unlockedSkills )  ) )
        );

        if ($limitToCurrent) $lockedSkills = $lockedSkills->filter( fn(HeroSkillPrototype $skill) => $skill->getLevel() === 1+Arr::get( $g, $skill->getGroupIdentifier(), 0 ) );

        $a = $lockedSkills->toArray();
        usort($a, fn(HeroSkillPrototype $a, HeroSkillPrototype $b) =>
            $a->getDaysNeeded() <=> $b->getDaysNeeded() ?:
            ($a->getLevel() - Arr::get( $g, $a->getGroupIdentifier(), 0 )) <=> ($b->getLevel() - Arr::get( $g, $b->getGroupIdentifier(), 0 )) ?:
            $a->getSort() <=> $b->getSort()
        );

        return $a;
    }

    public function unlockSkillForUser(User $user, HeroSkillPrototype $skill, Season|true $season): bool {
        if ($season === true)
            $season = $this->getService(EntityManagerInterface::class)->getRepository(Season::class)->findOneBy(['current' => true]);

        if ($skill->isLegacy()) return false;
        try {
            $this->getService(EntityManagerInterface::class)->persist( (new HeroSkillUnlock())
                ->setSkill($skill)
                ->setUser($user)
                ->setSeason($season)
            );
            $this->getService(EntityManagerInterface::class)->flush();
        } catch ( \Exception $e ) {
            return false;
        }

        return true;
    }

    public function performSkillResetForUser(User $user, Season|true $season, ?array $skill_ids = null): bool {
        $em = $this->getService(EntityManagerInterface::class);

        if ($season === true)
            $season = $this->getService(EntityManagerInterface::class)->getRepository(Season::class)->findOneBy(['current' => true]);

        $entryCriteria = (new Criteria())
            ->andWhere( new Comparison( 'user', Comparison::EQ, $user ) )
            ->andWhere( new Comparison( 'season', Comparison::EQ, $season ) );

        $unlockCriteria = (new Criteria())
            ->andWhere( new Comparison( 'user', Comparison::EQ, $user ) );
        if ($skill_ids !== null)
            $unlockCriteria->andWhere( new Comparison( 'skill', Comparison::IN, $skill_ids ) );

        /** @var Collection<HeroExperienceEntry> $allEntries */
        $allEntries = $em->getRepository(HeroExperienceEntry::class)->matching($entryCriteria);

        /** @var Collection<HeroSkillUnlock> $unlocks */
        $unlocks = $em->getRepository(HeroSkillUnlock::class)->matching($unlockCriteria);

        foreach ($allEntries as $entry)
            $em->persist( $entry->setReset( $entry->getReset() + 1 ) );
        foreach ($unlocks as $unlock)
            $em->remove($unlock);

        $em->flush();
        ($this->getService(InvalidateTagsInAllPoolsAction::class))("user-{$user->getId()}-hxp");

        return true;
    }
}