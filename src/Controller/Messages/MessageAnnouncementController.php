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
     * @Route("jx/admin/com/changelogs/{tab}", name="admin_changelogs")
     * @param string $tab
     * @return Response
     */
    public function changelogs( string $tab = 'changelog' ): Response
    {
        return $this->render( 'ajax/admin/changelogs/changelogs.html.twig', $this->addDefaultTwigArgs(null, [
            'news' => $this->isGranted('ROLE_CROW') ? $this->entity_manager->getRepository(Changelog::class)->findAll() : [],
            'announces' => $this->entity_manager->getRepository(Announcement::class)->findAll(),
            'tab' => $tab
        ]));
    }

    /**
     * @Route("jx/admin/changelogs/c/editor", name="admin_new_changelog_editor_controller")
     * @return Response
     */
    public function admin_new_changelog_editor_controller(): Response {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) $p = ForumUsagePermissions::PermissionOwn;
        elseif ($this->isGranted('ROLE_CROW')) $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingModerator;
        else $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingOracle;

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( $p ),
            'snippets' => [],
            'emotes' => $this->getEmotesByUser($user,true),

            'forum' => false,
            'type' => 'changelog',
            'username' => $user->getName(),
            'target_url' => 'admin_changelog_new_changelog',
            'town_controls' => false
        ] );
    }

    /**
     * @Route("jx/admin/com/changelogs/a/editor", name="admin_new_announcement_editor_controller")
     * @return Response
     */
    public function admin_new_announcement_editor_controller(): Response {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) $p = ForumUsagePermissions::PermissionOwn;
        elseif ($this->isGranted('ROLE_CROW')) $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingModerator;
        else $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingOracle;

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( $p ),
            'snippets' => [],
            'emotes' => $this->getEmotesByUser($user,true),

            'forum' => false,
            'type' => 'announcement',
            'username' => $user->getName(),
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
     * @Route("api/admin/com/changelogs/new_announcement", name="admin_changelog_new_announcement")
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
     * @Route("api/admin/com/changelogs/del_a/{id<\d+>}", name="admin_changelog_del_announcement")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function delete_announcement_api(int $id, EntityManagerInterface $em): Response {
        $announcement = $em->getRepository(Announcement::class)->find($id);

        if (!$announcement) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $announcement->getSender())
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $em->remove($announcement);
        $em->flush();

        return AjaxResponse::success( );
    }
}