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

    public function __invoke(): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();
        $path = $this->registry->getHub()?->getPublicUrl();

        return $user ? $this->gameCachePool->get("mh_mercure_{$user->getId()}_{$user->getCheckInt()}", function (ItemInterface $item) use ($user, $path) {
            $expires = new \DateTimeImmutable("+2hours");
            $t = $this->registry->getHub()?->getFactory()?->create(
                subscribe: [
                    "myhordes://live/concerns/{$user->getId()}",
                    "myhordes://live/concerns/authorized",
                ],
                additionalClaims: [
                    RegisteredClaims::EXPIRATION_TIME => $expires,
                    RegisteredClaims::ISSUED_AT => new \DateTimeImmutable()
                ],
            );

            $item->expiresAfter($t ? 3600 : 1)->tag(['mercure',"mercure_{$user->getId()}"]);

            return self::format( $path, $user, $t, 1 );
        }) : self::format($path);
    }
}