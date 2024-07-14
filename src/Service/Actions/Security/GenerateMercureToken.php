<?php

namespace App\Service\Actions\Security;

use App\Entity\User;
use Lcobucci\JWT\Token\RegisteredClaims;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class GenerateMercureToken
{
    public function __construct(
        private Security $security,
        private TagAwareCacheInterface $gameCachePool,
        private HubRegistry $registry
    ) { }

    private static function format(?string $path, ?User $user = null, ?string $token = null, int $priority = 0): array {
        return [
            'm' => $path ?? '#',
            'u' => ($user && $token) ? $user->getId() : null,
            'p' => ($user && $token) ? $priority : 0,
            't' => ($user && $token) ? $token : null
        ];
    }

    public function __invoke(string|array|null $topics = null, int $expiration = 7200): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();
        $path = $this->registry->getHub()?->getPublicUrl();

        $topics ??= [
            "myhordes://live/concerns/{$user->getId()}",
            "myhordes://live/concerns/authorized",
        ];
        if (!is_array($topics)) $topics = [$topics];

        $key = str_replace(['{','}','(',')','/','\\','@',':'], '', $topics[0]);

        return $user ? $this->gameCachePool->get("mh_mercure_{$user->getId()}_{$user->getCheckInt()}_$key", function (ItemInterface $item) use ($user, $path, $expiration, $topics) {
            $expires = new \DateTimeImmutable("+{$expiration}seconds");
            $t = $this->registry->getHub()?->getFactory()?->create(
                subscribe: $topics,
                additionalClaims: [
                    RegisteredClaims::EXPIRATION_TIME => $expires,
                    RegisteredClaims::ISSUED_AT => new \DateTimeImmutable()
                ],
            );

            $item->expiresAfter($t ? (int)round($expiration / 4) : 1)->tag(['mercure',"mercure_{$user->getId()}"]);

            return self::format( $path, $user, $t, $expires->getTimestamp() );
        }) : self::format($path);
    }
}