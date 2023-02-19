<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Service\JSONRequestParser;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function App\Controller\REST\User\mb_strlen;
use function App\Controller\REST\User\str_contains;


/**
 * @Route("/rest/v1/user/soul/distinctions", name="rest_user_soul_distinctions_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 */
class DistinctionController extends CustomAbstractCoreController
{

    /**
     * @Route("", name="base", methods={"GET"})
     * @Cache(smaxage="43200", mustRevalidate=false, public=true)
     * @param Packages $assets
     * @return JsonResponse
     */
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
     * @Route("/{id}/{source<old|soul|mh|imported|all>}", name="list", methods={"GET"})
     * @param User $user
     * @param string $source
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @param Packages $assets
     * @return JsonResponse
     */
    public function list(
        User $user,
        string $source,
        UserHandler $userHandler,
        EntityManagerInterface $em,
        Packages $assets
    ): JsonResponse {

        $picto_db = array_column( array_map(fn(array $p) => [
            'id' => (int)$p['id'],
            'label' => $this->translator->trans( $p['label'], [], 'game' ),
            'description' => $this->translator->trans( $p['description'], [], 'game' ),
            'icon' => $assets->getUrl( "build/images/pictos/{$p['icon']}.gif" ),
            'rare' => $p['rare'],
            'count' => (int)$p['c']
        ], match ($source) {
            'soul'      => $em->getRepository(Picto::class)->findNotPendingByUser( $user ),
            'mh'        => $em->getRepository(Picto::class)->findNotPendingByUser( $user, imported: false),
            'imported'  => $em->getRepository(Picto::class)->findNotPendingByUser( $user, imported: true),
            'old'       => $em->getRepository(Picto::class)->findOldByUser( $user ),
            'all'       => $userHandler->hasRole( $this->getUser(), 'ROLE_CROW' ) ? $em->getRepository(Picto::class)->findByUser( $user ) : [],
            default => []
        }), null, 'id');

        $award_db = ($user === $this->getUser() || $userHandler->hasRole( $this->getUser(), 'ROLE_CROW' )) ? match ($source) {
            'soul' => $user->getAwards()->filter(fn(Award $a) => $a->getCustomTitle() || $a->getPrototype()?->getTitle())->map(fn(Award $a) => [
                'id' => $a->getPrototype()?->getId() ?? 0,
                'label' => $a->getCustomTitle() ?? $this->translator->trans( $a->getPrototype()?->getTitle() ?? '', [], 'game' ),
                'picto' => $a->getPrototype()?->getAssociatedPicto() ? [
                    'id' => $a->getPrototype()->getAssociatedPicto()->getId(),
                    'count' => $a->getPrototype()->getUnlockQuantity()
                ] : null,
            ])->toArray(),
            default => null
        } : null;

        foreach ( $award_db ?? [] as $item )
            if ($item['picto'] && !isset( $picto_db[ $item['picto']['id'] ] )) {
                $picto = $em->getRepository(PictoPrototype::class)->find( $item['picto'] );
                $picto_db[ $item['picto'] ] = [
                    'id' => (int)$item['picto'],
                    'label' => $this->translator->trans( $picto?->getLabel() ?? '', [], 'game' ),
                    'description' => $this->translator->trans( $picto?->getDescription() ?? '', [], 'game' ),
                    'icon' => $assets->getUrl( "build/images/pictos/{$picto?->getIcon()}.gif" ),
                    'rare' => !!$picto?->getRare(),
                    'count' => 0
                ];
            }

        return new JsonResponse([
            'points' => match ($source) {
                'soul'      => round($userHandler->getPoints( $user )),
                'mh'        => round($userHandler->getPoints( $user, imported: false )),
                'imported'  => round($userHandler->getPoints( $user, imported: true )),
                'old'       => round($userHandler->getPoints( $user, old: true )),
                default => null
            },
            'pictos' => array_values($picto_db),
            'awards' => array_values($award_db ?? []),
            'top3' => match ($source) {
                'soul' => array_slice( array_keys( $picto_db ), 0, 3),
                default => null
            }
        ]);

    }
}
