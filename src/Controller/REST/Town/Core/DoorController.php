<?php

namespace App\Controller\REST\Town\Core;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\CustomAbstractCoreController;
use App\Controller\Town\TownController;
use App\Entity\BuildingPrototype;
use App\Entity\HomeIntrusion;
use App\Entity\Zone;
use App\Enum\EventStages\BuildingValueQuery;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\GameEventService;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Traits\Controller\ActiveCitizen;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/rest/v1/town/core/door', name: 'rest_town_core_door_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
#[Semaphore('town', scope: 'town')]
class DoorController extends CustomAbstractCoreController
{
    use ActiveCitizen;

    /**
     * @param EventProxyService $event
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param TownHandler $townHandler
     * @param CitizenHandler $citizenHandler
     * @param LogTemplateHandler $log
     * @return JsonResponse
     */
    #[Route(path: '', name: 'control', methods: ['PATCH'])]
    public function control(
        EventProxyService $event,
        JSONRequestParser $parser,
        EntityManagerInterface $em,
        TownHandler $townHandler,
        CitizenHandler $citizenHandler,
        LogTemplateHandler $log,
        GameEventService $gameEvents
    ): JsonResponse {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableBanished );

        if (!($action = $parser->get('action', null, ['open','close'])))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($action === 'close' && $town->getDevastated())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if ($action === 'open'  && $town->getDoor())
            return AjaxResponse::error( TownController::ErrorDoorAlreadyOpen );
        if ($action === 'open' && $town->getLockdown()) {
            $this->addFlash('error', $this->translator->trans('Das Stadttor kann während eines Lockdowns nicht geöffnet werden!', [], 'game'));
            return AjaxResponse::success();
        }
        if ($action === 'open' && $town->getQuarantine()) {
            $this->addFlash('error', $this->translator->trans('Das Stadttor kann während einer Quarantäne nicht geöffnet werden!', [], 'game'));
            return AjaxResponse::success();
        }
        if ($action === 'open'  && ($b = $townHandler->door_is_locked( $town ))) {
            if ($b === true) {
                $this->addFlash('error', $this->translator->trans('Es ist unmöglich, das Stadttor zu einer Privatstadt zu öffnen, solange es *weniger als {num} eingeschriebene Bürger* gibt.', [ 'num' => $town->getPopulation() ], 'game'));
                return AjaxResponse::success();
            } elseif (is_a( $b, BuildingPrototype::class )) {
                if ($b->getName() === 'small_door_closed_#01') {
                    $this->addFlash('error', $this->translator->trans('Der <strong>Kolbenschließmechanismus</strong> hat das Stadttor für heute Nacht sicher verriegelt...', [], 'game'));
                    return AjaxResponse::success();
                } else {
                    $this->addFlash('error', $this->translator->trans('Der <strong>Stadttorriegel</strong> ist eingerastet und das Tor ist zu. Im Moment geht da gar nichts mehr!', [], 'game'));
                    return AjaxResponse::success();
                }
            } else return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }
        if ($action === 'close' && !$town->getDoor())
            return AjaxResponse::error( TownController::ErrorDoorAlreadyClosed );

        if ($citizen->hasStatus('wound3'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableWounded );

        $door_interaction_ap = $event->queryTownParameter( $this->getActiveCitizen()->getTown(), $this->getActiveCitizen()->getTown()->getDoor()
            ? BuildingValueQuery::TownDoorClosingCost
            : BuildingValueQuery::TownDoorOpeningCost
        );

        if ($door_interaction_ap > 0 && ($citizen->getAp() < $door_interaction_ap || $citizen->hasStatus('tired')))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $result = $gameEvents->triggerDoorResponseHooks( $town, $this->conf->getCurrentEvents($town), $action );
        if ($result) return $result;

        if ($door_interaction_ap > 0)
            $citizenHandler->setAP($citizen, true, -$door_interaction_ap);

        $town->setDoor( $action === 'open' );

        $em->persist( $log->doorControl( $citizen, $action === 'open' ) );

        try {
            $em->persist($citizen);
            $em->persist($town);
            $em->flush();
        } catch (\Throwable $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @param LogTemplateHandler $log
     * @param string $special
     * @return JsonResponse
     */
    #[Route(path: '/exit/{special}', name: 'exit', methods: ['POST'])]
    public function exit(
        EntityManagerInterface $em,
        LogTemplateHandler $log,
        string $special = 'normal',
    ): JsonResponse {
        $citizen = $this->getActiveCitizen();
        switch ($special) {
            case 'normal':
                if (!$citizen->getTown()->getDoor())
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            case 'sneak':
                if (!$citizen->getTown()->getDoor() || !$citizen->hasRole('ghoul'))
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            case 'hero':
                if (!$citizen->getProfession()->getHeroic())
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $zone = $em->getRepository(Zone::class)->findOneByPosition($citizen->getTown(), 0, 0);

        if (!$zone)
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        if ($special !== 'sneak')
            $em->persist( $log->doorPass( $citizen, false ) );

        $zone->addCitizen( $citizen );

        foreach ($em->getRepository(HomeIntrusion::class)->findBy(['intruder' => $citizen]) as $homeIntrusion)
            $em->remove($homeIntrusion);

        try {
            $em->persist($citizen);
            $em->flush();
        } catch (\Throwable $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}
