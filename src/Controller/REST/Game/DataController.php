<?php

namespace App\Controller\REST\Game;

use App\Controller\CustomAbstractCoreController;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Service\ConfMaster;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Shivas\VersioningBundle\Service\VersionManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/rest/v1/game/data', name: 'rest_game_data_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class DataController extends CustomAbstractCoreController
{

    public function __construct(
        ConfMaster $conf,
        TranslatorInterface $translator,
        private readonly Packages $assets,
        private readonly VersionManagerInterface $versionManager,
    )
    {
        parent::__construct($conf, $translator);
    }

    /**
     * @template T
     * @param EntityManagerInterface $em
     * @param class-string<T> $class
     * @param string $ids
     * @return Collection<T>
     */
    private static function resolve(EntityManagerInterface $em, string $class, string $ids): Collection {
        return $em->getRepository($class)->matching(
            (new Criteria( ))->where( Criteria::expr()->in( 'id', array_unique(
                (new ArrayCollection( explode(',',  $ids) ))
                    ->filter( fn(string $s) => !empty($s) && is_numeric($s) )
                    ->map( fn(string $s) => (int)$s )
                    ->toArray()
            ) ) )
        );
    }

    #[Route(path: '/items', name: 'items', methods: ['GET'])]
    public function inventory(EntityManagerInterface $em, Request $request): JsonResponse {
        $fe_props = [
            'is_water',
            'single_use',
            'deco',
            'defence',
            'weapon'
        ];

        return new JsonResponse([
            'v' => $this->versionManager->getVersion(),
            'l' => $this->translator->getLocale(),
            'data' => self::resolve($em, ItemPrototype::class, $request->query->get('ids', ''))->map(fn(ItemPrototype $p) => [
                'id' => $p->getId(),
                'name' => $this->translator->trans( $p->getLabel(), [], 'items' ),
                'desc' => $this->translator->trans( $p->getDescription(), [], 'items' ),
                'icon' => $this->assets->getUrl( "build/images/item/item_{$p->getIcon()}.gif" ),
                'props' => $p->getProperties()->filter( fn(ItemProperty $p) => in_array( $p->getName(), $fe_props ) )->map( fn(ItemProperty $p) => $p->getName() )->getValues(),
                'heavy' => $p->getHeavy(),
                'deco' => $p->getDeco(),
                'watch' => $p->getWatchpoint(),
            ])->toArray()
        ]);
    }
}
