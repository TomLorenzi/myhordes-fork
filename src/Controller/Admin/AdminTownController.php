<?php

namespace App\Controller\Admin;

use App\Entity\AdminReport;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Entity\Zone;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\NightlyHandler;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        return $this->render( 'ajax/admin/towns/explorer.html.twig', [
            'town' => $town,
            'conf' => $this->conf->getTownConfiguration( $town ),
            'explorables' => $explorables,
            'log' => $this->renderLog( -1, $town, false, null, null )->getContent(),
            'day' => $town->getDay()
        ]);
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
}
