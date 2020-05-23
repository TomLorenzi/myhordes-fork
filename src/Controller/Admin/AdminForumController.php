<?php

namespace App\Controller\Admin;

use App\Entity\AdminReport;
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
class AdminForumController extends AdminActionController
{
    /**
     * @Route("jx/admin/forum/reports/{opt}", name="admin_reports")
     * @param string $opt
     * @return Response
     */
    public function reports(string $opt = ''): Response
    {
        $show_all = $opt === 'all';

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => false]);
        // Make sure to fetch only unseen reports for posts with at least 2 unseen reports
        $postsList = array('post' => [], 'reporter' => []);
        $postsList['post'] = array_map(function($report) { return $report->getPost(); }, $reports);
        $postsList['reporter'] = array_map(function($report) { return $report->getSourceUser(); }, $reports);


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

        return $this->render( 'ajax/admin/reports/reports.html.twig', [
            'posts' => $selectedReports,
            'all_shown' => $show_all
        ]);      
    }

    /**
     * @Route("api/admin/forum/reports/clear", name="admin_reports_clear")
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
}
