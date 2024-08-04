<?php

namespace App\Service\User;

use App\Entity\Activity;
use App\Entity\OfficialGroup;
use App\Entity\User;
use App\EventListener\ContainerTypeTrait;
use App\Service\PermissionHandler;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class UserAccountService implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
        ];
    }

    /**
     * @param User $user
     * @return array<string>
     */
    public function getKnownIPsForUser(User $user): array {
        return $this->getService(EntityManagerInterface::class)
            ->getRepository(Activity::class)->createQueryBuilder('a')
            ->select('a.ip')->distinct()
            ->where('a.user = :user')->setParameter(':user', $user)
            ->getQuery()->getSingleColumnResult();
    }

}