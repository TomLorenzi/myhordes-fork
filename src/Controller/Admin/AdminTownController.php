<?php

namespace App\Controller\Admin;

use App\Entity\ExpeditionRoute;
use App\Entity\Town;
use App\Entity\Zone;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\NightlyHandler;
use App\Service\UserFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminTownController extends AdminActionController
{
    /**
     * @Route("jx/admin/town/list", name="admin_town_list")
     * @return Response
     */
    public function town_list(): Response
    {
        return $this->render( 'ajax/admin/towns/list.html.twig', [
            'towns' => $this->entity_manager->getRepository(Town::class)->findAll(),
        ]);      
    }

    /**
     * @Route("jx/admin/town/{id<\d+>}", name="admin_town_explorer")
     * @param int $id
     * @return Response
     */
    public function town_explorer(int $id): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if ($town === null) $this->redirect( $this->generateUrl( 'admin_town_list' ) );

        $explorables = [];

        foreach ($town->getZones() as $zone)
            /** @var Zone $zone */
            if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                $explorables[ $zone->getId() ] = ['rz' => [], 'z' => $zone, 'x' => $zone->getExplorerStats(), 'ax' => $zone->activeExplorerStats()];
                if ($zone->activeExplorerStats()) $explorables[ $zone->getId() ][ 'axt' ] = max(0,$zone->activeExplorerStats()->getTimeout()->getTimestamp() - time());
                $rz = $zone->getRuinZones();
                foreach ($rz as $r)
                    $explorables[ $zone->getId() ]['rz'][] = $r;
            }

        return $this->render( 'ajax/admin/towns/explorer.html.twig', array_merge([
            'town' => $town,
            'conf' => $this->conf->getTownConfiguration( $town ),
            'explorables' => $explorables,
            'log' => $this->renderLog( -1, $town, false, null, null )->getContent(),
            'day' => $town->getDay()
        ], $this->get_map_blob($town)));
    }

    /**
     * @Route("api/admin/town/{id}/do/{action}", name="admin_town_manage", requirements={"id"="\d+"})
     * @param int $id
     * @param string $action
     * @param JSONRequestParser $parser
     * @param UserFactory $uf
     * @return Response
     */
    public function town_manager(int $id, string $action, NightlyHandler $night): Response
    {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (in_array($action, [ 'release', 'quarantine', 'advance' ]) && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        switch ($action) {
            case 'release':
                $town->setAttackFails(0);
                $this->entity_manager->persist($town);
                break;
            case 'quarantine':
                $town->setAttackFails(3);
                $this->entity_manager->persist($town);
                break;
            case 'advance':
                if ($night->advance_day($town)) {
                    foreach ($night->get_cleanup_container() as $c) $this->entity_manager->remove($c);
                    $town->setAttackFails(0);
                    $this->entity_manager->persist( $town );
                }
                break;

            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    public function get_map_blob(Town $town): array {
        $zones = []; $range_x = [PHP_INT_MAX,PHP_INT_MIN]; $range_y = [PHP_INT_MAX,PHP_INT_MIN];
        $zones_classes = [];

        $soul_zones_ids = array_map(function(Zone $z) { return $z->getId(); },$this->zone_handler->getSoulZones( $town ) );

        foreach ($town->getZones() as $zone) {
            $x = $zone->getX();
            $y = $zone->getY();

            $range_x = [ min($range_x[0], $x), max($range_x[1], $x) ];
            $range_y = [ min($range_y[0], $y), max($range_y[1], $y) ];

            if (!isset($zones[$x])) $zones[$x] = [];
            $zones[$x][$y] = $zone;



            if (!isset($zones_attributes[$x])) $zones_attributes[$x] = [];
            $zones_classes[$x][$y] = $this->zone_handler->getZoneClasses(
                $town,
                $zone,
                null,
                in_array($zone->getId(), $soul_zones_ids)
            );
        }

        return [
            'map_data' => [
                'zones' =>  $zones,
                'zones_classes' =>  $zones_classes,
                'town_devast' => $town->getDevastated(),
                'routes' => $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown( $town ),
                'pos_x'  => 0,
                'pos_y'  => 0,
                'map_x0' => $range_x[0],
                'map_x1' => $range_x[1],
                'map_y0' => $range_y[0],
                'map_y1' => $range_y[1],
            ]
        ];
    }
}
