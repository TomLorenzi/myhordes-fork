<?php

namespace App\Service\User;

use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\HeroExperienceEntry;
use App\Entity\LogEntryTemplate;
use App\Entity\OfficialGroup;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\HeroXPType;
use App\EventListener\ContainerTypeTrait;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\PermissionHandler;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
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
            InvalidateTagsInAllPoolsAction::class
        ];
    }

    /**
     * Generates default query builder to query for hxp entries
     * @param User $user
     * @return QueryBuilder
     */
    private function generateDefaultQuery(User $user): QueryBuilder {
        return $this->getService(EntityManagerInterface::class)->createQueryBuilder()
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
    }

    /**
     * Returns the heroic xp a user has accumulated
     * @note The return value of this function is cached
     * @param User $user User
     * @param Season|bool|null $season Season; can be an explicit Season object, true (current season), false (no season) or null (any season or no season)
     * @param string|null $subject Restrict to specific subject in addition to season
     * @return int
     */
    public function getHeroicExperience(User $user, Season|bool|null $season = true, ?string $subject = null, bool $include_legacy = false): int {

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

        try {
            return $this->gameCachePool->get($key, function (ItemInterface $item) use ($user, $season, $subject, $include_legacy) {
                $item->expiresAfter(86400)->tag(["user-{$user->getId()}-hxp",'hxp']);

                $qb = $this->generateDefaultQuery($user)
                    ->select('SUM(x.value)');

                if (!$include_legacy)
                    $qb->andWhere('x.type != :legacy')->setParameter('legacy', HeroXPType::Legacy);

                if ($season === false)
                    $qb->andWhere('x.season IS NULL');
                elseif ($season !== null)
                    $qb->andWhere('x.season = :season')->setParameter('season', $season);

                if ($subject !== null)
                    $qb->andWhere('x.subject = :subject')->setParameter('subject', $subject);

                return $qb->getQuery()->getSingleScalarResult() ?? 0;
            });
        } catch (InvalidArgumentException $t) {
            return 0;
        }

    }

    /**
     * Helper function to query specifically for legacy hero xp (hero days spent)
     * @note Calls getHeroicExperience internally
     * @param User $user User
     * @param bool|null $imported True to query for imported xp, false for MH xp or null for both
     * @see getHeroicExperience
     * @return int
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
        ?Season $season = null,
        ?\DateTimeInterface $date = null,
    ): bool {

        if ($value === 0) return false;

        if (is_a($town, Town::class)) $town = $town->getRankingEntry();
        if (is_a($citizen, Citizen::class)) $citizen = $citizen->getRankingEntry();

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
                ->getQuery()->getSingleScalarResult();

            if ($prev > 0) return false;
        }

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
                ->setCreated($date ?? new \DateTime())
            );

        ($this->getService(InvalidateTagsInAllPoolsAction::class))("user-{$user->getId()}-hxp");
        return true;
    }

}