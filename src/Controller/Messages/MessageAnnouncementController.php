<?php

namespace App\Controller\Messages;

use App\Entity\Announcement;
use App\Entity\Changelog;
use App\Entity\ForumPoll;
use App\Entity\ForumPollAnswer;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPoll;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\HTMLService;
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
     * @Route("jx/admin/com/changelogs/polls", name="admin_polls", priority=1)
     * @return Response
     */
    public function polls(  ): Response
    {
        return $this->render( 'ajax/admin/changelogs/polls.html.twig', $this->addDefaultTwigArgs(null, [
            'polls' => $this->entity_manager->getRepository(GlobalPoll::class)->findAll(),
            'emotes' => $this->getEmotesByUser($this->getUser(),true),
        ]));
    }

    /**
     * @Route("api/admin/com/changelogs/new_poll", name="admin_changelog_new_poll")
     * @param JSONRequestParser $parser
     * @param HTMLService $html
     * @return Response
     */
    public function create_poll_api(JSONRequestParser $parser, HTMLService $html): Response {
        if ($this->isGranted('ROLE_ADMIN')) $p = ForumUsagePermissions::PermissionOwn;
        elseif ($this->isGranted('ROLE_CROW')) $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingModerator;
        else $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingOracle;

        $format_html = function(&$data) use ($html, $p): bool {
            foreach ($this->generatedLangsCodes as $lang) {
                $str = trim($data[$lang]);
                if (mb_strlen($str) < 3) return false;
                if (!$html->htmlPrepare( $this->getUser(), $p, false, $data[$lang], null, $len  )) return false;
                if ($len < 3) return false;
            }
            return true;
        };

        $title = $parser->get_array( 'title' );
        $desc = $parser->get_array( 'desc' );
        $premature = (bool)$parser->get( 'premature' );
        $preview = $parser->get_array( 'preview' );

        try {
            $start = new DateTime( $parser->get('start', '-1') );
            $end = new DateTime( $parser->get('end', '-1') );
        } catch (\Throwable $t) { return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest ); }

        if ($end <= new DateTime('now')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if ($start >= $end) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $answers = array_values( $parser->get_array( 'answers' ) );
        if (count($answers) < 2) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (!$format_html($title)) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (!$format_html($desc)) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (!$format_html($preview)) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $answer_data = [];
        foreach ( $answers as &$answer ) {
            if (!$format_html($answer['title'])) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            if (!$format_html($answer['desc'])) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            $answer_data[] = [$answer, (new ForumPollAnswer())->setNum(0)];
        }

        $poll = (new ForumPoll())->setOwner( $this->getUser() )->setClosed( false );
        foreach ($answer_data as [,$answer_entity]) $poll->addAnswer( $answer_entity );

        try {
            $this->entity_manager->persist($poll);
            $this->entity_manager->flush();

            $global_poll = (new GlobalPoll())
                ->setPoll( $poll )->setStartDate( $start )->setEndDate( $end )->setShowResultsImmediately( $premature );

            foreach ($langs as $lang) {
                $global_poll
                    ->setTitleByLang( $lang, $title[$lang] )
                    ->setDescriptionByLang( $lang, $desc[$lang] )
                    ->setShortDescriptionByLang( $lang, $preview[$lang] );
                foreach ($answer_data as [['title' => $answer_title, 'desc' => $answer_desc],$entity])
                    $global_poll
                        ->setAnswerTitleByLang( $entity, $lang, $answer_title[$lang] )
                        ->setAnswerDescriptionByLang( $entity, $lang, $answer_desc[$lang] );
            }

            $this->entity_manager->persist($global_poll);
            $this->entity_manager->flush();

        } catch (\Throwable $t) { return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['m' => $t->getMessage()] ); }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_polls', ['id' => $global_poll->getId()])] );
    }

    /**
     * @Route("api/admin/changelogs/poll/{id}/{action}", name="admin_changelog_poll_control")
     * @param int $id
     * @param string $action
     * @return Response
     */
    public function modify_poll_api(int $id, string $action): Response {

        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $poll = $this->entity_manager->getRepository(GlobalPoll::class)->find($id);
        if (!$poll) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $now = new DateTime();
        if ($action === 'start' && $poll->getStartDate() > $now)
            $this->entity_manager->persist( $poll->setStartDate($now) );
        elseif ($action === 'close' && $poll->getStartDate() < $now && $poll->getEndDate() > $now)
            $this->entity_manager->persist( $poll->setEndDate($now) );
        elseif ($action === 'delete') {
            $this->entity_manager->remove( $poll );
        }

        $this->entity_manager->flush();

        return AjaxResponse::success( );
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