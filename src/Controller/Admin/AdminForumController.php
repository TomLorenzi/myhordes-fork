<?php

namespace App\Controller\Admin;

use App\DataFixtures\PermissionFixtures;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\Complaint;
use App\Entity\ForumModerationSnippet;
use App\Entity\ForumUsagePermissions;
use App\Entity\ItemPrototype;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class AdminForumController extends AdminActionController
{
    /**
     * @Route("jx/admin/forum/report/pm", name="admin_pm_viewer")
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @return Response
     */
    public function render_pm(JSONRequestParser $parser, PermissionHandler $perm) {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return new Response();

        /** @var PrivateMessage $pm */
        if (!($pm = $this->entity_manager->getRepository(PrivateMessage::class)->find($pmid)))
            return new Response();

        if ($pm->getAdminReports(true)->isEmpty())  return new Response();

        $thread = $pm->getPrivateMessageThread();
        if (!$thread || !$thread->getSender() || !$thread->getSender()->getTown()->getForum()) return new Response();

        if (!$perm->checkAnyEffectivePermissions($user, $thread->getSender()->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]))
            return new Response();

        $posts = $thread->getMessages();

        return $this->render( 'ajax/admin/reports/pn-viewer.html.twig', $this->addDefaultTwigArgs(null, [
            'thread' => $thread,
            'posts' => $posts,
            'markedPost' => $pmid,
            'emotes' => []
        ] ));
    }

    /**
     * @Route("api/admin/forum/reports/clear", name="admin_reports_clear")
     * @param JSONRequestParser $parser
     * @param AdminActionHandler $admh
     * @return Response
     */
    public function reports_clear(JSONRequestParser $parser, AdminActionHandler $admh): Response
    {
        if (!$parser->has_all(['postId'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user = $this->getUser();
        $postId = $parser->get('postId');
        if ($admh->clearReports($user->getId(), $postId))
            return AjaxResponse::success();
        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/admin/forum/reports/moderate-pm", name="admin_reports_mod_pn")
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @return Response
     */
    public function reports_moderate_pm(JSONRequestParser $parser, PermissionHandler $perm): Response
    {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var PrivateMessage $pm */
        if (!($pm = $this->entity_manager->getRepository(PrivateMessage::class)->find($pmid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $has_report = false;
        foreach ($pm->getPrivateMessageThread()->getMessages() as $ppm)
        if (!$ppm->getAdminReports(true)->isEmpty()) $has_report = true;

        if (!$has_report) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $thread = $pm->getPrivateMessageThread();
        if (!$thread || !$thread->getSender() || !$thread->getSender()->getTown()->getForum()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$perm->checkAnyEffectivePermissions($user, $thread->getSender()->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $seen = (bool)$parser->get('seen', false);
        $hide = (bool)$parser->get('hide', false);
        $message = $parser->get('message', null);

        if (!$seen && !$hide && !$message) return AjaxResponse::success();

        if ($seen)
            foreach ($pm->getAdminReports(true) as $report)
                $this->entity_manager->persist($report->setSeen(true));

        if ($hide) $pm->setHidden(true);
        if ($message) $pm->setModMessage( $message );
        $this->entity_manager->persist($pm->setModerator($user));

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/forum/reports/snippet/add", name="admin_reports_add_snippet")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function add_snippet(JSONRequestParser $parser): Response {

        if (!$parser->has_all(['id','lang','content'],true)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $lang = strtolower( $parser->trimmed('lang') );

        if (!in_array($lang, ['de','en','fr','es'])) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($this->entity_manager->getRepository(ForumModerationSnippet::class)->findOneBy(['id' => $parser->trimmed('id'), 'lang' => $lang]))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );



        $this->entity_manager->persist( (new ForumModerationSnippet)
            ->setShort( $parser->trimmed('id') )
            ->setLang( $lang )
            ->setText( $parser->trimmed( 'content' ) )
        );

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/forum/reports/snippet/remove/{id<\d+>}", name="admin_reports_remove_snippet")
     * @param int $id
     * @return Response
     */
    public function remove_snippet(int $id): Response {


        $snippet = $this->entity_manager->getRepository(ForumModerationSnippet::class)->find($id);

        if ($snippet) {
            $this->entity_manager->remove($snippet);
            try {
                $this->entity_manager->flush();
            } catch (\Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            return AjaxResponse::success();
        } else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
    }

    /**
     * @Route("jx/admin/forum/{tab}/{opt}", name="admin_reports")
     * @param PermissionHandler $perm
     * @param string $opt
     * @return Response
     */
    public function reports(PermissionHandler $perm, string $tab = 'reports', string $opt = ''): Response
    {
        $show_all = $opt === 'all';

        $allowed_forums = [];

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => false]);

        $forum_reports = array_filter($reports, function(AdminReport $r) use (&$allowed_forums, $perm) {
            if ($r->getPost() === null) return false;
            $tid = $r->getPost()->getThread()->getForum()->getId();
            if (isset($allowed_forums[$tid])) return $allowed_forums[$tid];
            else return $allowed_forums[$tid] = $perm->checkAnyEffectivePermissions($this->getUser(), $r->getPost()->getThread()->getForum(), [ForumUsagePermissions::PermissionReadThreads, ForumUsagePermissions::PermissionModerate]);
        });

        // Make sure to fetch only unseen reports for posts with at least 2 unseen reports
        $postsList = [
            'post' => array_map(function($report) { return $report->getPost(); }, $forum_reports),
            'reporter' => array_map(function($report) { return $report->getSourceUser(); }, $forum_reports)
        ];

        $alreadyCountedIndexes = [];
        $selectedReports = [];
        foreach ($postsList['post'] as $idx => $post) {
            if (in_array($idx, $alreadyCountedIndexes))
                continue;
            $keys = array_keys($postsList['post'], $post);
            $alreadyCountedIndexes = array_merge($alreadyCountedIndexes, $keys);
            $reportCount = count($keys);
            if ($reportCount > ($show_all ? 0 : 1)) {
                $reporters = [];
                foreach ($keys as $key){
                    $reporters[] = $postsList['reporter'][$key];
                }
                $selectedReports[] = array('post' => $post, 'count' => $reportCount, 'reporters' => $reporters);
            }
        }

        /** @var AdminReport[] $pm_reports */
        $pm_reports = array_filter($reports, function(AdminReport $r) use (&$allowed_forums, $perm) {
            if ($r->getPm() === null || $r->getPm()->getOwner() === null || $r->getPm()->getOwner()->getTown()->getForum() === null) return false;
            $tid = $r->getPm()->getOwner()->getTown()->getForum()->getId();
            if (isset($allowed_forums[$tid])) return $allowed_forums[$tid];
            else return $allowed_forums[$tid] = $perm->checkAnyEffectivePermissions($this->getUser(), $r->getPm()->getOwner()->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]);
        });

        $pm_cache = [];
        foreach ($pm_reports as $report)
            if (!isset($pm_cache[$report->getPm()->getId()]))
                $pm_cache[$report->getPm()->getId()] = [
                    'post' => $report->getPm(), 'count' => 1, 'reporters' => [ $report->getSourceUser() ]
                ];
            else {
                $pm_cache[$report->getPm()->getId()]['count']++;
                $pm_cache[$report->getPm()->getId()]['reporters'][] = $report->getSourceUser();
            }

        return $this->render( 'ajax/admin/reports/reports.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => $tab,

            'posts' => $selectedReports,
            'pms' => $pm_cache,
            'all_shown' => $show_all,

            'snippets' => $this->entity_manager->getRepository(ForumModerationSnippet::class)->findAll()
        ]));
    }
}
