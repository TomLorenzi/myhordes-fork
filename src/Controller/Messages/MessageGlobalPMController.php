<?php

namespace App\Controller\Messages;

use App\Annotations\GateKeeperProfile;
use App\Entity\AccountRestriction;
use App\Entity\AdminReport;
use App\Entity\Announcement;
use App\Entity\ForumModerationSnippet;
use App\Entity\ForumThreadSubscription;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\OfficialGroup;
use App\Entity\OfficialGroupMessageLink;
use App\Entity\Post;
use App\Entity\SocialRelation;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Response\AjaxResponse;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\PermissionHandler;
use App\Service\RateLimitingFactoryProvider;
use App\Service\UserHandler;
use App\Structures\HTMLParserInsight;
use App\Translation\T;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class MessageGlobalPMController extends MessageController
{
    /**
     * @param EntityManagerInterface $em
     * @param SessionInterface $s
     * @return Response
     */
    #[Route(path: 'api/pm/ping', name: 'api_pm_ping')]
    #[GateKeeperProfile('skip')]
    public function ping_check_new_message(EntityManagerInterface $em, SessionInterface $s): Response {
        $cache = $s->get('cache_ping');

        if ($cache && isset($cache['ts']) && isset($cache['r']) && (new DateTime('-1min')) < $cache['ts'] )
            return new AjaxResponse($cache['r']);

        $user = $this->getUser();
        if (!$user) return new AjaxResponse(['new' => 0, 'connected' => false, 'success' => true]);

        /** @var Collection|ForumThreadSubscription[] $subscriptions */
        $subscriptions = $em->getRepository(ForumThreadSubscription::class)->matching(
            (new Criteria())
                ->andWhere( Criteria::expr()->eq('user', $user) )
                ->andWhere( Criteria::expr()->gt('num', 0))
        );

        if (!empty($subscriptions)) {
            $forums = $this->perm->getForumsWithPermission($user);
            $subscriptions =  $subscriptions->filter(fn(ForumThreadSubscription $s) => !$s->getThread()->getHidden() && in_array($s->getThread()->getForum(), $forums));
        }

        $response = ['new' =>
            count($subscriptions) +
            $em->getRepository(UserGroupAssociation::class)->countUnreadPMsByUser($user, true, true, true) +
            $em->getRepository(UserGroupAssociation::class)->countUnreadInactivePMsByUser($user) +
            $em->getRepository(GlobalPrivateMessage::class)->countUnreadDirectPMsByUser($user) +
            $em->getRepository(Announcement::class)->countUnreadByUser($user, $this->getUserLanguage()),
            'connected' => 60000, 'success' => true];

        $s->set('cache_ping', ['ts' => new DateTime(), 'r' => $response]);

        return new AjaxResponse($response);
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param string $domain
     * @param int $id
     * @param int $archive
     * @return Response
     */
    #[Route(path: 'api/pm/spring/{domain}/{id<\d+>}/{archive<\d>}', name: 'api_pm_spring')]
    public function ping_fetch_new_messages(EntityManagerInterface $em, JSONRequestParser $parser, string $domain = '', int $id = 0, int $archive = 0): Response {

        $user = $this->getUser();
        if (!$user) return new AjaxResponse(['connected' => false, 'success' => true]);
        $rk = $parser->get_int('rk',0);

        if ($rk <= 0 || $rk >= time()) return new AjaxResponse(['connected' => 15000, 'success' => true]);

        if ($id <= 0 || !in_array($domain, ['d','g'])) $domain = '';

        $cutoff = new DateTime();
        $cutoff->setTimestamp( $rk );

        /** @var GlobalPrivateMessage[] $dm_cache */
        $dm_cache = $em->getRepository(GlobalPrivateMessage::class)->getUnreadDirectPMsByUser($user, $cutoff);

        $entries = [];
        $this->render_group_associations( $em->getRepository(UserGroupAssociation::class)->getUnreadPMsByUser($user, $cutoff), $entries );
        $this->render_announcements( $em->getRepository(Announcement::class)->getUnreadByUser($user, $this->getUserLanguage(), $cutoff), $entries );
        $this->render_directNotifications( $dm_cache );

        usort($entries, fn($a,$b) => $b['date'] <=> $a['date']);

        $index = $this->render( 'ajax/pm/bubbles.html.twig', ['raw_id' => $entries] )->getContent();
        $focus = '';

        switch ($domain) {
            case 'd':
                foreach ($dm_cache as $entry) $this->entity_manager->persist( $entry->setSeen(true) );
                try {
                    $this->entity_manager->flush();
                } catch (Exception $e) {}

                foreach ($dm_cache as $entry) $entry->setText( $this->html->prepareEmotes( $entry->getText() ?? '', $this->getUser() ) );
                $focus = $this->render( 'ajax/pm/bubbles.html.twig', ['raw_dm' => $dm_cache] )->getContent();

                break;
            case 'g':

                $group = $em->getRepository( UserGroup::class )->find($id);
                if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) break;

                /** @var UserGroupAssociation $group_association */
                $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' =>
                    [UserGroupAssociation::GroupAssociationTypePrivateMessageMember],
                'association' => $group]);
                if (!$group_association) break;

                $messages = $em->getRepository(GlobalPrivateMessage::class)->findByGroup($group, 0, 0, 0, $cutoff);
                if (!$messages) break;

                try {
                    $this->entity_manager->persist( $group_association->setRef1( $group->getRef1() )->setRef2( $messages[0]->getId() ) );
                    $this->entity_manager->flush();
                } catch (\Exception $e) {}

                foreach ($messages as $message) $message->setText( $this->html->prepareEmotes( $message->getText(), $this->getUser() ) );
                $focus = $this->render( 'ajax/pm/bubbles.html.twig', ['raw_gp' => $messages, 'raw_gp_owner' => $group_association->getAssociationLevel() == UserGroupAssociation::GroupAssociationLevelFounder] )->getContent();

                break;
        }

        return new AjaxResponse(['success' => true, 'response_key' => (new DateTime('now'))->getTimestamp(), 'payload' => [
            'connected' => 60000,
            'index' => $index,
            'focus' => $focus,
        ]]);
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/pm', name: 'pm_proxy_view')]
    public function pm_proxy_view(): Response {
        return $this->render( 'ajax/pm/proxy.html.twig', $this->addDefaultTwigArgs());
    }


    /**
     * @param string|null $s
     * @return Response
     */
    #[Route(path: 'jx/pm/short/{s}', name: 'pm_proxy_view_short')]
    public function pm_proxy_view_short(?string $s = null): Response {
        return $this->render( 'ajax/pm/proxy.html.twig', $this->addDefaultTwigArgs(null,['command' => $s]));
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'jx/pm/view', name: 'pm_view')]
    public function pm_view(JSONRequestParser $parser): Response {
        $target = Request::createFromGlobals()->headers->get('X-Render-Target', '');

        if ($target === 'post-office-content') {
            return $this->render( 'ajax/pm/view.html.twig', [
                'rk' => (new DateTime('now'))->getTimestamp(),
                'command' => $parser->get('command'),
                'official_groups' => array_filter( $this->entity_manager->getRepository(OfficialGroup::class)->findAll(), fn(OfficialGroup $o) => $this->perm->userInGroup($this->getUser(), $o->getUsergroup()) ),
                'has_forum_notif' => $this->entity_manager->getRepository(ForumThreadSubscription::class)->count(['user' => $this->getUser()]) > 0,
            ]);
        }

        return $this->pm_proxy_view();
    }

    /**
     * @param UserGroupAssociation[] $group_associations
     * @param array|null $entries
     */
    private function render_group_associations(array $group_associations, ?array &$entries = null): void {
        if ($entries === null) $entries = [];

        foreach ($group_associations as $association) {
            $official_meta = $this->entity_manager->getRepository(OfficialGroupMessageLink::class)->findOneBy(['messageGroup' => $association->getAssociation()]);

            $has_response = $association->getAssociationType() === UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember &&
                $this->entity_manager->getRepository(GlobalPrivateMessage::class)->lastInGroup($association->getAssociation())->getSenderGroup() !== null;

            $owner_assoc = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy([
                'association' => $association->getAssociation(),
                'associationLevel' => $official_meta ? UserGroupAssociation::GroupAssociationLevelDefault : UserGroupAssociation::GroupAssociationLevelFounder,
                'associationType' => $official_meta ? [UserGroupAssociation::GroupAssociationTypePrivateMessageMember,UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive] : UserGroupAssociation::GroupAssociationTypePrivateMessageMember,
            ]);

            $read_only = $association->getAssociationType() === UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive;

            $last_post_date = new DateTime();
            if ($read_only) {
                $last_readable = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->find( $association->getRef4() );
                $last_post_date->setTimestamp((!$last_readable || $last_readable->getReceiverGroup() !== $association->getAssociation())
                    ? $association->getAssociation()->getRef2()
                    : $last_readable->getTimestamp()->getTimestamp()
                );
            } else $last_post_date->setTimestamp($association->getAssociation()->getRef2());

            $entries[] = [
                'obj'    => $association,
                'date'   => $last_post_date,
                'pinned' => $association->getPriority(),
                'system' => false,
                'response' => $has_response,
                'archive' => $association->getBref(),
                'official' => $official_meta ? $official_meta->getOfficialGroup() : null,
                'title'  => $association->getAssociation()->getName(),
                'closed' => $association->getAssociationType() === UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive,
                'count'  => $read_only ? $association->getRef3() : $association->getAssociation()->getRef1(),
                'unread' => $read_only ? ($association->getRef3() - $association->getRef1()) : ($association->getAssociation()->getRef1() - $association->getRef1()),
                'owner'  => ($owner_assoc && $owner_assoc->getUser()) ? $owner_assoc->getUser() : null,
                'users'  => array_map(fn(UserGroupAssociation $a) => $a->getUser(), $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                    'association' => $association->getAssociation(),
                    'associationType' =>  UserGroupAssociation::GroupAssociationTypePrivateMessageMember]
                ))
            ];
        }
    }

    /**
     * @param Announcement[] $announcements
     * @param array|null $entries
     */
    private function render_announcements( array $announcements, ?array &$entries = null ): void {
        if ($entries === null) $entries = [];

        foreach ($announcements as $announcement) {

            $entries[] = [
                'obj'    => $announcement,
                'date'   => $announcement->getTimestamp(),
                'pinned' => 0,
                'system' => false,
                'archive' => false,
                'official' => null,
                'title'  => $announcement->getTitle(),
                'closed' => false,
                'count'  => 1,
                'unread' => ($announcement->getTimestamp() < new DateTime('-60days') || $announcement->getReadBy()->contains( $this->getUser() )) ? 0 : 1,
                'owner'  => $announcement->getSender(),
                'users'  => [$this->getUser(), $announcement->getSender()]
            ];

        }
    }

    /**
     * @param array|null $entries
     * @param int[] $skip
     */
    private function render_forumNotifications( ?array &$entries = null, array $skip = [], ?string $query = null ): void {
        if ($entries === null) $entries = [];

        /** @var Collection|ForumThreadSubscription[] $subscriptions */
        $subscriptions = $this->entity_manager->getRepository(ForumThreadSubscription::class)->matching(
            (new Criteria())
                ->andWhere( Criteria::expr()->eq('user', $this->getUser()) )
                ->andWhere( Criteria::expr()->gt('num', 0))
        );

        if (!empty($subscriptions)) {
            $forums = $this->perm->getForumsWithPermission($this->getUser());
            $subscriptions =  $subscriptions->filter(fn(ForumThreadSubscription $s) => !$s->getThread()->getHidden() && !in_array($s->getThread()->getId(), $skip) && ($query === null || mb_strpos( mb_strtolower($s->getThread()->getTitle()), mb_strtolower( $query ) ) !== false) && in_array($s->getThread()->getForum(), $forums));
        }

        foreach ($subscriptions as $subscription) {
            $user_cache = [$this->getUser()];
            $users = array_filter( array_reverse( array_map( fn(Post $p) => $p->getOwner(), array_filter(
                $this->entity_manager->getRepository(Post::class)->findBy(['thread' => $subscription->getThread()], ['date' => 'DESC'], $subscription->getNum()),
                fn(Post $p) => !$p->getHidden() && $p->getOwner() !== $this->getUser(),
            ))), function (User $u) use (&$user_cache) {
                if (in_array($u, $user_cache)) return false;
                $user_cache[] = $u;
                return true;
            });

            $users[] = $this->getUser();

            $entries[] = [
                'obj'    => $subscription->getThread(),
                'date'   => new DateTime(),
                'pinned' => 0,
                'system' => false,
                'archive' => false,
                'official' => null,
                'title'  => $subscription->getThread()->getTranslatable()
                        ? $this->translator->trans($subscription->getThread()->getTitle(), [], 'game') : $subscription->getThread()->getTitle()
                ,
                'closed' => false,
                'count'  => $subscription->getNum(),
                'unread' => $subscription->getNum(),
                'owner'  => $this->getUser(),
                'users'  => $users
            ];
        }
    }

    /**
     * @param GlobalPrivateMessage[] $pms
     * @param array|null $entries
     */
    private function render_directNotifications( array $pms, ?array &$entries = null ): void {
        if ($entries === null) $entries = [];

        $latest_pm = empty($pms) ? null : $pms[0];
        $unread = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->countUnreadDirectPMsByUser($this->getUser());

        if ($latest_pm) {
            $crow = $this->entity_manager->getRepository(User::class)->find(66);
            $entries[] = [
                'obj'    => $latest_pm,
                'date'   => $latest_pm->getTimestamp(),
                'pinned' => $unread > 0 ? 200 : 0,
                'system' => true,
                'official' => null,
                'title'  => $this->translator->trans('Nachrichten des Raben', [], 'global'),
                'closed' => false,
                'count'  => $this->entity_manager->getRepository(GlobalPrivateMessage::class)->count(['receiverUser' => $this->getUser(), 'receiverGroup' => null]),
                'unread' => $unread,
                'owner'  => $crow,
                'users'  => [$this->getUser(),$crow]
            ];

        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @param string $set
     * @return Response
     */
    #[Route(path: 'jx/pm/list/{set}', name: 'pm_list')]
    public function pm_load_list(EntityManagerInterface $em, JSONRequestParser $p, string $set = 'inbox'): Response {
        $entries = [];

        if (!in_array($set,['inbox','archive','support','forum','announcements'])) return new Response('');

        $group_filter = match($set) {
            'support' => [ UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember ],
            'archive' => [ UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember ],
            'inbox'   => [ UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive ],
            default   => []
        };

        $skip = $p->get_array('skip');
        $num = max(5,min(30,$p->get_int('num', 30)));

        $query = $p->get('filter');
        $user_filter = $p->get_num('user', 0);
        if ($user_filter <= 0) $user_filter = null;

        if (!empty($group_filter))
            $this->render_group_associations( $em->getRepository(UserGroupAssociation::class)->findByUserAssociation($this->getUser(), $group_filter,
                    $skip['g'] ?? [], $num+1, $set === 'archive', $query, $user_filter), $entries );

        if ($set === 'announcements' || ($set === 'archive'))
            $this->render_announcements( $em->getRepository(Announcement::class)->findByLang($this->getUserLanguage(),
                                                                                         $skip['a'] ?? [], $num+1, $set === 'archive', $query), $entries );
        if ($set === 'inbox' && $query === null && $user_filter <= 0) {
            if (empty($skip['d'])) $this->render_directNotifications($this->entity_manager->getRepository(GlobalPrivateMessage::class)->getDirectPMsByUser($this->getUser(), 0, 1), $entries);
        }

        if ($set === 'forum')
            $this->render_forumNotifications($entries, $skip['f'] ?? [], $query );

        usort($entries, fn($a,$b) => $b['pinned'] <=> $a['pinned'] ?: $b['date'] <=> $a['date']);

        return $this->render( 'ajax/pm/list.html.twig', $this->addDefaultTwigArgs(null, [
            'more' => count($entries) > $num,
            'entries' => array_slice($entries,0,$num)
        ] ));
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/pm/folders/state', name: 'pm_check_folder_states')]
    public function check_folder_states( EntityManagerInterface $em, JSONRequestParser $parser ): Response {

        $folders = array_unique( $parser->get_array('folders') );
        $user = $this->getUser();

        $return = [];
        foreach ($folders as $folder)
            switch ($folder) {
                case 'inbox':
                    $return[$folder] =
                        $em->getRepository(UserGroupAssociation::class)->countUnreadPMsByUser($user, true, false) +
                        $em->getRepository(UserGroupAssociation::class)->countUnreadInactivePMsByUser($user) +
                        $em->getRepository(GlobalPrivateMessage::class)->countUnreadDirectPMsByUser($user);
                    break;
                case 'support':
                    $return[$folder] =
                        $em->getRepository(UserGroupAssociation::class)->countUnreadPMsByUser($user, false, true, true);
                    break;
                case 'announcements':
                    $return[$folder] = $em->getRepository(Announcement::class)->countUnreadByUser($user, $this->getUserLanguage());
                    break;
                case 'forum':
                    /** @var Collection|ForumThreadSubscription[] $subscriptions */
                    $subscriptions = $em->getRepository(ForumThreadSubscription::class)->matching(
                        (new Criteria())
                            ->andWhere( Criteria::expr()->eq('user', $user) )
                            ->andWhere( Criteria::expr()->gt('num', 0))
                    );

                    if (!empty($subscriptions)) {
                        $forums = $this->perm->getForumsWithPermission($user);
                        $subscriptions =  $subscriptions->filter(fn(ForumThreadSubscription $s) => !$s->getThread()->getHidden() && in_array($s->getThread()->getForum(), $forums));
                    }

                    $return[$folder] = count($subscriptions);
                    break;
                default:
                    $return[$folder] = 0;
                    break;
            }
        return AjaxResponse::success(true, ['folders' => $return]);
    }

    /**
     * @param int $gid
     * @param int $uid
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/{gid<\d+>}/user/{uid<\d+>}/kick', name: 'pm_conv_group_user_kick')]
    public function pm_conversation_group_user_kick(int $gid, int $uid, EntityManagerInterface $em, PermissionHandler $perm): Response {

        if ($uid === $this->getUser()->getId()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $group = $em->getRepository( UserGroup::class )->find($gid);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' =>
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, 'associationLevel' => UserGroupAssociation::GroupAssociationLevelFounder
        , 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $other_user = $em->getRepository(User::class)->find($uid);
        if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $other_association */
        $other_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $other_user, 'associationType' =>
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, 'association' => $group]);
        if (!$other_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($other_association->getRef1() === null)
            $perm->disassociate( $other_user, $group );
        else {
            $messages = $em->getRepository(GlobalPrivateMessage::class)->findByGroup($group, 0, 1);

            $em->persist($other_association
                             ->setAssociationType(UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive)
                             ->setRef3( $group->getRef1() )->setRef4( empty($messages) ? 1 : $messages[0]->getId() )
            );
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $gid
     * @param int $uid
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/{gid<\d+>}/user/{uid<\d+>}/restore', name: 'pm_conv_group_user_restore')]
    public function pm_conversation_group_user_restore(int $gid, int $uid, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($gid);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' =>
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, 'associationLevel' => UserGroupAssociation::GroupAssociationLevelFounder
                                                                                            , 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $other_user = $em->getRepository(User::class)->find($uid);
        if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $other_association */
        $other_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $other_user, 'associationType' =>
            UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive, 'association' => $group]);
        if (!$other_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $em->persist($other_association
             ->setAssociationType(UserGroupAssociation::GroupAssociationTypePrivateMessageMember)
             ->setRef3( null )->setRef4( null )
        );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $gid
     * @param int $uid
     * @param EntityManagerInterface $em
     * @param UserHandler $userHandler
     * @param PermissionHandler $perm
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/{gid<\d+>}/user/{uid<\d+>}/add', name: 'pm_conv_group_user_add')]
    public function pm_conversation_group_user_add(int $gid, int $uid, EntityManagerInterface $em, UserHandler $userHandler, PermissionHandler $perm): Response {

        if ($userHandler->isRestricted($this->getUser(), AccountRestriction::RestrictionGlobalCommunication))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $group = $em->getRepository( UserGroup::class )->find($gid);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember,UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember], 'association' => $group]);
        if (!$group_association || ($group_association->getAssociationType() !== UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember && $group_association->getAssociationLevel() !== UserGroupAssociation::GroupAssociationLevelFounder))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $member_count = $em->getRepository(UserGroupAssociation::class)->count(['association' => $group]);
        if ($member_count >= 100) return AjaxResponse::error( self::ErrorGPMMemberLimitHit);

        $other_user = $em->getRepository(User::class)->find($uid);
        if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if ($userHandler->hasRole($other_user, 'ROLE_DUMMY')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($userHandler->checkRelation($other_user,$this->getUser(),SocialRelation::SocialRelationTypeBlock))
            return AjaxResponse::error(ErrorHelper::ErrorBlockedByUser);

        /** @var UserGroupAssociation $other_association */
        $other_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $other_user,'association' => $group]);
        if ($other_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $perm->associate($other_user, $group, UserGroupAssociation::GroupAssociationTypePrivateMessageMember);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }


    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/pm/conversation/group/{id<\d+>}/users', name: 'pm_conv_group_users')]
    public function pm_user_list(int $id, EntityManagerInterface $em): Response {
        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return new Response('not found');

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember,UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember], 'association' => $group]);
        if (!$group_association) return new Response('not found');

        $all_associations = $em->getRepository(UserGroupAssociation::class)->findBy(['associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], 'association' => $group]);

        if ($og_link = $this->entity_manager->getRepository(OfficialGroupMessageLink::class)->findOneBy(['messageGroup' => $group]))
            $og_link = $og_link->getOfficialGroup();

        $oa = array_filter( array_map( fn(UserGroupAssociation $a): ?User => $a->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder ? $a->getUser() : null, $all_associations ) );
        $oa = count($oa) === 1 ? $oa[0] : null;

        return $this->render( 'ajax/pm/user_list.html.twig', $this->addDefaultTwigArgs(null, [
            'gid' => $id,
            'owner' => $og_link
                ? $group_association->getAssociationType() === UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember
                : $group_association->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder,
            'owning_user' => $oa,
            'can_add' => count($all_associations) < 100,
            'active'   => array_filter( array_map( fn(UserGroupAssociation $a): ?User => $a->getAssociationType() === UserGroupAssociation::GroupAssociationTypePrivateMessageMember ? $a->getUser() : null, $all_associations ) ),
            'inactive' => array_filter( array_map( fn(UserGroupAssociation $a): ?User => $a->getAssociationType() !== UserGroupAssociation::GroupAssociationTypePrivateMessageMember ? $a->getUser() : null, $all_associations ) ),
            'og' => $og_link
        ] ));
    }


    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @param SessionInterface $s
     * @return Response
     */
    #[Route(path: 'jx/pm/conversation/group/{id<\d+>}', name: 'pm_conv_group')]
    public function pm_conversation_group(int $id, EntityManagerInterface $em, JSONRequestParser $p, SessionInterface $s): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return new Response('not found');

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember
        ], 'association' => $group]);
        if (!$group_association) return new Response('not found');

        $read_only = $group_association->getAssociationType() === UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive;

        $num = max(5,min($p->get('num', 5),30));
        $last_id = $p->get('last', 0);
        $nb_id = $read_only ? $group_association->getRef4() : 0;

        $messages = $em->getRepository(GlobalPrivateMessage::class)->findByGroup($group, $last_id, $num + 1, $nb_id);
        if (!$messages) return new Response('no messages');

        $last = $group_association->getRef2();

        try {
            $s->remove('cache_ping');
            $this->entity_manager->persist( $group_association->setRef1( $read_only ? $group_association->getRef3() : $group->getRef1() )->setRef2( $messages[0]->getId() ) );
            $this->entity_manager->flush();
        } catch (\Exception $e) {}

        foreach ($messages as $message) $message->setText( $this->html->prepareEmotes( $message->getText(), $this->getUser() ) );

        /** @var GlobalPrivateMessage[] $sliced */
        $sliced = array_slice($messages, 0, $num);

        $pinned = $last_id === 0
            ? $em->getRepository(GlobalPrivateMessage::class)->findOneBy(['receiverGroup' => $group, 'pinned' => true])
            : null;

        if ($read_only && $pinned?->getId() > $group_association->getRef4()) $pinned = null;

        if ($pinned)  {
            $rendered = false;
            foreach ($messages as $message) if ($message === $pinned) $rendered = true;
            if (!$rendered) $pinned->setText( $this->html->prepareEmotes( $pinned->getText(), $this->getUser() ) );
        }

        return $this->render( 'ajax/pm/conversation_group.html.twig', $this->addDefaultTwigArgs(null, [
            'gid' => $id,
            'owner' => $group_association->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder,
            'last' => $last,
            'more' => count($messages) > $num,
            'pinned' => $pinned,
            'messages' => $sliced,
            'last_message' => $sliced[array_key_last($sliced)]->getId()
        ] ));
    }

    /**
     * @param EntityManagerInterface $em
     * @param LogTemplateHandler $th
     * @param JSONRequestParser $p
     * @param SessionInterface $s
     * @return Response
     */
    #[Route(path: 'jx/pm/conversation/dm', name: 'pm_dm')]
    public function pm_direct_messages(EntityManagerInterface $em, LogTemplateHandler $th, JSONRequestParser $p, SessionInterface $s): Response {

        $num = max(5,min($p->get('num', 5),30));
        $last_id = $p->get('last', 0);

        /** @var GlobalPrivateMessage[] $messages */
        $messages = $em->getRepository(GlobalPrivateMessage::class)->getDirectPMsByUser($this->getUser(), $last_id, $num + 1);
        if (!$messages) return new Response('no messages');

        $sliced = array_slice($messages, 0, $num);

        $update = false;
        $seen_map = [];
        foreach ($sliced as $message)
            if (!$message->getSeen()) {
                $seen_map[] = $message->getId();
                $message->setSeen($update = true);
                $em->persist($message);
            }

        if ($update) try {
            $s->remove('cache_ping');
            $this->entity_manager->flush();
        } catch (\Exception $e) {}

        foreach ($sliced as $message) {
            $tx = '';
            if ($message->getTemplate() === null && $message->getText()) $tx .= $this->html->prepareEmotes($message->getText(), $this->getUser());

            if ($message->getTemplate())
                try {
                    $tx .= $this->translator->trans(
                        $message->getTemplate()->getText(), $th->parseTransParams($message->getTemplate()->getVariableTypes(), $message->getData()), 'game'
                    ) . $th->processAmendment( $message->getTemplate(), $message->getData() );
                }
                catch (\Exception $e) { $tx .= '_TEMPLATE_ERROR_'; }

            $message->setText($tx);
            if (in_array($message->getId(),$seen_map)) $message->setSeen(false);
        }

        return $this->render( 'ajax/pm/dm.html.twig', $this->addDefaultTwigArgs(null, [
            'last' => $sliced[array_key_last($sliced)]->getId(),
            'more' => count($messages) > $num,
            'messages' => $sliced,
            'last_message' => $sliced[array_key_last($sliced)]->getId()
        ] ));
    }


    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @param SessionInterface $s
     * @return Response
     */
    #[Route(path: 'jx/pm/conversation/announce/{id<\d+>}', name: 'pm_announce')]
    public function pm_announcement(int $id, EntityManagerInterface $em, SessionInterface $s): Response {
        $announce = $em->getRepository( Announcement::class )->find($id);
        if (!$announce || $announce->getLang() != $this->getUserLanguage()) return new Response('not found');

        $new = !$announce->getReadBy()->contains($this->getUser());
        if ($new)
            try {
                $s->remove('cache_ping');
                $announce->getReadBy()->add($this->getUser());
                $this->entity_manager->persist( $announce );
                $this->entity_manager->flush();
            } catch (\Exception $e) {}

        $announce->setText( $this->html->prepareEmotes( $announce->getText(), $announce->getSender() ) );

        return $this->render( 'ajax/pm/announcement.html.twig', $this->addDefaultTwigArgs(null, [
            'announcements' => [$announce],
            'new' => $new,
            'more' => false
        ] ));
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param SessionInterface $s
     * @return Response
     */
    #[Route(path: 'jx/pm/conversation/announce/all', name: 'pm_announce_all')]
    public function pm_announcement_all(EntityManagerInterface $em, JSONRequestParser $parser, SessionInterface $s): Response {
        $skip = $parser->get_array('skip');
        $num = max(1,min(10,$parser->get_int('num', 5)));

        /** @var Announcement[] $announces */
        $announces = $em->getRepository( Announcement::class )->findByLang($this->getUserLanguage(), $skip, $num + 1 );

        $sliced = array_slice($announces, 0, $num);

        $new = false;
        foreach ($sliced as $announce)
            if (!$announce->getReadBy()->contains($this->getUser())) {
                $new = true;
                $announce->getReadBy()->add($this->getUser());
                $this->entity_manager->persist($announce);
            }

        if ($new)
            try {
                $s->remove('cache_ping');
                $this->entity_manager->flush();
            } catch (\Exception $e) {}

        foreach ($sliced as $announce)
            $announce->setText( $this->html->prepareEmotes( $announce->getText(), $announce->getSender() ) );

        return $this->render( 'ajax/pm/announcement.html.twig', $this->addDefaultTwigArgs(null, [
            'announcements' => $sliced,
            'new' => $new,
            'more' => count($announces) > $num
        ] ));
    }

    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @param PermissionHandler $perm
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/{id<\d+>}/delete', name: 'pm_delete_conv_group')]
    public function pm_delete_conversation_group(int $id, EntityManagerInterface $em, PermissionHandler $perm): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($em->getRepository(OfficialGroupMessageLink::class)->findOneBy(['messageGroup' => $group]))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $num_of_assocs = $em->getRepository(UserGroupAssociation::class)->count(['association' => $group]);

        $perm->disassociate( $this->getUser(), $group );
        if ($num_of_assocs < 2) $this->entity_manager->remove( $group );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/unread/{id<\d+>}', name: 'pm_unread_conv_group')]
    public function pm_unread_conversation_group(int $id, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember
        ], 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $this->entity_manager->persist( $group_association->setRef1(0)->setRef2(null) );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param int $arch
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/archive/{id<\d+>}/{arch<\d>}', name: 'pm_archive_conv_group')]
    public function pm_archive_conversation_group(int $id, int $arch, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember
        ], 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $this->entity_manager->persist( $group_association->setBref($arch !== 0) );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param int $pin
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/pin/{id<\d+>}/{pin<\d>}', name: 'pm_pin_conv_group')]
    public function pm_pin_conversation_group(int $id, int $pin, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember
        ], 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $this->entity_manager->persist( $group_association->setPriority($pin !== 0 ? 100 : 0) );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/group/block/{id<\d+>}', name: 'pm_block_conv_group')]
    public function pm_block_conversation_group(int $id, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], 'association' => $group]);
        if (!$group_association || $group_association->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $owner_assoc = $em->getRepository(UserGroupAssociation::class)->findOneBy(['associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], 'association' => $group, 'associationLevel' => UserGroupAssociation::GroupAssociationLevelFounder]);

        if (!$owner_assoc || $owner_assoc->getUser() === $this->getUser() || $this->userHandler->hasRole( $owner_assoc->getUser(), 'ROLE_CROW' ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($this->userHandler->checkRelation($this->getUser(), $owner_assoc->getUser(), SocialRelation::SocialRelationTypeBlock))
            return AjaxResponse::success();

        $this->entity_manager->persist( (new SocialRelation())->setOwner($this->getUser())->setRelated($owner_assoc->getUser())->setType( SocialRelation::SocialRelationTypeBlock ) );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/dm/delete', name: 'pm_delete_dm')]
    public function pm_delete_dm(EntityManagerInterface $em): Response {

        foreach ($em->getRepository(GlobalPrivateMessage::class)->getDirectPMsByUser( $this->getUser() ) as $dm)
            $em->remove($dm);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/dm/unread', name: 'pm_unread_dm')]
    public function pm_unread_dm(EntityManagerInterface $em): Response {
        $dm = $em->getRepository(GlobalPrivateMessage::class)->getDirectPMsByUser( $this->getUser(), 0, 1 );

        if ($dm) {
            $em->persist( $dm[0]->setSeen(false) );
            try {
                $em->flush();
            } catch (\Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/conversation/announce/unread/{id<\d+>}', name: 'pm_unread_announce')]
    public function pm_unread_announcement(int $id, EntityManagerInterface $em): Response {

        $announce = $em->getRepository( Announcement::class )->find($id);
        if (!$announce || $announce->getLang() !== $this->getUserLanguage())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($announce->getReadBy()->contains($this->getUser())) {
            $announce->getReadBy()->removeElement($this->getUser());
            $this->entity_manager->persist( $announce );

            try {
                $em->flush();
            } catch (\Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/pm/og_resolve', name: 'pm_og_resolve')]
    public function editor_og_group_preview(JSONRequestParser $parser) {
        if (!$parser->has('og')) return new Response("");
        $group = $this->entity_manager->getRepository(OfficialGroup::class)->find($parser->get_int('og'));
        if (!$group) return new Response("");

        return $this->render( 'ajax/pm/og.html.twig', ['group' => $group]);
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/pm/user_resolve', name: 'pm_user_resolve')]
    public function editor_user_preview(JSONRequestParser $parser) {
        if (!$parser->has('user')) return new Response("");
        $user = $this->entity_manager->getRepository(User::class)->find($parser->get_int('user'));
        if (!$user) return new Response("");

        return $this->render( 'ajax/pm/user.html.twig', ['user' => $user]);
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/pm/create-editor', name: 'pm_thread_editor_controller')]
    public function editor_pm_thread_api(EntityManagerInterface $em): Response {
        if ($this->userHandler->isRestricted($this->getUser(), AccountRestriction::RestrictionGlobalCommunication))
            return new Response("");

        if ($em->getRepository(UserGroupAssociation::class)->countRecentRecipients($this->getUser()) > 100)
            return $this->render( 'ajax/pm/non-editor.html.twig');

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionCreateThread ),
            'snippets' => $this->isGranted('ROLE_CROW') ? $this->entity_manager->getRepository(ForumModerationSnippet::class)->findAll() : [],

            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'username' => $this->getUser()->getName(),
            'forum' => false,
            'town_controls' => null,

            'type' => 'global-pm',
            'target_url' => 'pm_new_thread_controller',
            'langsCodes' => $this->generatedLangsCodes
        ] );
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/pm/create-og-editor', name: 'pm_og_thread_editor_controller')]
    public function editor_pm_og_thread_api(EntityManagerInterface $em): Response {

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionCreateThread ),
            'snippets' => $this->isGranted('ROLE_CROW') ? $this->entity_manager->getRepository(ForumModerationSnippet::class)->findAll() : [],

            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'username' => $this->getUser()->getName(),
            'forum' => false,
            'town_controls' => null,

            'type' => 'global-og-pm',
            'target_url' => 'pm_new_og_thread_controller',
            'langsCodes' => $this->generatedLangsCodes
        ] );
    }

    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/pm/answer-editor/{id<\d+>}', name: 'pm_post_editor_controller')]
    public function editor_pm_post_api(int $id, EntityManagerInterface $em): Response {
        if ($this->userHandler->isRestricted($this->getUser(), AccountRestriction::RestrictionGlobalCommunication))
            return new Response("");

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionCreatePost ),
            'snippets' => $this->isGranted('ROLE_CROW') ? $this->entity_manager->getRepository(ForumModerationSnippet::class)->findAll() : [],

            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'username' => $this->getUser()->getName(),
            'forum' => false,
            'town_controls' => null,

            'type' => 'global-pm',
            'target_url'  => 'pm_new_post_controller',
            'target_data' => ['id' => $id],
            'langsCodes' => $this->generatedLangsCodes
        ] );
    }

    /**
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param UserHandler $userHandler
     * @param PermissionHandler $perm
     * @param EventProxyService $proxy
     * @return Response
     */
    #[Route(path: 'api/pm/post', name: 'pm_new_thread_controller')]
    public function new_thread_api(JSONRequestParser $parser, EntityManagerInterface $em, UserHandler $userHandler, PermissionHandler $perm, EventProxyService $proxy): Response {

        $user = $this->getUser();
        if ($this->userHandler->isRestricted( $user, AccountRestriction::RestrictionGlobalCommunication ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['title','content'], true))
            return AjaxResponse::error(self::ErrorPostTitleTextMissing);
        if (!$parser->has_all(['users'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user_ids = $parser->get('users');
        array_map( fn($u) => (int)$u, is_array($parser->get('users')) ? $parser->get('users') : [] );
        $users = $this->entity_manager->getRepository(User::class)->findBy(['id' => $user_ids]);

        $sender_as = $parser->trimmed('sender');
        if ($sender_as) {
            list($modal,$id) = explode('-', $sender_as);
            switch ($modal) {
                case 'u':
                    if ((int)$id !== $user->getId()) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
                    break;
                case 'og':
                    $official_group = $this->entity_manager->getRepository(OfficialGroup::class)->find( (int)$id );
                    if (!$official_group || !$this->perm->userInGroup($user, $official_group->getUsergroup()))
                        return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
                    return $this->new_og_thread_api($parser,$em,$perm,$proxy,(int)$id,$users);
            }
        }

        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('content');

        if (count($user_ids) !== count($users)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (count($users) > 100) return AjaxResponse::error( self::ErrorGPMMemberLimitHit);

        $self_pm = count($users) === 1 && $users[0] === $user;

        foreach ($users as $chk_user)
            if ($userHandler->hasRole($chk_user, 'ROLE_DUMMY')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var User[] $blocked_users */
        $blocked_users = [];
        $valid_non_blocked = 0;
        $users = array_filter($users, function(User $chk_user) use ($user,&$valid_non_blocked,&$blocked_users) {
            if ($chk_user === $user) {
                $valid_non_blocked++;
                return true;
            }
            if ($this->userHandler->checkRelation($chk_user,$user,SocialRelation::SocialRelationTypeBlock)) {
                $blocked_users[] = $chk_user;
                return false;
            }

            $valid_non_blocked++;
            return true;
        });

        if ($valid_non_blocked === 0) return AjaxResponse::error(ErrorHelper::ErrorBlockedByUser);

        if (!$self_pm && $em->getRepository(UserGroupAssociation::class)->countRecentRecipients($user) > 100)
            return AjaxResponse::error( self::ErrorGPMThreadLimitHit);

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)  return AjaxResponse::error( self::ErrorPostTitleLength );
        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $ts = new DateTime();

        $pg = (new UserGroup())->setType(UserGroup::GroupMessageGroup)->setName( $title )->setRef1(1)->setRef2( $ts->getTimestamp() )->setRef3( $ts->getTimestamp() );
        $this->entity_manager->persist($pg);

        $perm->associate( $user, $pg, UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationLevelFounder, $pg->getRef1());
        foreach ($users as $chk_user)
            if ($user !== $chk_user) $perm->associate( $chk_user, $pg, UserGroupAssociation::GroupAssociationTypePrivateMessageMember);

        $post = (new GlobalPrivateMessage())
            ->setSender($user)->setTimestamp($ts)->setReceiverGroup($pg)->setText($text);

        /** @var HTMLParserInsight $insight */
        if (!$this->preparePost($user,null,$post, null, $insight))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest, ['a' => 10] );
        if ($insight->text_length < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        $this->entity_manager->persist( $post );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $proxy->globalPrivateMessageNewPostEvent( $post, $insight, true );

        if (!empty($blocked_users)) {
            if (count($blocked_users) === 1)
                $this->addFlash('error', $this->translator->trans('{user} hat dich geblockt und wurde daher aus der Liste der Empfänger für diese Nachricht gestrichen.',['{user}' => $blocked_users[0]->getName()],'global'));
            else {
                $users_text = $this->translator->trans('{users} und {last_user}', ['{users}' => implode( ', ', array_map(fn(User $u) => $u->getName(), array_slice($blocked_users, 0, -1) )), '{last_user}' => $blocked_users[array_key_last($blocked_users)]->getName()], 'global');
                $this->addFlash('error', $this->translator->trans('{users} haben dich geblockt und wurden daher aus der Liste der Empfänger für diese Nachricht gestrichen.',['{users}' => $users_text],'global'));
            }
        }

        return AjaxResponse::success( true , ['url' => $this->generateUrl('pm_view')] );
    }

    /**
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param PermissionHandler $perm
     * @param EventProxyService $proxy
     * @param int|null $overwrite_og
     * @param User[]|null $overwrite_user
     * @return Response
     */
    #[Route(path: 'api/pm/og_post', name: 'pm_new_og_thread_controller')]
    public function new_og_thread_api(JSONRequestParser $parser, EntityManagerInterface $em, PermissionHandler $perm, EventProxyService $proxy, ?int $overwrite_og = null, ?array $overwrite_user = null): Response {
        if (!$parser->has_all(['title','content'], true) || ( !$parser->has('og') && !$overwrite_og ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('content');
        $og = $overwrite_og ?? $parser->get_int('og');

        $official_group = $this->entity_manager->getRepository(OfficialGroup::class)->find($og);
        if (!$official_group) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)  return AjaxResponse::error( self::ErrorPostTitleLength );
        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $ts = new DateTime();

        $pg = (new UserGroup())->setType(UserGroup::GroupMessageGroup)->setName( $title )->setRef1(1)->setRef2( $ts->getTimestamp() )->setRef3( $ts->getTimestamp() );
        $this->entity_manager->persist($pg);

        if ($overwrite_user)
            foreach ($overwrite_user as $user)
                $perm->associate( $user, $pg, UserGroupAssociation::GroupAssociationTypePrivateMessageMember );
        else {
            $perm->associate( $this->getUser(), $pg, UserGroupAssociation::GroupAssociationTypePrivateMessageMember );
            $overwrite_user = [$this->getUser()];
        }

        $post = (new GlobalPrivateMessage())
            ->setSender($this->getUser())->setTimestamp($ts)->setReceiverGroup($pg)->setText($text);
        if ($overwrite_og) $post->setSenderGroup($official_group);

        /** @var HTMLParserInsight $insight */
        if (!$this->preparePost($this->getUser(),null,$post, null, $insight))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest, ['a' => 10] );
        if ($insight->text_length < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        $this->entity_manager->persist( (new OfficialGroupMessageLink())->setMessageGroup( $pg )->setOfficialGroup( $official_group ) );

        foreach ($perm->usersInGroup( $official_group->getUsergroup()) as $group_member)
            if (!in_array($group_member, $overwrite_user))
                $perm->associate( $group_member, $pg, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember )
                    ->setRef1( 0  )->setRef2( 0 );

        $this->entity_manager->persist( $post );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException );
        }

        $proxy->globalPrivateMessageNewPostEvent( $post, $insight, true );

        return AjaxResponse::success( true , ['url' => $this->generateUrl('pm_view')] );
    }


    /**
     * @param int $id
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/{id<\d+>}/answer', name: 'pm_new_post_controller')]
    public function new_post_api(int $id, JSONRequestParser $parser, EntityManagerInterface $em, EventProxyService $proxy): Response {

        $user = $this->getUser();

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $official = $this->entity_manager->getRepository(OfficialGroupMessageLink::class)->findOneBy(['messageGroup' => $group]);

        if (!$official && $this->userHandler->isRestricted( $user, AccountRestriction::RestrictionGlobalCommunication ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has('content', true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember,UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember], 'association' => $group]);

        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $all_associations = $em->getRepository(UserGroupAssociation::class)->findBy(['association' => $group]);
        $self_pm = count($all_associations) === 1 && $all_associations[0] = $group_association;

        // Check the last 4 posts; if they were all made by the same user, they must wait 5min before they can post again
        /** @var GlobalPrivateMessage[] $last_posts */
        $last_posts = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->findBy(['receiverGroup' => $group], ['timestamp' => 'DESC'], 4);
        if (!$self_pm && count($last_posts) === 4) {
            $all_by_user = true;
            foreach ($last_posts as $last_post) $all_by_user = $all_by_user && ($last_post->getSender() === $user);
            if ($all_by_user && $last_posts[0]->getTimestamp()->getTimestamp() > (time() - 300) )
                return AjaxResponse::error( self::ErrorForumLimitHit );
        }

        $text  = $parser->trimmed('content');

        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );
        $ts = new DateTime();

        $this->entity_manager->persist( $group->setRef1( $group->getRef1() + 1 )->setRef2( $ts->getTimestamp() ) );
        $this->entity_manager->persist( $group_association->setRef1($group_association->getRef1() + 1 ));

        $post = (new GlobalPrivateMessage())->setSender($user)->setTimestamp($ts)->setReceiverGroup($group)->setText($text);

        /** @var HTMLParserInsight $insight */
        if (!$this->preparePost($user,null,$post, null, $insight))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest, ['a' => 10] );
        if ($insight->text_length < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        if ($group_association->getAssociationType() === UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember && $official)
            $post->setSenderGroup($official->getOfficialGroup());

        $this->entity_manager->persist( $post );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $proxy->globalPrivateMessageNewPostEvent( $post, $insight, false );

        return AjaxResponse::success( true , ['url' => $this->generateUrl('pm_view')] );
    }

    /**
     * @param int $pid
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $ti
     * @return Response
     */
    #[Route(path: 'api/pm/{pid<\d+>}/report', name: 'pm_report_post_controller')]
    public function report_post_api(int $pid, EntityManagerInterface $em, TranslatorInterface $ti, JSONRequestParser $parser, CrowService $crow, RateLimitingFactoryProvider $rateLimiter): Response {
        $user = $this->getUser();

        $message = $em->getRepository( GlobalPrivateMessage::class )->find( $pid );
        if (!$message) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $reason = $parser->get_int('reason', 0, 0, 13);
        if ($reason === 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $group = $message->getReceiverGroup();
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive], 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );


        $targetUser = $message->getSender();
        if ($targetUser->getName() === "Der Rabe" )
            return AjaxResponse::success(true, ['msg' => $ti->trans('Das ist keine gute Idee, das ist dir doch wohl klar!', [], 'game')]);

        $reports = $message->getAdminReports();
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() == $user->getId())
                return AjaxResponse::success();

        if (!($limit = $rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume())->isAccepted())
            return AjaxResponse::error( ErrorHelper::ErrorRateLimited, ['detail' => 'report', 'retry_in' => $limit->getRetryAfter()->getTimestamp() - (new DateTime())->getTimestamp()]);

        $details = $parser->trimmed('details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setTs(new DateTime('now'))
            ->setReason( $parser->get_int('reason', 0, 0, 13) )
            ->setDetails( $details ?: null )
            ->setGpm($message);

        try {
            $em->persist($newReport);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        try {
            $crow->triggerExternalModNotification( 'A global PM has been reported.', $message, $newReport );
        } catch (\Throwable $e) {}

        return AjaxResponse::success( true, ['msg' => $ti->trans('Du hast die Nachricht von {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...', ['{username}' => '<span>' . $message->getSender()->getName() . '</span>'], 'game')]);
    }

    /**
     * @param int $pid
     * @param int $action
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/{pid<\d+>}/pin/{action<\d+>}', name: 'pm_pin_post_controller')]
    public function report_pin_api(int $pid, int $action, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        if ($action !== 0 && $action !== 1) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $message = $em->getRepository( GlobalPrivateMessage::class )->find( $pid );
        if (!$message || $message->getHidden()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $group = $message->getReceiverGroup();
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
                                                                                            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive], 'association' => $group]);
        if (!$group_association || $group_association->getAssociationLevel() !== UserGroupAssociation::GroupAssociationLevelFounder)
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($action === 1) {
            $message->setCollapsed( false );
            foreach ($em->getRepository( GlobalPrivateMessage::class )->findBy(['receiverGroup' => $group, 'pinned' => true]) as $pinned)
                $this->entity_manager->persist($pinned->setPinned(false));
        }

        $message->setPinned( $action === 1 );

        $this->entity_manager->persist($message);

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $pid
     * @param int $action
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/pm/{pid<\d+>}/collapse/{action<\d+>}', name: 'pm_collapse_post_controller')]
    public function report_collapse_api(int $pid, int $action, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        if ($action !== 0 && $action !== 1) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $message = $em->getRepository( GlobalPrivateMessage::class )->find( $pid );
        if (!$message || $message->getHidden()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $group = $message->getReceiverGroup();
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
                                                                                            'associationType' => [UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive], 'association' => $group]);
        if (!$group_association || $group_association->getAssociationLevel() !== UserGroupAssociation::GroupAssociationLevelFounder)
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $message->setCollapsed( $action === 1 );
        if ($action === 1) $message->setPinned(false);

        $this->entity_manager->persist($message);

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }
}