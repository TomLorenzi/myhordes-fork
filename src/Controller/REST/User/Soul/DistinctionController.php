<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\PictoRollup;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Interfaces\Entity\PictoRollupInterface;
use App\Service\JSONRequestParser;
use App\Service\User\PictoService;
use App\Service\User\UserCapabilityService;
use App\Service\UserHandler;
use App\Structures\Entity\PictoRollupStructure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/user/soul/distinctions', name: 'rest_user_soul_distinctions_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class DistinctionController extends CustomAbstractCoreController
{

    /**
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[Route(path: '/index', name: 'base_index', methods: ['GET'])]
    public function index(Packages $assets): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [
                    'header' => $this->translator->trans('Statistiken', [], 'global'),
                    'points' => $this->translator->trans('{points} Punkte', [], 'global'),
                    'tab_picto' => $this->translator->trans('Statistiken', [], 'soul'),
                    'tab_award' => $this->translator->trans('Errungenschaften', [], 'soul'),
                ],
                'pictos' => [
                    'empty' => $this->translator->trans('Dieser Spieler hat bisher noch keine Preise gewonnen...', [], 'global'),
                ],
                'awards' => [
                    'unique' => $this->translator->trans('Einzigartige Errungenschaften', [], 'game'),
                    'unique_desc' => $this->translator->trans('Dies ist eine einzigartige Errungenschaft, die im Rahmen eines Events verliehen wurde.', [], 'game'),
                    'unique_url' => $assets->getUrl( 'build/images/icons/icon_mh_team.gif' ),
                    'single' => $this->translator->trans('Besondere Errungenschaften', [], 'game'),
                    'single_desc' => $this->translator->trans('Dies ist eine besondere Errungenschaft, die im Rahmen eines Events verliehen wurde.', [], 'game'),
                ]
            ]
        ]);
    }


    /**
     * @param User $user
     * @param string $source
     * @param UserCapabilityService $capabilityService
     * @param EntityManagerInterface $em
     * @param PictoService $pictoService
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '/{id}/{source<old|soul|mh|imported|all>}', name: 'list', methods: ['GET'])]
    public function list(
        User $user,
        string $source,
        UserCapabilityService $capabilityService,
        EntityManagerInterface $em,
        PictoService $pictoService,
        Packages $assets
    ): JsonResponse {

        $data = match ($source) {
            'soul'      => $pictoService->accumulateAllPictos( $user, include_imported: true ),
            'mh'        => $pictoService->accumulateAllPictos( $user ),
            'imported'  => $pictoService->getPictoGroup( $user, imported: true ),
            'old'       => $pictoService->getPictoGroup( $user, old: true ),
            'all'       => $capabilityService->hasRole( $this->getUser(), 'ROLE_CROW' ) ? $pictoService->accumulateAllPictos($user, include_imported: true, include_old: true) : [],
            default => []
        };

        $comments = match ($source) {
            'imported', 'old'   => [],
            'soul', 'mh', 'all' => $pictoService->accumulateAllPictoComments( $user ),
            default => []
        };

        $picto_db = array_column( array_map(fn(PictoRollupInterface $p) => [
            'id' => $p->getPrototype()->getId(),
            'label' => $this->translator->trans( $p->getPrototype()->getLabel(), [], 'game' ),
            'description' => $this->translator->trans( $p->getPrototype()->getDescription(), [], 'game' ),
            'comments' => $comments[$p->getPrototype()->getId()] ?? [],
            'icon' => $assets->getUrl( "build/images/pictos/{$p->getPrototype()->getIcon()}.gif" ),
            'rare' => $p->getPrototype()->getRare(),
            'count' => $p->getCount()
        ], $data), null, 'id');

        $award_db = match ($source) {
            'soul' => $user->getAwards()->filter(fn(Award $a) => $a->getCustomTitle() || $a->getPrototype()?->getTitle())->map(fn(Award $a) => [
                'id' => $a->getPrototype()?->getId() ?? 0,
                'label' => $a->getCustomTitle() ?? $this->translator->trans( $a->getPrototype()?->getTitle() ?? '', [], 'game' ),
                'picto' => $a->getPrototype()?->getAssociatedPicto() ? [
                    'id' => $a->getPrototype()->getAssociatedPicto()->getId(),
                    'count' => $a->getPrototype()->getUnlockQuantity()
                ] : null,
            ])->toArray(),
            default => null
        };

        foreach ( $award_db ?? [] as $item )
            if ($item['picto'] && !isset( $picto_db[ $item['picto']['id'] ] )) {
                $picto = $em->getRepository(PictoPrototype::class)->find( $item['picto']['id'] );
                $picto_db[ $item['picto']['id'] ] = [
                    'id' => (int)$item['picto']['id'],
                    'label' => $this->translator->trans( $picto?->getLabel() ?? '', [], 'game' ),
                    'description' => $this->translator->trans( $picto?->getDescription() ?? '', [], 'game' ),
                    'icon' => $assets->getUrl( "build/images/pictos/{$picto?->getIcon()}.gif" ),
                    'rare' => !!$picto?->getRare(),
                    'count' => 0
                ];
            }

        $top3 = null;
        if ($source === 'soul') {
            $natural_top3 = array_slice( array_keys( $picto_db ), 0, 3);
            while(count($natural_top3) < 3) $natural_top3[] = null;

            $user_top3 = array_values(array_slice($user->getSetting( UserSetting::DistinctionTop3 ), 0, 3));
            while(count($user_top3) < 3) $user_top3[] = null;

            $top3 = array_map( fn($u,$n) => (($picto_db[$u] ?? null) ? $u : null) ?? $n ?? null, $user_top3, $natural_top3 );
        }

        return new JsonResponse([
            'points' => $pictoService->getPointsFromPictoRollupSet( $data ),
            'pictos' => array_values($picto_db),
            'awards' => array_values($award_db ?? []),
            'top3' => $top3
        ]);

    }

    /**
     * @param User $user
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    #[Route(path: '/{id}/top3', name: 'patch_top3', methods: ['PATCH'])]
    public function patchTop3(
        User $user,
        UserHandler $userHandler,
        EntityManagerInterface $em,
        JSONRequestParser $parser
    ): JsonResponse {
        if ($user !== $this->getUser()) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $data = array_map(
            fn($c) => $em->getRepository(PictoPrototype::class)->find($c ?? -1)?->getId() ?? null,
            array_unique(array_values(array_slice($parser->get_array('data', [null,null,null]), 0, 3)))
        );

        for ($i = 0; $i < 3; $i++) $data[$i] ??= null;
        ksort($data);

        $em->persist( $user->setSetting( UserSetting::DistinctionTop3, $data ) );
        $em->flush();

        return new JsonResponse(['updated' => $data]);
    }
}
