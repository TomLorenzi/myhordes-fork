<?php

namespace App\Service\Actions\User;

use App\Entity\NotificationSubscription;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\PictoRollup;
use App\Entity\Season;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\UserSetting;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class GetAudienceAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $gameCachePool,
    ) { }

    /**
     * @param UserSetting $setting
     * @param mixed $value
     * @param string|null $language
     * @return int[]
     */
    private function collect(UserSetting $setting, mixed $value, ?string $language = null): array {
        $user_ids = $this->em->getRepository(NotificationSubscription::class)->createQueryBuilder('n')
            ->select('IDENTITY(n.user) as user_id')->distinct()
            ->andWhere('n.expired = false')
            ->getQuery()->getSingleColumnResult();

        return array_map( fn(User $u) => $u->getId(), array_filter(
            $this->em->getRepository(User::class)->findBy(
                [
                    'id' => $user_ids,
                    ...($language !== null ? ['language' => $language] : []),
                ]
            ),
            fn(User $u) => $u->getSetting( $setting ) === $value
        ));
    }


    /**
     * Returns all user IDs that have notification subscriptions and conform to the given setting and language. By
     * default, the list of IDs returned by this function is cached for up to 24 hours.
     * @param UserSetting $setting Setting to check
     * @param mixed $value Expected setting value. Defaults to true
     * @param string|null $language Restrict users to the given language
     * @param bool $disable_cache Disable the cache for this call (will neither read nor write to the cache)
     * @return int[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __invoke(UserSetting $setting, mixed $value = true, ?string $language = null, bool $disable_cache = false): array
    {
        $kv = is_array($value) ? json_encode($value) : $value;
        $key = "{$setting->name}_{$kv}_{$language}";
        return $disable_cache ? $this->collect($setting, $value, $language) : $this->gameCachePool->get( $key, function (ItemInterface $item) use ($setting, $value, $language) {
            $item->expiresAfter(86400)->tag(['audience']);
            return $this->collect($setting, $value, $language);
        } );
    }
}