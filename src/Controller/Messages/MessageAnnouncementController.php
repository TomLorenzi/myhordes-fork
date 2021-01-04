<?php

namespace App\Controller\Messages;

use App\Entity\Announcement;
use App\Entity\Changelog;
use App\Entity\ForumUsagePermissions;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @IsGranted("ROLE_USER")
 * @method User getUser
 */
class MessageAnnouncementController extends MessageController
{
    /**
     * @Route("jx/admin/changelogs/c/editor", name="admin_new_changelog_editor_controller")
     * @return Response
     */
    public function admin_new_changelog_editor_controller(): Response {
        $user = $this->getUser();

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionOwn ),
            'snippets' => [],
            'emotes' => $this->getEmotesByUser($user,true),

            'forum' => false,
            'type' => 'changelog',
            'target_url' => 'admin_changelog_new_changelog',
            'town_controls' => false
        ] );
    }

    /**
     * @Route("jx/admin/changelogs/a/editor", name="admin_new_announcement_editor_controller")
     * @return Response
     */
    public function admin_new_announcement_editor_controller(): Response {
        $user = $this->getUser();

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionOwn ),
            'snippets' => [],
            'emotes' => $this->getEmotesByUser($user,true),

            'forum' => false,
            'type' => 'announcement',
            'target_url' => 'admin_changelog_new_announcement',
            'town_controls' => false
        ] );
    }

    /**
     * @Route("api/admin/changelogs/new_changelog", name="admin_changelog_new_changelog")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function create_changelog_api(EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $version   = $parser->get('version', '');
        $lang      = $parser->get('lang', 'de');

        $author    = $this->getUser();

        if(empty($title) || empty($content) || empty($version)) {
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $change = new Changelog();
        $change->setTitle($title)->setText($content)->setVersion($version)->setLang($lang)->setAuthor($author)->setDate(new DateTime());

        $tx_len = 0;
        if (!$this->preparePost($author,null,$change,$tx_len))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $em->persist($change);
        $em->flush();

        return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_changelogs', ['tab' => 'changelog'])] );
    }

    /**
     * @Route("api/admin/changelogs/new_announcement", name="admin_changelog_new_announcement")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function create_announcement_api(EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $lang      = $parser->get('lang', 'de');

        $author = $this->getUser();

        if(empty($title) || empty($content)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $announcement = (new Announcement())
            ->setTitle($title)->setText($content)->setLang($lang)->setSender($author)->setTimestamp(new DateTime());

        $tx_len = 0;
        if (!$this->preparePost($author,null,$announcement,$tx_len))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $em->persist($announcement);
        $em->flush();

        return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_changelogs', ['tab' => 'announcement'])] );
    }

    /**
     * @Route("api/admin/changelogs/del_a/{id<\d+>}", name="admin_changelog_del_announcement")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function delete_announcement_api(int $id, EntityManagerInterface $em): Response {
        $announcement = $em->getRepository(Announcement::class)->find($id);

        if (!$announcement) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $em->remove($announcement);
        $em->flush();

        return AjaxResponse::success( );
    }
}