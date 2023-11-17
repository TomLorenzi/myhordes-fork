<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Toaster;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Citizen;
use App\Entity\Town;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Service\Actions\Game\RenderMapAction;
use App\Service\Actions\Game\RenderMapRouteAction;
use App\Service\CitizenHandler;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


#[Route(path: '/rest/v1/game/map', name: 'rest_game_map_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class MapController extends CustomAbstractCoreController
{

    /**
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[Route(path: '/index', name: 'base_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse {
        $all_tags = [];
        $last = 0;
        foreach ($em->getRepository(ZoneTag::class)->findBy([], ['ref' => 'ASC']) as $i => $tag) {
            for (;$last <= $i-1;$last++) $all_tags[] = '';
            $all_tags[] = $tag->getRef() !== ZoneTag::TagNone ? $this->translator->trans( $tag->getLabel(), [], 'game' ) : '';
            $last++;
        }

        return new JsonResponse([
            'zone' => $this->translator->trans('Zone', [], 'game'),
            'distance' => $this->translator->trans('Entfernung', [], 'game'),
            'distanceSelf' => $this->translator->trans('Entfernung von hier', [], 'game'),
            'distanceTown' => $this->translator->trans('Entfernung zur Stadt', [], 'game'),
            'danger' => [
                $this->translator->trans('Isolierte Zombies', [], 'game'),
                $this->translator->trans('Die Zombies verstümmeln', [], 'game'),
                $this->translator->trans('Horde der Zombies', [], 'game'),
            ],
            'tags' => array_values($all_tags),
            'mark' => $this->translator->trans('Mark.', [], 'game'),
            'global' => $this->translator->trans('Global', [], 'game'),
            'routes' => $this->translator->trans('Routen', [], 'game'),
            'map'    => $this->translator->trans('Karte', [], 'game'),
            'close'  => $this->translator->trans('Schließen', [], 'game'),
            'position' => $this->translator->trans('Position:', [], 'game'),
            'horror' => [
                $this->translator->trans('Psychose', [], 'names'),
                $this->translator->trans('Demenz', [], 'names'),
                $this->translator->trans('Leid', [], 'names'),
                $this->translator->trans('Trinke', [], 'names'),
                $this->translator->trans('Schlaf', [], 'names'),
                $this->translator->trans('Blut', [], 'names'),
                $this->translator->trans('Wut', [], 'names'),
                $this->translator->trans('Hass', [], 'names'),
                $this->translator->trans('Töte', [], 'names'),
                $this->translator->trans('Töte sie', [], 'names'),
                $this->translator->trans('Mord', [], 'names'),
                $this->translator->trans('Drogen', [], 'names'),
                $this->translator->trans('Der Tod wartet', [], 'names'),
                $this->translator->trans('Du wirst sterben', [], 'names'),
                $this->translator->trans('Du wirst heute sterben', [], 'names'),
                $this->translator->trans('Folge den Schatten', [], 'names'),
                $this->translator->trans('Alpträume', [], 'names'),
                $this->translator->trans('Verstümmelung', [], 'names'),
                $this->translator->trans('Keine Hoffnung', [], 'names'),
                $this->translator->trans('Sie beobachten dich', [], 'names'),
                $this->translator->trans('Sie wollen dich töten', [], 'names'),
                $this->translator->trans('Traue niemandem', [], 'names'),
                $this->translator->trans('Lauf lauf weg', [], 'names'),
                $this->translator->trans('Hör nicht auf sie', [], 'names'),
                $this->translator->trans('Sie werden dich töten', [], 'names'),
                $this->translator->trans('Dein Nachbar will deinen Tod', [], 'names'),
                $this->translator->trans('Sie wollen dich hängen', [], 'names'),
                $this->translator->trans('Sie werden dich umbringen', [], 'names'),
                $this->translator->trans('Bleib hier', [], 'names'),
                $this->translator->trans('Koste ihr Blut', [], 'names'),
                $this->translator->trans('Wir beobachten dich', [], 'names'),
                $this->translator->trans('Sie sind VERRÜCKT', [], 'names'),
                $this->translator->trans('Wir wollen deine Haut', [], 'names'),
                $this->translator->trans('Koste ihr Fleisch', [], 'names'),
                $this->translator->trans('Iss ihn', [], 'names'),
                $this->translator->trans('Töte ihn', [], 'names'),
                $this->translator->trans('Lass ihn zahlen', [], 'names'),
                $this->translator->trans('Räche dich', [], 'names'),
                $this->translator->trans('Verrate sie', [], 'names'),
                $this->translator->trans('Ohne Gnade', [], 'names'),
                $this->translator->trans('Ich bin nicht verrückt', [], 'names'),
                $this->translator->trans('Ich sehe nichts', [], 'names'),
                $this->translator->trans('Sie sind wahnsinnig', [], 'names'),
                $this->translator->trans('Bis zum Tod', [], 'names'),
                $this->translator->trans('Töte sie alle', [], 'names'),
                $this->translator->trans('Sie werden sterben', [], 'names'),
                $this->translator->trans('Lass sie sterben', [], 'names'),
                $this->translator->trans('Verschlinge sie', [], 'names'),
                $this->translator->trans('Gib auf', [], 'names'),
                $this->translator->trans('Alles... ist ... OK …', [], 'names')
            ]
        ]);
    }

    /**
     * @param RenderMapAction $renderer
     * @return JsonResponse
     */
    #[Route(path: '/radar/map', name: 'radar', methods: ['GET'])]
    #[GateKeeperProfile(only_alive: true, only_beyond: true)]
    #[Toaster]
    public function radar(RenderMapAction $renderer): JsonResponse {
        return new JsonResponse($renderer( activeCitizen: $this->getUser()->getActiveCitizen() ) );
    }

    /**
     * @param RenderMapRouteAction $renderer
     * @return JsonResponse
     */
    #[Route(path: '/radar/routes', name: 'radar_routes', methods: ['GET'])]
    #[GateKeeperProfile(only_alive: true, only_beyond: true)]
    public function radar_routes(RenderMapRouteAction $renderer): JsonResponse {
        return new JsonResponse($renderer( $this->getUser()->getActiveCitizen()->getTown() ) );
    }

    /**
     * @param RenderMapAction $renderer
     * @return JsonResponse
     */
    #[Route(path: '/satellite/map', name: 'satellite', methods: ['GET'])]
    #[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
    #[Toaster]
    public function satellite(RenderMapAction $renderer): JsonResponse {
        return new JsonResponse($renderer( activeCitizen: $this->getUser()->getActiveCitizen() ) );
    }

    /**
     * @param RenderMapRouteAction $renderer
     * @return JsonResponse
     */
    #[Route(path: '/satellite/routes', name: 'satellite_routes', methods: ['GET'])]
    #[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
    public function satellite_routes(RenderMapRouteAction $renderer): JsonResponse {
        return new JsonResponse($renderer( $this->getUser()->getActiveCitizen()->getTown() ) );
    }

    /**
     * @param Town $town
     * @param RenderMapAction $renderer
     * @return JsonResponse
     */
    #[Route(path: '/admin/{id}/map', name: 'admin', methods: ['GET'])]
    #[IsGranted('ROLE_CROW')]
    public function admin(Town $town, RenderMapAction $renderer): JsonResponse {
        return new JsonResponse($renderer( town: $town, admin: true ) );
    }

    /**
     * @param Town $town
     * @param RenderMapRouteAction $renderer
     * @return JsonResponse
     */
    #[Route(path: '/admin/{id}/routes', name: 'admin_routes', methods: ['GET'])]
    #[IsGranted('ROLE_CROW')]
    public function admin_routes(Town $town, RenderMapRouteAction $renderer): JsonResponse {
        return new JsonResponse($renderer($town));
    }
}
