<?php

namespace App\Controller\REST\User;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AdminReport;
use App\Entity\BlackboardEdit;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Enum\AdminReportSpecification;
use App\Service\CrowService;
use App\Service\EventProxyService;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Service\RateLimitingFactoryProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route(path: '/rest/v1/user/complaint', name: 'rest_user_complaint_', condition: "request.headers.get('Accept') === 'application/json'")]
#[GateKeeperProfile('skip')]
class ComplaintController extends CustomAbstractCoreController
{
    private function renderOptions(string $type): array {
        return array_values(array_filter([
            ...( $type !== 'user' ? [
                [1 , $this->translator->trans('Cheating', [], 'global')],
                [2 , $this->translator->trans('Flooding oder Spam', [], 'global')],
                [3 , match ($type) {
                    'forum-post' => $this->translator->trans('Verwendung einer anderen als der Forensprache', [], 'global'),
                    'blackboard', 'citizen-description', 'town-pm' => $this->translator->trans('Verwendung einer anderen als der Stadtsprache', [], 'global'),
                    default => null,
                }],
                [4 , $this->translator->trans('Beleidigungen / Unangemessener Ausdruck', [], 'global')],
                [5 , $this->translator->trans('Pornographie', [], 'global')],
                [6 , $this->translator->trans('Hassrede', [], 'global')],
                [7 , $this->translator->trans('Verbreitung persönlicher Informationen', [], 'global')],
                [8 , $this->translator->trans('Verletzung von Copyright', [], 'global')],
                [9 , $this->translator->trans('Aufruf zu Gesetzesverstößen', [], 'global')],
                [10, $this->translator->trans('Ermutigung von Selbstmord oder Selbstverletzung', [], 'global')],
            ] : [] ),

            ...( $type === 'user' ? [
                [11, $this->translator->trans('Unangemessene Profilbeschreibung', [], 'global')],
                [12, $this->translator->trans('Unangemessener Avatar', [], 'global')],
                [13, $this->translator->trans('Unangemessener Name', [], 'global')],
            ] : [] ),

        ], fn($a) => $a[1] !== null));
    }

    private function getValidReasonsFor(string $type) {
        return array_values( array_map( fn(array $a) => $a[0], $this->renderOptions( $type ) ) );
    }

    private function renderTexts(string $type): array {
        return [
            'ok' => $this->translator->trans('Inhalt melden', [], 'global'),
            'abort' => $this->translator->trans('Abbrechen', [], 'global'),
            'subline' => $this->translator->trans('Wenn möglich gib bitte einen Grund für deine Meldung an:', [], 'global'),
            'additional' => $this->translator->trans('Zusätzliche Informationen zu deiner Meldung (optional):', [], 'global'),
            'error_400' => $this->translator->trans('Bitte fülle das Formular vollständig aus.', [], 'global'),
            'error_404' => $this->translator->trans('Der gemeldete Inhalt wurde nicht gefunden. Möglicherweise wurde er in der Zwischenzeit bereits gelöscht.', [], 'global'),
            'error_409' => $this->translator->trans('Dieser Inhalt kann nicht gemeldet werden.', [], 'global'),
            'error_429' => $this->translator->trans('Du hast zu viele Meldungen in kurzer Zeit abgesendet. Du musst einen Augenblick warten, bevor du weitere Meldungen senden kannst.', [], 'global'),
            ...match($type) {
                'forum-post', 'town-pm', 'global-pm' => [
                    'header' => $this->translator->trans('Bist du sicher, dass du diesen Post an die Moderatoren melden willst?', [], 'global'),
                ],
                'blackboard' => [
                    'header' => $this->translator->trans('Bist du sicher, dass du den Inhalt des Schwarzen Bretts an die Moderatoren melden willst?', [], 'global'),
                ],
                'citizen-description' => [
                    'header' => $this->translator->trans('Bist du sicher, dass du die persönliche Beschreibung dieses Bürgers an die Moderatoren melden willst?', [], 'global'),
                ],
                'citizen-last-words' => [
                    'header' => $this->translator->trans('Bist du sicher, dass du die letzten Worte dieses Bürgers an die Moderatoren melden willst?', [], 'global'),
                ],
                'citizen-town-comment' => [
                    'header' => $this->translator->trans('Bist du sicher, dass du den Stadtkommentar dieses Bürgers an die Moderatoren melden willst?', [], 'global'),
                ],
                'user' => [
                    'header' => $this->translator->trans('Bist du sicher, dass du diesen Spieler an die Moderatoren melden willst?', [], 'global'),
                ],
                default => []
            },
        ];
    }

    /**
     * @param string $type
     * @return JsonResponse
     */
    #[Route(path: '/{type}', name: 'base', methods: ['GET'])]
    public function index(string $type): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'options' => $this->renderOptions( $type ),
                'texts' => $this->renderTexts( $type ),
            ]
        ]);
    }

    /**
     * @param Post $post
     * @param string $type
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param PermissionHandler $perm
     * @param RateLimitingFactoryProvider $rateLimiter
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/forum-post/{id}', name: 'forum_post', defaults: ['type' => 'forum-post'], methods: ['PUT'])]
    public function report_forum_post(Post $post, string $type, JSONRequestParser $parser, EntityManagerInterface $em, PermissionHandler $perm, RateLimitingFactoryProvider $rateLimiter, EventProxyService $proxy): JsonResponse {
        $user = $this->getUser();

        $reason = $parser->get_int('report_reason', 0);
        if (!in_array( $reason, $this->getValidReasonsFor( $type ) ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        if ($post->getTranslate())
            return new JsonResponse([], Response::HTTP_CONFLICT);

        if (!$perm->checkEffectivePermissions($user, $post->getThread()->getForum(), ForumUsagePermissions::PermissionReadThreads))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $targetUser = $post->getOwner();
        if ($targetUser->getId() === 66 )
            return new JsonResponse(
                ['message' =>$this->translator->trans('Das ist keine gute Idee, das ist dir doch wohl klar!', [], 'game') ],
                Response::HTTP_FORBIDDEN
            );

        $message = $this->translator->trans('Du hast die Nachricht von {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...', [
            '{username}' => '<span>' . ($post->isAnonymous() ? '???' : $post->getOwner()->getName()) . '</span>'
        ], 'game');


        $reports = $post->getAdminReports();
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() === $user->getId())
                return new JsonResponse([
                    'message' => $message
                ], Response::HTTP_OK);

        if (!($rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume($reports->isEmpty() ? 2 : 1))->isAccepted())
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);

        $details = $parser->trimmed('report_details');
        $post->addAdminReport(
            $newReport = (new AdminReport())
                ->setSourceUser($user)
                ->setReason( $reason )
                ->setDetails( $details ?: null )
                ->setTs(new DateTime('now'))
        );

        try {
            $em->persist($post);
            $em->persist($newReport);
            $em->flush();
        } catch (Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $proxy->contentReport( $user, $newReport, $post, $post->getAdminReports()->count() );
        } catch (\Throwable $e) {}

        return new JsonResponse([
            'message' => $message
        ], Response::HTTP_CREATED);
    }

    /**
     * @param BlackboardEdit $blackBoardEdit
     * @param string $type
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param PermissionHandler $perm
     * @param RateLimitingFactoryProvider $rateLimiter
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/blackboard/{id}', name: 'blackboard', defaults: ['type' => 'blackboard'], methods: ['PUT'])]
    public function report_blackboard(BlackboardEdit $blackBoardEdit, string $type, JSONRequestParser $parser, EntityManagerInterface $em, PermissionHandler $perm, RateLimitingFactoryProvider $rateLimiter, EventProxyService $proxy): JsonResponse {
        $user = $this->getUser();

        $reason = $parser->get_int('report_reason', 0);
        if (!in_array( $reason, $this->getValidReasonsFor( $type ) ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        if (empty( $blackBoardEdit->getText() ))
            return new JsonResponse([], Response::HTTP_CONFLICT);

        if ($blackBoardEdit->getTown() !== $user->getActiveCitizen()->getTown())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $message = $this->translator->trans('Du hast die Nachricht auf dem Schwarzen Brett an den Raben gemeldet. Wer weiß, vielleicht wird deren Verfasser heute Nacht stääärben...', [], 'game');

        $targetUser =  $blackBoardEdit->getUser();
        if ($targetUser === $user)
            return new JsonResponse([
                                        'message' => $message
                                    ], Response::HTTP_OK);

        $reports = $em->getRepository(AdminReport::class)->findBy(['blackBoard' => $blackBoardEdit]);
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() === $user->getId())
                return new JsonResponse([
                                            'message' => $message
                                        ], Response::HTTP_OK);
        $report_count = count($reports) + 1;

        if (!($rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume())->isAccepted())
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);

        $details = $parser->trimmed('report_details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setReason( $reason )
            ->setDetails( $details ?: null )
            ->setTs(new DateTime('now'))
            ->setBlackBoard( $blackBoardEdit );

        try {
            $em->persist($newReport);
            $em->flush();
        } catch (Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $proxy->contentReport( $user, $newReport, $blackBoardEdit, $report_count );
        } catch (\Throwable $e) {}

        return new JsonResponse([
                                    'message' => $message
                                ], Response::HTTP_CREATED);
    }

    /**
     * @param Citizen|null $citizen_instance
     * @param CitizenRankingProxy|null $citizen_proxy
     * @param string $type
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param RateLimitingFactoryProvider $rateLimiter
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/citizen-description/{cid}', name: 'citizen_description', defaults: ['type' => 'citizen-description'], methods: ['PUT'])]
    #[Route(path: '/citizen-last-words/{ccid}', name: 'citizen_last_words', defaults: ['type' => 'citizen-last-words'], methods: ['PUT'])]
    #[Route(path: '/citizen-town-comment/{ccid}', name: 'citizen_town_comment', defaults: ['type' => 'citizen-town-comment'], methods: ['PUT'])]
    public function report_citizen(
        #[MapEntity(id: 'cid')]
        ?Citizen $citizen_instance,
        #[MapEntity(id: 'ccid')]
        ?CitizenRankingProxy $citizen_proxy,
        string $type, JSONRequestParser $parser, EntityManagerInterface $em, RateLimitingFactoryProvider $rateLimiter, EventProxyService $proxyService): JsonResponse
    {
        $citizen = $citizen_instance ?? $citizen_proxy;
        if (!$citizen) return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $user = $this->getUser();
        $reason = $parser->get_int('report_reason', 0);
        if (!in_array( $reason, $this->getValidReasonsFor( $type ) ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $proxy = is_a( $citizen, CitizenRankingProxy::class ) ? $citizen : $citizen->getRankingEntry();

        $specification = match ($type) {
            'citizen-description' => AdminReportSpecification::CitizenAnnouncement,
            'citizen-last-words' => AdminReportSpecification::CitizenLastWords,
            'citizen-town-comment' => AdminReportSpecification::CitizenTownComment,
        };

        if ($specification === AdminReportSpecification::CitizenAnnouncement && $citizen->getTown() !== $user->getActiveCitizen()?->getTown())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $message = $this->translator->trans('Du hast die Nachricht von {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...', ['{username}' => '<span>' . $proxy->getUser()->getName() . '</span>'], 'game');
        if ($proxy->getUser() === $user)
            return new JsonResponse([
                                        'message' => $message
                                    ], Response::HTTP_OK);

        $payload = match ($specification) {
            AdminReportSpecification::CitizenAnnouncement => $citizen?->getHome()?->getDescription(),
            AdminReportSpecification::CitizenLastWords => $proxy->getLastWords(),
            AdminReportSpecification::CitizenTownComment => $proxy->getComment(),
            default => null
        };

        if (empty( $payload ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $reports = $em->getRepository(AdminReport::class)->findBy(['citizen' => $proxy, 'specification' => $specification->value]);
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() === $user->getId())
                return new JsonResponse([
                                            'message' => $message
                                        ], Response::HTTP_OK);

        $report_count = count($reports) + 1;

        if (!($rateLimiter->reportLimiter( $this->getUser() )->create( $user->getId() )->consume())->isAccepted())
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);

        $details = $parser->trimmed('report_details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setReason( $reason )
            ->setDetails( $details ?: null )
            ->setTs(new \DateTime('now'))
            ->setCitizen( $proxy )
            ->setSpecification( $specification );

        try {
            $em->persist($newReport);
            $em->flush();
        } catch (Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $proxyService->contentReport( $user, $newReport, $proxy, $report_count );
        } catch (Throwable $e) {}

        return new JsonResponse([
                                    'message' => $message
                                ], Response::HTTP_CREATED);
    }

    /**
     * @param User $reportedUser
     * @param string $type
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param RateLimitingFactoryProvider $rateLimiter
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/user/{id}', name: 'user', defaults: ['type' => 'user'], methods: ['PUT'])]
    public function report_user(User $reportedUser, string $type, JSONRequestParser $parser, EntityManagerInterface $em, RateLimitingFactoryProvider $rateLimiter, EventProxyService $proxy): JsonResponse {
        $user = $this->getUser();

        $reason = $parser->get_int('report_reason', 0);
        if (!in_array( $reason, $this->getValidReasonsFor( $type ) ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $message = $this->translator->trans('Du hast {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...', ['{username}' => '<span>' . $reportedUser->getName() . '</span>'], 'game');

        if ($reportedUser === $user)
            return new JsonResponse([
                                        'message' => $message
                                    ], Response::HTTP_OK);

        $reports = $em->getRepository(AdminReport::class)->findBy(['user' => $reportedUser]);

        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() === $user->getId())
                return new JsonResponse([
                                            'message' => $message
                                        ], Response::HTTP_OK);
        $report_count = count($reports) + 1;

        if (!($rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume())->isAccepted())
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);

        $details = $parser->trimmed('report_details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setReason( $reason )
            ->setDetails( $details ?: null )
            ->setTs(new \DateTime('now'))
            ->setUser( $reportedUser );

        try {
            $em->persist($newReport);
            $em->flush();
        } catch (Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $proxy->contentReport( $user, $newReport, $reportedUser, $report_count );
        } catch (Throwable $e) {}

        return new JsonResponse([
                                    'message' => $message
                                ], Response::HTTP_CREATED);
    }

    /**
     * @param PrivateMessage $post
     * @param string $type
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param RateLimitingFactoryProvider $rateLimiter
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/town-pm/{id}', name: 'town_pm', defaults: ['type' => 'town-pm'], methods: ['PUT'])]
    public function report_town_pm(PrivateMessage $post, string $type, JSONRequestParser $parser, EntityManagerInterface $em, RateLimitingFactoryProvider $rateLimiter, EventProxyService $proxy): JsonResponse {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        if (!($citizen = $user->getActiveCitizen()))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $thread = $post->getPrivateMessageThread();
        if (!$thread) return new JsonResponse([], Response::HTTP_NOT_FOUND);

        if (!$thread->getSender() || ($thread->getRecipient()->getId() !== $citizen->getId() && $thread->getSender()->getId() !== $citizen->getId()))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $reason = $parser->get_int('report_reason', 0);
        if (!in_array( $reason, $this->getValidReasonsFor( $type ) ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $message = $this->translator->trans('Du hast die Nachricht von {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...',
                                            ['{username}' => '<span>' . ($post->isAnonymous() ? '???' : $post->getOwner()->getName()) . '</span>'], 'game');

        if ($post->getOwner() === $citizen)
            return new JsonResponse([
                                        'message' => $message
                                    ], Response::HTTP_OK);

        $reports = $post->getAdminReports();

        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() === $user->getId())
                return new JsonResponse([
                                            'message' => $message
                                        ], Response::HTTP_OK);
        $report_count = count($reports) + 1;

        if (!($rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume())->isAccepted())
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);

        $details = $parser->trimmed('report_details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setReason( $reason )
            ->setDetails( $details ?: null )
            ->setTs(new \DateTime('now'))
            ->setPm( $post );

        try {
            $em->persist($newReport);
            $em->flush();
        } catch (Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $proxy->contentReport( $user, $newReport, $post, $report_count );
        } catch (Throwable $e) {}

        return new JsonResponse([
                                    'message' => $message
                                ], Response::HTTP_CREATED);
    }

    /**
     * @param GlobalPrivateMessage $message
     * @param string $type
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param RateLimitingFactoryProvider $rateLimiter
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/global-pm/{id}', name: 'global_pm', defaults: ['type' => 'global-pm'], methods: ['PUT'])]
    public function report_global_pm(GlobalPrivateMessage $message, string $type, JSONRequestParser $parser, EntityManagerInterface $em, RateLimitingFactoryProvider $rateLimiter, EventProxyService $proxy): JsonResponse {
        $user = $this->getUser();

        $group = $message->getReceiverGroup();
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup)
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
                                                                                            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive], 'association' => $group]);
        if (!$group_association)
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $targetUser = $message->getSender();
        if ($targetUser->getName() === "Der Rabe" )
            return new JsonResponse([
                                        'message' => $this->translator->trans('Das ist keine gute Idee, das ist dir doch wohl klar!', [], 'game')
                                    ], Response::HTTP_OK);

        $reason = $parser->get_int('report_reason', 0);
        if (!in_array( $reason, $this->getValidReasonsFor( $type ) ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $success_message = $this->translator->trans('Du hast die Nachricht von {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...', ['{username}' => '<span>' . $message->getSender()->getName() . '</span>'], 'game');

        if ($message->getSender() === $user)
            return new JsonResponse([
                                        'message' => $success_message
                                    ], Response::HTTP_OK);

        $reports = $message->getAdminReports();

        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() === $user->getId())
                return new JsonResponse([
                                            'message' => $success_message
                                        ], Response::HTTP_OK);
        $report_count = count($reports) + 1;

        if (!($rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume())->isAccepted())
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);

        $details = $parser->trimmed('report_details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setReason( $reason )
            ->setDetails( $details ?: null )
            ->setTs(new \DateTime('now'))
            ->setGpm($message);

        try {
            $em->persist($newReport);
            $em->flush();
        } catch (Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $proxy->contentReport( $user, $newReport, $message, $report_count );
        } catch (Throwable $e) {}

        return new JsonResponse([
                                    'message' => $success_message
                                ], Response::HTTP_CREATED);
    }
}
