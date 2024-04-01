<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Annotations\GateKeeperProfile;
use App\Entity\AdminReport;
use App\Entity\BlackboardEdit;
use App\Entity\CitizenRankingProxy;
use App\Entity\ForumModerationSnippet;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\ReportSeenMarker;
use App\Entity\User;
use App\Enum\AdminReportSpecification;
use App\Response\AjaxResponse;
use App\Service\AdminHandler;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminForumController extends AdminActionController
{
    /**
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/report/pm', name: 'admin_pm_viewer')]
    public function render_pm(JSONRequestParser $parser, PermissionHandler $perm, HTMLService $html) {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return new Response();

        /** @var PrivateMessage $pm */
        if (!($pm = $this->entity_manager->getRepository(PrivateMessage::class)->find($pmid)))
            return new Response();

        if ($pm->getAdminReports(false)->isEmpty())  return new Response();

        $thread = $pm->getPrivateMessageThread();
        if (!$thread || !$thread->getSender() || !$thread->getSender()->getTown()->getForum()) return new Response();

        if (!$perm->checkAnyEffectivePermissions($user, $thread->getSender()->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]))
            return new Response();

        $posts = $thread->getMessages();
        foreach ($posts as $post) $post->setText( $html->prepareEmotes( $post->getText(), $this->getUser() ) );

        return $this->render( 'ajax/admin/reports/pn-viewer.html.twig', $this->addDefaultTwigArgs(null, [
            'thread' => $thread,
            'posts' => $posts,
            'markedPost' => $pmid,
            'emotes' => []
        ] ));
    }

    /**
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/report/gpm', name: 'admin_gpm_viewer')]
    public function render_gpm(JSONRequestParser $parser, HTMLService $html) {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return new Response();

        /** @var GlobalPrivateMessage $message */
        if (!($message = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->find($pmid)))
            return new Response();

        if ($message->getAdminReports(false)->isEmpty()) return new Response();

        $group = $message->getReceiverGroup();
        if (!$group || !$message->getSender()) return new Response();

        $posts = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->findByGroup( $group, 0, 15, $message->getId() );
        foreach ($posts as $post) $post->setText( $html->prepareEmotes( $post->getText(), $this->getUser() ) );

        return $this->render( 'ajax/admin/reports/gpn-viewer.html.twig', $this->addDefaultTwigArgs(null, [
            'group' => $group,
            'posts' => $posts,
            'markedPost' => $pmid,
            'emotes' => []
        ] ));
    }

    /**
     * @param JSONRequestParser $parser
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/clear', name: 'admin_reports_clear')]
    #[AdminLogProfile(enabled: true)]
    public function reports_clear(JSONRequestParser $parser, AdminHandler $admh): Response
    {
        if (!$parser->has_all(['postId'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user = $this->getUser();
        $postId = $parser->get('postId');
        if ($admh->clearReports($user->getId(), $postId)){
            $this->logger->invoke("Admin <info>{$this->getUser()->getName()}</info> cleared reports");
            return AjaxResponse::success();
        }
        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @param JSONRequestParser $parser
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/seen', name: 'admin_reports_seen')]
    #[AdminLogProfile(enabled: true)]
    public function reports_seen(JSONRequestParser $parser, AdminHandler $admh): Response
    {
        if (!$parser->has_all(['postId'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user = $this->getUser();
        $post = $this->entity_manager->getRepository(Post::class)->find($parser->get('postId'));
        $reports = $post->getAdminReports();

        try
        {
            foreach ($reports as $report) {
                $existing_seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $user, 'report' => $report]);
                if (!$existing_seen) $this->entity_manager->persist( (new ReportSeenMarker())->setUser($user)->setReport($report) );
            }
            $this->entity_manager->flush();
        }
        catch (\Throwable $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }
        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @param CrowService $crow
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/moderate-pm', name: 'admin_reports_mod_pm')]
    #[AdminLogProfile(enabled: true)]
    public function reports_moderate_pm(JSONRequestParser $parser, PermissionHandler $perm, CrowService $crow): Response
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

        if ($hide || $message) {
            $notification = $crow->createPM_moderation( $pm->getOwner()->getUser(),
                CrowService::ModerationActionDomainTownPM, CrowService::ModerationActionTargetPost, $hide ? CrowService::ModerationActionDelete : CrowService::ModerationActionEdit,
                $pm, $message ?? ''
            );
            if ($notification) $this->entity_manager->persist($notification);
        }

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/moderate-bb', name: 'admin_reports_mod_bb')]
    #[AdminLogProfile(enabled: true)]
    public function reports_moderate_bb(JSONRequestParser $parser, PermissionHandler $perm): Response
    {
        $user = $this->getUser();

        $bbid = $parser->get('bbid', null);
        if ($bbid === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var PrivateMessage $pm */
        if (!($board = $this->entity_manager->getRepository(BlackboardEdit::class)->find($bbid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);


        $existing_reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['blackBoard' => $board]);
        if (empty($existing_reports)) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$perm->checkAnyEffectivePermissions($user, $board->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $seen = (bool)$parser->get('seen', false);

        if (!$seen) return AjaxResponse::success();
        foreach ($existing_reports as $report)
            $this->entity_manager->persist($report->setSeen(true));

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/moderate-c', name: 'admin_reports_mod_c')]
    #[AdminLogProfile(enabled: true)]
    public function reports_moderate_c(JSONRequestParser $parser): Response
    {
        $user = $this->getUser();

        $cid = $parser->get_int('cid', null);
        $ct = $parser->get_int('ct', null);
        if ($cid === null || $ct === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (($ct = AdminReportSpecification::tryFrom($ct)) === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!($citizen = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($cid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $existing_reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['citizen' => $citizen, 'specification' => $ct->value]);
        if (empty($existing_reports)) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $seen = (bool)$parser->get('seen', false);

        if (!$seen) return AjaxResponse::success();
        foreach ($existing_reports as $report)
            $this->entity_manager->persist($report->setSeen(true));

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/moderate-u', name: 'admin_reports_mod_u')]
    #[AdminLogProfile(enabled: true)]
    public function reports_moderate_u(JSONRequestParser $parser): Response
    {
        $user = $this->getUser();

        $uid = $parser->get_int('uid', null);
        if ($uid === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!($target = $this->entity_manager->getRepository(User::class)->find($uid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $existing_reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['user' => $target]);
        if (empty($existing_reports)) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $seen = (bool)$parser->get('seen', false);

        if (!$seen) return AjaxResponse::success();
        foreach ($existing_reports as $report)
            $this->entity_manager->persist($report->setSeen(true));

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param PermissionHandler $perm
     * @param CrowService $crow
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/seen-pm', name: 'admin_reports_seen_pm')]
    #[AdminLogProfile(enabled: true)]
    public function reports_seen_pm(JSONRequestParser $parser, PermissionHandler $perm, CrowService $crow): Response
    {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var PrivateMessage $pm */
        if (!($pm = $this->entity_manager->getRepository(PrivateMessage::class)->find($pmid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        foreach ($pm->getAdminReports() as $report) {
            $existing_seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $user, 'report' => $report]);
            if (!$existing_seen) $this->entity_manager->persist( (new ReportSeenMarker())->setUser($user)->setReport($report) );
        }

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param CrowService $crow
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/moderate-gpm', name: 'admin_reports_mod_gpm')]
    #[AdminLogProfile(enabled: true)]
    public function reports_moderate_gpm(JSONRequestParser $parser, CrowService $crow): Response
    {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var GlobalPrivateMessage $pm */
        if (!($pm = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->find($pmid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$pm->getReceiverGroup()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $has_report = false;
        foreach ($this->entity_manager->getRepository(GlobalPrivateMessage::class)->findBy(['receiverGroup' => $pm->getReceiverGroup()]) as $ppm)
            if (!$ppm->getAdminReports(true)->isEmpty()) $has_report = true;

        if (!$has_report) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $seen = (bool)$parser->get('seen', false);
        $hide = (bool)$parser->get('hide', false);
        $message = $parser->get('message', null);

        if (!$seen && !$hide && !$message) return AjaxResponse::success();

        if ($seen)
            foreach ($pm->getAdminReports(true) as $report)
                $this->entity_manager->persist($report->setSeen(true));

        if ($hide) $pm->setHidden(true)->setPinned(false)->setCollapsed(false);
        if ($message) $pm->setModMessage( $message );
        $this->entity_manager->persist($pm->setModerator($user));

        if ($hide || $message) {
            $notification = $crow->createPM_moderation( $pm->getSender(),
                CrowService::ModerationActionDomainGlobalPM, CrowService::ModerationActionTargetPost, $hide ? CrowService::ModerationActionDelete : CrowService::ModerationActionEdit,
                $pm, $message ?? ''
            );
            if ($notification) $this->entity_manager->persist($notification);
        }

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param CrowService $crow
     * @return Response
     */
    #[Route(path: 'api/admin/forum/reports/seen-gpm', name: 'admin_reports_seen_gpm')]
    #[AdminLogProfile(enabled: true)]
    public function reports_seen_gpm(JSONRequestParser $parser, CrowService $crow): Response
    {
        $user = $this->getUser();

        $pmid = $parser->get('pmid', null);
        if ($pmid === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var GlobalPrivateMessage $pm */
        if (!($pm = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->find($pmid)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$pm->getReceiverGroup()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        foreach ($pm->getAdminReports() as $report) {
            $existing_seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $user, 'report' => $report]);
            if (!$existing_seen) $this->entity_manager->persist( (new ReportSeenMarker())->setUser($user)->setReport($report) );
        }

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param RoleHierarchyInterface $roles
     * @return Response
     */
    #[Route(path: 'api/admin/com/forum/reports/snippet/add', name: 'admin_reports_add_snippet')]
    #[AdminLogProfile(enabled: true)]
    public function add_snippet(JSONRequestParser $parser, RoleHierarchyInterface $roles): Response {

        if (!$parser->has_all(['id','lang','content','edit','role'],true)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $lang = strtolower( $parser->trimmed('lang') );
        $role = strtoupper( $parser->trimmed('role') );

        if (!in_array($lang, $this->allLangsCodes)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($role === '*' && !$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
        $available_roles = array_intersect(
            ["ROLE_CROW", "ROLE_ADMIN", "ROLE_ORACLE", "ROLE_ANIMAC"],
            $roles->getReachableRoleNames( $this->getUser()->getRoles() )
        );

        if ($role !== '*' && !in_array($role, $available_roles)) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $existing_id = $parser->get_int('edit', -1);
        $existing = $this->entity_manager->getRepository(ForumModerationSnippet::class)->findOneBy(['short' => $parser->trimmed('id'), 'lang' => $lang]);
        $editing  = $existing_id >= 0 ? $this->entity_manager->getRepository(ForumModerationSnippet::class)->find( $existing_id ) : null;
        if ( ($existing_id < 0 && $existing) || ($existing_id >= 0 && $existing?->getId() !== $existing_id) || ( $existing_id >= 0 && !$editing ) )
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $this->entity_manager->persist( ($existing_id < 0 ? (new ForumModerationSnippet) : $editing)
            ->setShort( $parser->trimmed('id') )
            ->setLang( $lang )
            ->setText( $parser->trimmed( 'content' ) )
            ->setRole( $role )
        );

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param ForumModerationSnippet $snippet
     * @param RoleHierarchyInterface $roles
     * @return Response
     */
    #[Route(path: 'api/admin/com/forum/reports/snippet/remove/{id<\d+>}', name: 'admin_reports_remove_snippet')]
    #[AdminLogProfile(enabled: true)]
    public function remove_snippet(ForumModerationSnippet $snippet, RoleHierarchyInterface $roles): Response {
        if ($snippet->getRole() === '*' && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$this->isGranted($snippet->getRole()))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $this->entity_manager->remove($snippet);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param PermissionHandler $perm
     * @param string $opt
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/posts/{opt}', name: 'admin_reports_forum_posts')]
    public function forum_reports(PermissionHandler $perm, string $opt = ''): Response
    {
        $show_bin = $opt === 'bin';
        $show_all = $show_bin || $opt === 'all';

        $allowed_forums = [];

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => $show_bin]);

        $forum_reports = array_filter($reports, function(AdminReport $r) use (&$allowed_forums, $perm) {
            if ($r->getPost() === null) return false;
            $tid = $r->getPost()->getThread()->getForum()->getId();
            if (isset($allowed_forums[$tid])) return $allowed_forums[$tid];
            else return $allowed_forums[$tid] = $perm->checkAnyEffectivePermissions($this->getUser(), $r->getPost()->getThread()->getForum(), [ForumUsagePermissions::PermissionReadThreads, ForumUsagePermissions::PermissionModerate]);
        });

        // Make sure to fetch only unseen reports for posts with at least 2 unseen reports
        $postsList = [
            'post' => array_map(fn(AdminReport $report) => $report->getPost(), $forum_reports),
            'reporter' => array_map(fn(AdminReport $report) => $report->getSourceUser(), $forum_reports)
        ];

        $alreadyCountedIndexes = [];
        $selectedReports = [];
        foreach ($postsList['post'] as $idx => $post) {
            if (in_array($idx, $alreadyCountedIndexes))
                continue;
            $keys = array_keys($postsList['post'], $post);
            $alreadyCountedIndexes = array_merge($alreadyCountedIndexes, $keys);
            $reportCount = count($keys);
            $seenCount = array_reduce($keys, fn(int $i, $key) => $i + ( $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $this->getUser(), 'report' => $forum_reports[$key]]) ? 1 : 0 ), 0);
            if ($show_all || ($reportCount > 1 && $reportCount > $seenCount)) {
                $reporters = [];
                foreach ($keys as $key){
                    $reporters[] = $postsList['reporter'][$key];
                }
                $selectedReports[] = array('post' => $post, 'count' => $reportCount, 'reporters' => $reporters);
            }
        }

        return $this->render( 'ajax/admin/reports/posts.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'posts',

            'posts' => $selectedReports,

            'opt' => $opt,
            'all_shown' => $show_all,
            'bin_shown' => $show_bin,
        ]));
    }

    /**
     * @param PermissionHandler $perm
     * @param string $opt
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/pn/{opt}', name: 'admin_reports_town')]
    public function pn_reports(PermissionHandler $perm, string $opt = ''): Response
    {
        $show_bin = $opt === 'bin';

        $allowed_forums = [];

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => $show_bin]);

        /** @var AdminReport[] $pm_reports */
        $pm_reports = array_filter($reports, function(AdminReport $r) use (&$allowed_forums, $perm) {
            if ($r->getPm() === null || $r->getPm()->getOwner() === null || $r->getPm()->getOwner()->getTown()->getForum() === null) return false;
            $tid = $r->getPm()->getOwner()->getTown()->getForum()->getId();
            if (isset($allowed_forums[$tid])) return $allowed_forums[$tid];
            else return $allowed_forums[$tid] = $perm->checkAnyEffectivePermissions($this->getUser(), $r->getPm()->getOwner()->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]);
        });

        $pm_cache = [];
        foreach ($pm_reports as $report) {
            $seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $this->getUser(), 'report' => $report]) !== null;
            if (!isset($pm_cache[$report->getPm()->getId()]))
                $pm_cache[$report->getPm()->getId()] = [
                    'post' => $report->getPm(), 'count' => 1, 'seen' => $seen ? 1 : 0, 'reporters' => [ $report->getSourceUser() ]
                ];
            else {
                $pm_cache[$report->getPm()->getId()]['count']++;
                $pm_cache[$report->getPm()->getId()]['seen'] += $seen ? 1 : 0;
                $pm_cache[$report->getPm()->getId()]['reporters'][] = $report->getSourceUser();
            }
        }

        if (!$show_bin) $pm_cache = array_filter( $pm_cache, fn($e) => $e['count'] > $e['seen'] );

        return $this->render( 'ajax/admin/reports/pn.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'pn',

            'pms'  => $pm_cache,

            'opt' => $opt,
            'bin_shown' => $show_bin,
        ]));
    }

    /**
     * @param string $opt
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/global/{opt}', name: 'admin_reports_global')]
    public function gpn_reports(string $opt = ''): Response
    {
        $show_bin = $opt === 'bin';

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => $show_bin]);

        /** @var AdminReport[] $gpm_reports */
        $gpm_reports = array_filter($reports, function(AdminReport $r) {
            if ($r->getGpm() === null || $r->getGpm()->getReceiverGroup() === null || $r->getGpm()->getSender() === null) return false;
            return true;
        });

        $gpm_cache = [];
        foreach ($gpm_reports as $report) {
            $seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $this->getUser(), 'report' => $report]) !== null;
            if (!isset($gpm_cache[$report->getGpm()->getId()]))
                $gpm_cache[$report->getGpm()->getId()] = [
                    'post' => $report->getGpm(), 'count' => 1, 'seen' => $seen ? 1 : 0, 'reporters' => [ $report->getSourceUser() ]
                ];
            else {
                $gpm_cache[$report->getGpm()->getId()]['count']++;
                $gpm_cache[$report->getGpm()->getId()]['seen'] += $seen ? 1 : 0;
                $gpm_cache[$report->getGpm()->getId()]['reporters'][] = $report->getSourceUser();
            }
        }

        if (!$show_bin) $gpm_cache = array_filter( $gpm_cache, fn($e) => $e['count'] > $e['seen'] );

        return $this->render( 'ajax/admin/reports/gpn.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'gpn',

            'gpms' => $gpm_cache,

            'opt' => $opt,
            'bin_shown' => $show_bin,
        ]));
    }

    /**
     * @param PermissionHandler $perm
     * @param string $opt
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/woh/{opt}', name: 'admin_reports_blackboard')]
    public function blackboard_reports(PermissionHandler $perm, string $opt = ''): Response
    {
        $show_bin = $opt === 'bin';

        $allowed_forums = [];

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => $show_bin]);

        /** @var AdminReport[] $bb_reports */
        $bb_reports = array_filter($reports, function(AdminReport $r) use (&$allowed_forums, $perm) {
            if ($r->getBlackBoard() === null || $r->getBlackBoard()->getTown()->getForum() === null) return false;
            $tid = $r->getBlackBoard()->getTown()->getForum()->getId();
            if (isset($allowed_forums[$tid])) return $allowed_forums[$tid];
            else return $allowed_forums[$tid] = $perm->checkAnyEffectivePermissions($this->getUser(), $r->getBlackBoard()->getTown()->getForum(), [ForumUsagePermissions::PermissionModerate]);
        });

        $bb_cache = [];
        foreach ($bb_reports as $report) {
            $seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $this->getUser(), 'report' => $report]) !== null;
            if (!isset($bb_cache[$report->getBlackBoard()->getId()]))
                $bb_cache[$report->getBlackBoard()->getId()] = [
                    'board' => $report->getBlackBoard(), 'count' => 1, 'seen' => $seen ? 1 : 0, 'reporters' => [ $report->getSourceUser() ]
                ];
            else {
                $bb_cache[$report->getBlackBoard()->getId()]['count']++;
                $bb_cache[$report->getBlackBoard()->getId()]['seen'] += $seen ? 1 : 0;
                $bb_cache[$report->getBlackBoard()->getId()]['reporters'][] = $report->getSourceUser();
            }
        }

        if (!$show_bin) $bb_cache = array_filter( $bb_cache, fn($e) => $e['count'] > $e['seen'] );

        return $this->render( 'ajax/admin/reports/woh.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'woh',

            'boards'  => $bb_cache,

            'opt' => $opt,
            'bin_shown' => $show_bin,
        ]));
    }

    /**
     * @param string $opt
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/citizen/{opt}', name: 'admin_reports_citizen')]
    public function citizen_reports(string $opt = ''): Response
    {
        $show_bin = $opt === 'bin';

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => $show_bin]);

        /** @var AdminReport[] $c_reports */
        $c_reports = array_filter($reports, function(AdminReport $r) {
            if (!$r->getCitizen()) return false;
            if ($r->getSpecification() === AdminReportSpecification::CitizenAnnouncement && !$r->getCitizen()->getCitizen()) return false;
            return true;
        });

        $c_cache = [];
        foreach ($c_reports as $report) {
            $key = "{$report->getCitizen()->getId()}_{$report->getSpecification()->value}";
            $seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $this->getUser(), 'report' => $report]) !== null;
            if (!isset($c_cache[$key]))
                $c_cache[$key] = [
                    'citizen' => $report->getCitizen(), 'spec' => $report->getSpecification(), 'count' => 1, 'seen' => $seen ? 1 : 0, 'reporters' => [
                        [$report->getSourceUser(), $report->getReason(), $report->getDetails()]
                    ],
                    'content' => match ( $report->getSpecification() ) {
                        AdminReportSpecification::None => null,
                        AdminReportSpecification::CitizenAnnouncement => $report->getCitizen()->getCitizen()->getHome()->getDescription(),
                        AdminReportSpecification::CitizenLastWords => $report->getCitizen()->getLastWords(),
                        AdminReportSpecification::CitizenTownComment => $report->getCitizen()->getComment(),
                    }
                ];
            else {
                $c_cache[$key]['count']++;
                $c_cache[$key]['seen'] += $seen ? 1 : 0;
                $c_cache[$key]['reporters'][] = [$report->getSourceUser(), $report->getReason(), $report->getDetails()];
            }
        }

        if (!$show_bin) $c_cache = array_filter( $c_cache, fn($e) => $e['count'] > $e['seen'] );

        return $this->render( 'ajax/admin/reports/citizen.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'citizen',

            'citizens'  => array_filter($c_cache, fn($e) => $e['content'] !== null ),

            'opt' => $opt,
            'bin_shown' => $show_bin,
        ]));
    }

    /**
     * @param string $opt
     * @return Response
     */
    #[Route(path: 'jx/admin/forum/user/{opt}', name: 'admin_reports_user')]
    public function user_reports(string $opt = ''): Response
    {
        $show_bin = $opt === 'bin';

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => $show_bin]);

        /** @var AdminReport[] $u_reports */
        $u_reports = array_filter($reports, function(AdminReport $r) {
            if (!$r->getUser()) return false;
            return true;
        });

        $u_cache = [];
        foreach ($u_reports as $report) {
            $key = "{$report->getUser()->getId()}";
            $seen = $this->entity_manager->getRepository(ReportSeenMarker::class)->findOneBy(['user' => $this->getUser(), 'report' => $report]) !== null;
            if (!isset($u_cache[$key]))
                $u_cache[$key] = [
                    'user' => $report->getUser(), 'spec' => $report->getSpecification(), 'count' => 1, 'seen' => $seen ? 1 : 0, 'reporters' => [
                        [$report->getSourceUser(), $report->getReason(), $report->getDetails()]
                    ]
                ];
            else {
                $u_cache[$key]['count']++;
                $u_cache[$key]['seen'] += $seen ? 1 : 0;
                $u_cache[$key]['reporters'][] = [$report->getSourceUser(), $report->getReason(), $report->getDetails()];
            }
        }

        if (!$show_bin) $u_cache = array_filter( $u_cache, fn($e) => $e['count'] > $e['seen'] );

        return $this->render( 'ajax/admin/reports/users.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'users',

            'users'  => $u_cache,

            'opt' => $opt,
            'bin_shown' => $show_bin,
        ]));
    }

    /**
     * @param RoleHierarchyInterface $roles
     * @return Response
     */
    #[Route(path: 'jx/admin/com/forum/snippets', name: 'admin_reports_snippets')]
    public function forum_snippets(RoleHierarchyInterface $roles): Response
    {
        $user_roles = $roles->getReachableRoleNames( $this->getUser()->getRoles() );
        $valid_roles = ["ROLE_CROW", "ROLE_ADMIN", "ROLE_ORACLE", "ROLE_ANIMAC"];
        $available_roles = array_intersect(
            $valid_roles,
            $user_roles
        );

        return $this->render( 'ajax/admin/reports/snippets.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => 'short',
            'available_roles' => $this->isGranted('ROLE_ADMIN') ? [...$available_roles, '*'] : $available_roles,
            'snippets' => $this->entity_manager->getRepository(ForumModerationSnippet::class)->findBy(['role' => $user_roles], ['lang' => 'DESC', 'role' => 'DESC', 'short' => 'DESC'])
        ]));
    }
}
