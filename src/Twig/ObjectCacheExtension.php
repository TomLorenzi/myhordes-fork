<?php


namespace App\Twig;


use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\Hook;
use App\Entity\Item;
use App\Entity\Town;
use App\Entity\TownSlotReservation;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ObjectCacheExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TagAwareCacheInterface $gameCachePool,
        private readonly RouterInterface        $router,
        private readonly Packages               $asset,
        private readonly EventProxyService      $events,
    ) { }

    public function getFilters(): array
    {
        return [
            new TwigFilter('avatar', [$this, 'get_cached_avatar']),
            new TwigFilter('itemCacheKey', [$this, 'get_item_cache_key']),
        ];
    }

    public function getFunctions(): array
    {
        return [];
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function get_cached_avatar(int|User $user, bool $small = false): string {
        $id = is_int($user) ? $user : $user->getId();
        $flag = $small ? 's' : 'q';
        $key = "twig_cache_user_avatar_{$id}_{$flag}";
        return $this->gameCachePool->get($key, function (ItemInterface $item) use ($small, $id, $user): string {
            $item->expiresAfter(604800)->tag(['user_avatars', "user_avatar_$id"]);

            $avatar = is_int($user)
                ? $this->em->getRepository(User::class)->find($user)?->getAvatar()
                : $user->getAvatar();

            // path('app_web_avatar', {'uid': user.id, 'name': small ? user.avatar.smallName : user.avatar.filename, 'ext': user.avatar.format})

            return $avatar
                ? $this->router->generate( 'app_web_avatar', [
                    'uid' => $id,
                    'name' => $small ? $avatar->getSmallName() : $avatar->getFilename(),
                    'ext' => $avatar->getFormat()
                ] )
                : $this->asset->getUrl('build/images/forum/empty_avatar.gif');

        }/*, INF*/);
    }

    public function get_item_cache_key(Item|ItemPrototype $item, int $count = 1, bool $devMode = false): string {
        if (is_a($item, ItemPrototype::class)) return "item_prototype_{$item->getId()}_plain";
        else {
            $key = "item_prototype_{$item->getPrototype()->getId()}_instance_$count";
            if ($devMode) $key .= "_i{$item->getId()}";
            if ($item->getBroken()) $key .= "_b";
            if ($item->getEssential()) $key .= "_e";
            if ($item->getHidden()) $key .= "_h";
            if ($item->getFirstPick()) $key .= "_f";
            $key .= "_p{$item->getPoison()->value}";
            if ($item->getPrototype()->getWatchpoint() <> 0 && ($t = $item->getInventory()->findTown()))
                $key .= "_w{$this->events->buildingQueryNightwatchDefenseBonus( $t, $item )}";

            return $key;
        }
    }
}
