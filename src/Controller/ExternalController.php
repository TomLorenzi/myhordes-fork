<?php
/** @noinspection PhpRouteMissingInspection */

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\ExternalApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ExternalController
 * @package App\Controller
 * @GateKeeperProfile(allow_during_attack=true, record_user_activity=false)
 */
class ExternalController extends CustomAbstractController {

    /**
     * @Route("/jx/disclaimer/{id<\d+>}", name="disclaimer", condition="request.isXmlHttpRequest()")
     * @param int $id
     * @return Response
     */
    public function disclaimer(int $id): Response {
        /** @var ExternalApp $app */
        $app = $this->entity_manager->getRepository(ExternalApp::class)
                                    ->find($id);
        $user = $this->getUser();
        if (!$app || !$user || ($app->getTesting() && $app->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
            return $this->redirect($this->generateUrl('initial_landing'));
        }

        $key = $user->getExternalId();

        return $this->render('ajax/public/disclaimer.html.twig', [
                                                                   'ex'  => $app,
                                                                   'key' => $key
                                                               ]
        );
    }

    /**
     * @Route("/jx/json_docs", name="json_docs", condition="request.isXmlHttpRequest()")
     * @return Response
     */
    public function json_documentation(): Response {
        return $this->render('ajax/public/jsonapidocs.html.twig', []);
    }

    /**
     * @Route("/jx/xml_docs", name="xml_docs", condition="request.isXmlHttpRequest()")
     * @return Response
     */
    public function xml_documentation(): Response {
        return $this->render('ajax/public/xmlapidocs.html.twig', []);
    }
}
