<?php

namespace App\Controller\REST\Admin\Forum;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AdminDeletion;
use App\Entity\AntiSpamDomains;
use App\Entity\AttackSchedule;
use App\Entity\Citizen;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\LogEntryTemplate;
use App\Entity\Post;
use App\Entity\User;
use App\Enum\DomainBlacklistType;
use App\Enum\UserAccountType;
use App\Response\AjaxResponse;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\Forum\PostService;
use App\Service\JSONRequestParser;
use App\Service\User\UserAccountService;
use App\Structures\MyHordesConf;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/rest/v1/admin/forum', name: 'rest_admin_forum_management_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_CROW')]
#[GateKeeperProfile('skip')]
class ForumManagementController extends CustomAbstractCoreController
{
    /**
     * @param User $user
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param PostService $postService
     * @return JsonResponse
     */
    #[Route(path: '/user/{id}', name: 'purge_user_posts', methods: ['DELETE'])]
    public function purgeUserPosts(User $user, EntityManagerInterface $em, TranslatorInterface $translator, PostService $postService): JsonResponse {

        $success = 0;
        foreach ($em->getRepository(Post::class)->findBy(['owner' => $user, 'hidden' => false]) as $post)
            if ($postService->hidePost( $post, $this->getUser(), checkModPermissions: false, addCrowAnnounce: false, purgeNotifications: false ))
                $success++;

        $this->addFlash( 'notice', $translator->trans('Es wurden {n} Posts gelöscht.', [
            'n' => $success,
        ], 'admin') );

        return new JsonResponse([ 'success' => $success > 0 ]);
    }

}
