<?php

namespace App\Controller\Admin;

use App\Entity\Changelog;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminChangelogController extends AdminActionController
{
    /**
     * @Route("jx/admin/changelogs", name="admin_changelogs")
     * @param string $opt
     * @return Response
     */
    public function changelogs(): Response
    {
        $news = $this->entity_manager->getRepository(Changelog::class)->findAll();

        return $this->render( 'ajax/admin/changelogs/changelogs.html.twig', [
            'news' => $news
        ]);      
    }

    /**
     * @Route("jx/admin/changelogs/new", name="admin_changelogs_add_new")
     * @param string $opt
     * @return Response
     */
    public function new_changelog(): Response
    {
        return $this->render( 'ajax/admin/changelogs/changelogs.html.twig', []);      
    }
}
