<?php

namespace App\Controller\Admin;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractController;
use App\Entity\Season;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Translation\T;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminSeasonController extends CustomAbstractController
{
    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/seasons/all', name: 'admin_seasons_view')]
    public function seasons_view(): Response
    {
        $seasons = $this->entity_manager->getRepository(Season::class)->findAll();
        return $this->render( 'ajax/admin/seasons/list.html.twig', $this->addDefaultTwigArgs(null, ['all_seasons' => $seasons]));
    }

    /**
     * @param int $id The season ID we want to toggle current
     * @return Response
     */
    #[Route(path: 'api/admin/seasons/toggle_current/{id}', name: 'admin_toggle_current_season')]
    #[IsGranted('ROLE_ADMIN')]
    public function seasons_toggle_current(int $id): Response
    {
        $seasons = $this->entity_manager->getRepository(Season::class)->findAll();
        foreach ($seasons as $season) {
            $season->setCurrent($season->getId() === $id);
            $this->entity_manager->persist($season);
        }

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/seasons/{id<\d+>}', name: 'admin_season_edit')]
    public function season_edit(int $id): Response
    {
        T::__("Neue Saison", "admin");
        if (!$this->isGranted('ROLE_ADMIN')) $this->redirect($this->generateUrl('admin_seasons_view'));
        $season = $this->entity_manager->getRepository(Season::class)->find($id);
        if ($season === null) return $this->redirect($this->generateUrl('admin_seasons_view'));
        return $this->render( 'ajax/admin/seasons/edit.html.twig', $this->addDefaultTwigArgs(null, ['current_season' => $season]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/seasons/new', name: 'admin_season_new')]
    public function season_new(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) $this->redirect($this->generateUrl('admin_seasons_view'));
        return $this->render( 'ajax/admin/seasons/edit.html.twig', $this->addDefaultTwigArgs(null, ['current_season' => null]));
    }

    /**
     * @param int $id
     * @param JSONRequestParser $parser
     * @param RandomGenerator $rand
     * @return Response
     */
    #[Route(path: 'api/admin/seasons/register/{id<-?\d+>}', name: 'admin_update_season')]
    public function season_update(int $id, JSONRequestParser $parser, RandomGenerator $rand): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['number','subnumber','current'])) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $season = $id < 0 ? new Season() : $this->entity_manager->getRepository(Season::class)->find($id);
        if ($season === null ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $season
            ->setNumber( (int)$parser->get('number') )
            ->setSubNumber( (int)$parser->get('subnumber') )
            ->setCurrent( (bool)$parser->get('current') );

        $test = $this->entity_manager->getRepository(Season::class)->findOneBy(['number' => $season->getNumber(), 'subNumber' => $season->getSubNumber()]);
        if ($test !== null && $id < 0) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($season->getCurrent()) {
            $allSeasons = $this->entity_manager->getRepository(Season::class)->findAll();
            foreach ($allSeasons as $current_season) {
                if($current_season->getId() !== $season->getId()) {
                    $current_season->setCurrent(false);
                    $this->entity_manager->persist($current_season);
                }
            }
        }

        $this->entity_manager->persist($season);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}