<?php

namespace App\Controller\Admin;

use App\Entity\Changelog;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminChangelogController extends AdminActionController
{
    /**
     * @Route("jx/admin/changelogs", name="admin_changelogs")
     * @return Response
     */
    public function changelogs(): Response
    {
        $news = $this->entity_manager->getRepository(Changelog::class)->findAll();

        return $this->render( 'ajax/admin/changelogs/changelogs.html.twig', $this->addDefaultTwigArgs(null, [
            'news' => $news
        ]));
    }

    /**
     * @Route("jx/admin/changelogs/new", name="admin_changelogs_add_new")
     * @return Response
     */
    public function new_changelog(): Response
    {
        return $this->render( 'ajax/admin/changelogs/changelogs.html.twig', []);      
    }
}
