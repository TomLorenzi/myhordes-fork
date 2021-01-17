<?php

namespace App\Controller\Messages;

use App\Entity\Announcement;
use App\Entity\ForumModerationSnippet;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use App\Translation\T;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class MessageGlobalPMController extends MessageController
{

    /**
     * @Route("api/pm/ping", name="api_pm_ping")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function ping_check_new_message(EntityManagerInterface $em): Response {
        $user = $this->getUser();
        if (!$user) return new AjaxResponse(['new' => 0, 'connected' => false, 'success' => true]);

        return new AjaxResponse(['new' =>
            $em->getRepository(UserGroupAssociation::class)->countUnreadPMsByUser($user) +
            $em->getRepository(UserGroupAssociation::class)->countUnreadInactivePMsByUser($user) +
            $em->getRepository(GlobalPrivateMessage::class)->countUnreadDirectPMsByUser($user) +
            $em->getRepository(Announcement::class)->countUnreadByUser($user, $this->getUserLanguage())
            , 'connected' => 15000, 'success' => true]);
    }

    /**
     * @Route("jx/pm/view", name="pm_view")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @return Response
     */
    public function pm_view(EntityManagerInterface $em, JSONRequestParser $p): Response {
        return $this->render( 'ajax/pm/view.html.twig', $this->addDefaultTwigArgs(null, [

        ] ));
    }

    /**
     * @param UserGroupAssociation[] $group_associations
     * @param array|null $entries
     */
    private function render_group_associations(array $group_associations, ?array &$entries = null): void {
        if ($entries === null) $entries = [];

        foreach ($group_associations as $association) {

            $last_post_date = new DateTime();
            $last_post_date->setTimestamp($association->getAssociation()->getRef2());

            $owner_assoc = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy([
                'association' => $association->getAssociation(),
                'associationLevel' => UserGroupAssociation::GroupAssociationLevelFounder,
            ]);

            $read_only = $association->getAssociationType() === UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive;

            $entries[] = [
                'obj'    => $association,
                'date'   => $last_post_date,
                'system' => false,
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
                'system' => false,
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
     */
    private function render_directNotifications( ?array &$entries = null ): void {
        if ($entries === null) $entries = [];

        $latest_pm = $this->entity_manager->getRepository(GlobalPrivateMessage::class)->getDirectPMsByUser($this->getUser(), 0, 1);

        if ($latest_pm) {
            $crow = $this->entity_manager->getRepository(User::class)->find(66);
            $entries[] = [
                'obj'    => $latest_pm[0],
                'date'   => $latest_pm[0]->getTimestamp(),
                'system' => true,
                'title'  => T::__('Nachrichten des Raben', 'global'),
                'closed' => false,
                'count'  => $this->entity_manager->getRepository(GlobalPrivateMessage::class)->count(['receiverUser' => $this->getUser(), 'receiverGroup' => null]),
                'unread' => $this->entity_manager->getRepository(GlobalPrivateMessage::class)->countUnreadDirectPMsByUser($this->getUser()),
                'owner'  => $crow,
                'users'  => [$this->getUser(),$crow]
            ];

        }
    }

    /**
     * @Route("jx/pm/list", name="pm_list")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @return Response
     */
    public function pm_load_list(EntityManagerInterface $em, JSONRequestParser $p): Response {
        $entries = [];

        $skip = $p->get_array('skip');
        $num = max(5,min(30,$p->get_num('num', 30)));

        $this->render_group_associations( $em->getRepository(UserGroupAssociation::class)->findByUserAssociation($this->getUser(), [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], $skip['g'] ?? [], $num+1), $entries );

        $this->render_announcements( $em->getRepository(Announcement::class)->findByLang($this->getUserLanguage(),
        $skip['a'] ?? [], $num+1), $entries );

        $this->render_directNotifications($entries);

        usort($entries, fn($a,$b) => $b['date'] <=> $a['date']);

        return $this->render( 'ajax/pm/list.html.twig', $this->addDefaultTwigArgs(null, [
            'more' => count($entries) > $num,
            'entries' => array_slice($entries,0,$num)
        ] ));
    }

    /**
     * @Route("api/pm/conversation/group/{gid<\d+>}/user/{uid<\d+>}/kick", name="pm_conv_group_user_kick")
     * @param int $gid
     * @param int $uid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_conversation_group_user_kick(int $gid, int $uid, EntityManagerInterface $em): Response {

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
            $em->remove( $other_association );
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
     * @Route("api/pm/conversation/group/{gid<\d+>}/user/{uid<\d+>}/restore", name="pm_conv_group_user_restore")
     * @param int $gid
     * @param int $uid
     * @param EntityManagerInterface $em
     * @return Response
     */
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
     * @Route("api/pm/conversation/group/{gid<\d+>}/user/{uid<\d+>}/add", name="pm_conv_group_user_add")
     * @param int $gid
     * @param int $uid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_conversation_group_user_add(int $gid, int $uid, EntityManagerInterface $em, UserHandler $userHandler): Response {

        $group = $em->getRepository( UserGroup::class )->find($gid);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' =>
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, 'associationLevel' => UserGroupAssociation::GroupAssociationLevelFounder
                                                                                            , 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $other_user = $em->getRepository(User::class)->find($uid);
        if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if ($userHandler->hasRole($other_user, 'ROLE_DUMMY')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var UserGroupAssociation $other_association */
        $other_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $other_user,'association' => $group]);
        if ($other_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $em->persist((new UserGroupAssociation())
                         ->setUser($other_user)->setAssociation($group)
                         ->setAssociationLevel(UserGroupAssociation::GroupAssociationLevelDefault)
                         ->setAssociationType(UserGroupAssociation::GroupAssociationTypePrivateMessageMember)
        );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }


    /**
     * @Route("jx/pm/conversation/group/{id<\d+>}/users", name="pm_conv_group_users")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_user_list(int $id, EntityManagerInterface $em): Response {
        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return new Response('not found');

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
            'associationType' => UserGroupAssociation::GroupAssociationTypePrivateMessageMember, 'association' => $group]);
        if (!$group_association) return new Response('not found');

        $all_associations = $em->getRepository(UserGroupAssociation::class)->findBy(['associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], 'association' => $group]);

        return $this->render( 'ajax/pm/user_list.html.twig', $this->addDefaultTwigArgs(null, [
            'gid' => $id,
            'owner' => $group_association->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder,
            'active'   => array_filter( array_map( fn(UserGroupAssociation $a): ?User => $a->getAssociationType() === UserGroupAssociation::GroupAssociationTypePrivateMessageMember ? $a->getUser() : null, $all_associations ) ),
            'inactive' => array_filter( array_map( fn(UserGroupAssociation $a): ?User => $a->getAssociationType() !== UserGroupAssociation::GroupAssociationTypePrivateMessageMember ? $a->getUser() : null, $all_associations ) ),
        ] ));
    }



    /**
     * @Route("jx/pm/conversation/group/{id<\d+>}", name="pm_conv_group")
     * @param int $id
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @return Response
     */
    public function pm_conversation_group(int $id, EntityManagerInterface $em, JSONRequestParser $p): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return new Response('not found');

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
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
            $this->entity_manager->persist( $group_association->setRef1( $read_only ? $group_association->getRef3() : $group->getRef1() )->setRef2( $messages[0]->getId() ) );
            $this->entity_manager->flush();
        } catch (\Exception $e) {}

        foreach ($messages as $message) $message->setText( $this->prepareEmotes( $message->getText() ) );

        /** @var GlobalPrivateMessage[] $sliced */
        $sliced = array_slice($messages, 0, $num);

        return $this->render( 'ajax/pm/conversation_group.html.twig', $this->addDefaultTwigArgs(null, [
            'gid' => $id,
            'last' => $last,
            'more' => count($messages) > $num,
            'messages' => $sliced,
            'last_message' => $sliced[array_key_last($sliced)]->getId()
        ] ));
    }

    /**
     * @Route("jx/pm/conversation/dm", name="pm_dm")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @return Response
     */
    public function pm_direct_messages(EntityManagerInterface $em, LogTemplateHandler $th, JSONRequestParser $p): Response {

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
            $this->entity_manager->flush();
        } catch (\Exception $e) {}

        foreach ($sliced as $message) {
            $tx = '';
            if ($message->getText()) $tx .= $this->prepareEmotes($message->getText());

            if ($message->getTemplate())
                try {
                    $tx .= $this->translator->trans(
                        $message->getTemplate()->getText(), $th->parseTransParams($message->getTemplate()->getVariableTypes(), $message->getData()), 'game'
                    );
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
     * @Route("jx/pm/conversation/announce/{id<\d+>}", name="pm_announce")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_announcement(int $id, EntityManagerInterface $em): Response {
        $announce = $em->getRepository( Announcement::class )->find($id);
        if (!$announce || $announce->getLang() != $this->getUserLanguage()) return new Response('not found');

        $new = !$announce->getReadBy()->contains($this->getUser());
        if ($new)
            try {
                $announce->getReadBy()->add($this->getUser());
                $this->entity_manager->persist( $announce );
                $this->entity_manager->flush();
            } catch (\Exception $e) {}

        $announce->setText( $this->prepareEmotes( $announce->getText() ) );

        return $this->render( 'ajax/pm/announcement.html.twig', $this->addDefaultTwigArgs(null, [
            'announcements' => [$announce],
            'new' => $new,
            'more' => false
        ] ));
    }

    /**
     * @Route("jx/pm/conversation/announce/all", name="pm_announce_all")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function pm_announcement_all(EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $skip = $parser->get_array('skip');
        $num = max(1,min(10,$parser->get_num('num', 5)));

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
                $this->entity_manager->flush();
            } catch (\Exception $e) {}

        foreach ($sliced as $announce)
            $announce->setText( $this->prepareEmotes( $announce->getText() ) );

        return $this->render( 'ajax/pm/announcement.html.twig', $this->addDefaultTwigArgs(null, [
            'announcements' => $announces,
            'new' => $new,
            'more' => count($announces) > $num
        ] ));
    }

    /**
     * @Route("api/pm/conversation/group/{id<\d+>}/delete", name="pm_delete_conv_group")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_delete_conversation_group(int $id, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
        ], 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $num_of_assocs = $em->getRepository(UserGroupAssociation::class)->count(['association' => $group]);

        $this->entity_manager->remove( $group_association );
        if ($num_of_assocs < 2) $this->entity_manager->remove( $group );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/pm/conversation/group/unread/{id<\d+>}", name="pm_unread_conv_group")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_unread_conversation_group(int $id, EntityManagerInterface $em): Response {

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(), 'associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypePrivateMessageMemberInactive
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
     * @Route("api/pm/conversation/dm/delete", name="pm_delete_dm")
     * @param EntityManagerInterface $em
     * @return Response
     */
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
     * @Route("api/pm/conversation/dm/unread", name="pm_unread_dm")
     * @param EntityManagerInterface $em
     * @return Response
     */
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
     * @Route("api/pm/conversation/announce/unread/{id<\d+>}", name="pm_unread_announce")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
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
     * @Route("jx/pm/create-editor", name="pm_thread_editor_controller")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function editor_pm_thread_api(EntityManagerInterface $em): Response {
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
        ] );
    }

    /**
     * @Route("jx/pm/answer-editor/{id<\d+>}", name="pm_post_editor_controller")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function editor_pm_post_api(int $id, EntityManagerInterface $em): Response {
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
        ] );
    }

    /**
     * @Route("api/pm/post", name="pm_new_thread_controller")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param UserHandler $userHandler
     * @return Response
     */
    public function new_thread_api(JSONRequestParser $parser, EntityManagerInterface $em, UserHandler $userHandler): Response {

        $user = $this->getUser();
        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['title','content','users'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('content');
        $user_ids = $parser->get('users');
        array_map( fn($u) => (int)$u, is_array($parser->get('users')) ? $parser->get('users') : [] );

        $users = $this->entity_manager->getRepository(User::class)->findBy(['id' => $user_ids]);
        if (count($user_ids) !== count($users)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        foreach ($users as $chk_user) {
            if ($chk_user === $user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            if ($userHandler->hasRole($chk_user, 'ROLE_DUMMY')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)  return AjaxResponse::error( self::ErrorPostTitleLength );
        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $ts = new DateTime();

        $pg = (new UserGroup())->setType(UserGroup::GroupMessageGroup)->setName( $title )->setRef1(1)->setRef2( $ts->getTimestamp() );
        $this->entity_manager->persist($pg);

        $this->entity_manager->persist( $owner_assoc = (new UserGroupAssociation())
            ->setUser($user)->setAssociation($pg)
            ->setAssociationLevel(UserGroupAssociation::GroupAssociationLevelFounder)
            ->setAssociationType(UserGroupAssociation::GroupAssociationTypePrivateMessageMember)
            ->setRef1(1)
        );

        foreach ($users as $chk_user)
            $this->entity_manager->persist( (new UserGroupAssociation())
                ->setUser($chk_user)->setAssociation($pg)
                ->setAssociationLevel(UserGroupAssociation::GroupAssociationLevelDefault)
                ->setAssociationType(UserGroupAssociation::GroupAssociationTypePrivateMessageMember)
            );


        $post = (new GlobalPrivateMessage())
            ->setSender($user)->setTimestamp($ts)->setReceiverGroup($pg)->setText($text);

        $tx_len = 0;
        if (!$this->preparePost($user,null,$post,$tx_len, null, $edit))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest, ['a' => 10] );
        if ($tx_len < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        $this->entity_manager->persist( $post );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true , ['url' => $this->generateUrl('pm_view')] );
    }

    /**
     * @Route("api/pm/answer/{id<\d+>}", name="pm_new_post_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param UserHandler $userHandler
     * @return Response
     */
    public function new_post_api(int $id, JSONRequestParser $parser, EntityManagerInterface $em, UserHandler $userHandler): Response {

        $user = $this->getUser();
        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has('content', true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $group = $em->getRepository( UserGroup::class )->find($id);
        if (!$group || $group->getType() !== UserGroup::GroupMessageGroup) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var UserGroupAssociation $group_association */
        $group_association = $em->getRepository(UserGroupAssociation::class)->findOneBy(['user' => $this->getUser(),
            'associationType' => UserGroupAssociation::GroupAssociationTypePrivateMessageMember, 'association' => $group]);
        if (!$group_association) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $text  = $parser->trimmed('content');

        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );
        $ts = new DateTime();

        $this->entity_manager->persist( $group->setRef1( $group->getRef1() + 1 )->setRef2( $ts->getTimestamp() ) );
        $this->entity_manager->persist( $group_association->setRef1($group_association->getRef1() + 1 ));

        $post = (new GlobalPrivateMessage())->setSender($user)->setTimestamp($ts)->setReceiverGroup($group)->setText($text);

        $tx_len = 0;
        if (!$this->preparePost($user,null,$post,$tx_len, null, $edit))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest, ['a' => 10] );
        if ($tx_len < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        $this->entity_manager->persist( $post );

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true , ['url' => $this->generateUrl('pm_view')] );
    }
}