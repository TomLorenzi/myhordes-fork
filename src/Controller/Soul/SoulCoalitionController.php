<?php

namespace App\Controller\Soul;

use App\Entity\AccountRestriction;
use App\Entity\CitizenRankingProxy;
use App\Entity\Shoutbox;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Structures\MyHordesConf;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
class SoulCoalitionController extends SoulController
{
    /**
     * @param ConfMaster $conf
     * @return Response
     */
    #[Route(path: 'jx/soul/coalitions', name: 'soul_coalitions')]
    public function soul_coalitions(ConfMaster $conf): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
            'user' => $user,
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
            ]);

        $all_users = $user_coalition ? $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'association' => $user_coalition->getAssociation()
            ]) : [];

        $user_invitations = $user_coalition ? null : $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        );

        return $this->render( 'ajax/soul/coalitions.html.twig', $this->addDefaultTwigArgs("soul_coalitions", [
            'membership' => $user_coalition,
            'all_users' => $all_users,
            'invitations' => $user_invitations,
            'max_coa_size' => $conf->getGlobalConf()->get(MyHordesConf::CONF_COA_MAX_NUM, 5),
            'coa_full' => count($all_users) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_COA_MAX_NUM, 5),
            'coa_inactive_timeout' => $conf->getGlobalConf()->get(MyHordesConf::CONF_COA_MAX_DAYS_INACTIVITY, 5) * 86400,
        ]) );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/shoutbox', name: 'soul_shoutbox')]
    public function soul_shoutbox(): Response
    {
        $user = $this->getUser();

        $entries = [];

        /** @var UserGroupAssociation|null $user_coalition */
        if ($user_coalition = $this->user_handler->getCoalitionMembership($user)) {
            /** @var Shoutbox $shoutbox */
            if ($shoutbox = $this->entity_manager->getRepository(Shoutbox::class)->findOneBy(['userGroup' => $user_coalition->getAssociation()]))
                $entries = $this->entity_manager->getRepository(ShoutboxEntry::class)->findFromShoutbox($shoutbox, new DateTime('-60day'), 100);
        }

        $last_ref = $user_coalition?->getRef1() ?? 0;

        if (!empty($entries)) {
            $rm = $this->entity_manager->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $user]);
            if (!$rm) $rm = (new ShoutboxReadMarker())->setUser($user);

            if ($rm->getEntry() !== $entries[0]) {
                $rm->setEntry($entries[0]);
                $this->entity_manager->persist($rm);
                try {
                    $this->entity_manager->flush();
                } catch (Exception $e) {}
            }

            $user_coalition->setRef1( $entries[0]->getId() );
            $this->entity_manager->persist( $user_coalition );
            try { $this->entity_manager->flush(); } catch (\Throwable) {}
        }

        return $this->render( 'ajax/soul/shout_content.html.twig', [
            'entries' => $entries,
            'lastRef' => $last_ref
        ] );
    }

    /**
     * @param TranslatorInterface $trans
     * @param PermissionHandler $perm
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/create', name: 'soul_create_coalition')]
    public function api_soul_create_coalitions(TranslatorInterface $trans, PermissionHandler $perm): Response
    {
        $user = $this->getUser();
        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalitions = $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive] ]
        );

        if (!empty($user_coalitions)) return AjaxResponse::error( self::ErrorCoalitionAlreadyMember );

        // Creating a coalition refuses all invitations
        foreach ($this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        ) as $invitation) $this->entity_manager->remove($invitation);

        $this->entity_manager->persist(
            $g = (new UserGroup())
                ->setName($trans->trans("{name}'s Koalition", ['{name}' => $user->getUsername()], 'soul'))
                ->setType(UserGroup::GroupSmallCoalition)
                ->setRef1($user->getId())
        );
        $perm->associate( $user, $g, UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationLevelFounder );
        $this->entity_manager->persist((new Shoutbox())->setUserGroup($g));

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/toggle', name: 'soul_toggle_coalition')]
    public function api_soul_toggle_coalition_membership(): Response
    {
        $user = $this->getUser();


        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->user_handler->getCoalitionMembership($user);

        if ($user_coalition === null) return AjaxResponse::error( self::ErrorCoalitionNotSet );

        $user_coalition->setAssociationType(
            $user_coalition->getAssociationType() === UserGroupAssociation::GroupAssociationTypeCoalitionMember
                ? UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive
                : UserGroupAssociation::GroupAssociationTypeCoalitionMember
        );
        $this->entity_manager->persist( $user_coalition );

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $coalition
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/leave/{coalition<\d+>}', name: 'soul_leave_coalition')]
    public function api_soul_leave_coalition(int $coalition): Response
    {
        $user = $this->getUser();

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->find($coalition);

        if ($user_coalition === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (
            $user_coalition->getUser() !== $user ||
            $user_coalition->getAssociation()->getType() !== UserGroup::GroupSmallCoalition ||
            !in_array($user_coalition->getAssociationType(), [
                UserGroupAssociation::GroupAssociationTypeCoalitionInvitation,
                UserGroupAssociation::GroupAssociationTypeCoalitionMember,
                UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive
            ])) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $destroy = $user_coalition->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder;

        if ($destroy) {

            foreach ($this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'association' => $user_coalition->getAssociation()
            ]) as $assoc ) $this->entity_manager->remove($assoc);

            $this->entity_manager->remove( $user_coalition->getAssociation() );

        } else {
            $this->entity_manager->remove( $user_coalition );
            /** @var Shoutbox|null $shoutbox */
            if ($shoutbox = $this->user_handler->getShoutbox($user_coalition)) {
                $shoutbox->addEntry(
                    (new ShoutboxEntry())
                        ->setType( ShoutboxEntry::SBEntryTypeLeave )
                        ->setTimestamp( new DateTime() )
                        ->setUser1( $user )
                );
                $this->entity_manager->persist($shoutbox);
            }
        }


        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        if ($destroy) $this->addFlash('notice', $this->translator->trans('Durch deinen Weggang hast du deine Koalition soeben aufgelöst.', [], 'soul'));

        return AjaxResponse::success();
    }

    /**
     * @param int $coalition
     * @param TranslatorInterface $trans
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/join/{coalition<\d+>}', name: 'soul_join_coalition')]
    public function api_soul_join_coalition(int $coalition, TranslatorInterface $trans): Response
    {
        $user = $this->getUser();
        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->find($coalition);

        if ($user_coalition === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (
            $user_coalition->getUser() !== $user ||
            $user_coalition->getAssociation()->getType() !== UserGroup::GroupSmallCoalition ||
            $user_coalition->getAssociationType() !== UserGroupAssociation::GroupAssociationTypeCoalitionInvitation
            ) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $user_coalition->setAssociationType(UserGroupAssociation::GroupAssociationTypeCoalitionMember);
        $this->entity_manager->persist($user_coalition);

        // Joining a coalition refuses all other invitations
        foreach ($this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        ) as $invitation) if ($invitation->getId() !== $coalition) $this->entity_manager->remove($invitation);

        $this->addFlash('info', $trans->trans('Herzlichen Glückwunsch, du bist der Koalition soeben beigetreten!', [], 'soul'));

        /** @var Shoutbox|null $shoutbox */
        if ($shoutbox = $this->user_handler->getShoutbox($user_coalition)) {
            $shoutbox->addEntry(
                (new ShoutboxEntry())
                    ->setType( ShoutboxEntry::SBEntryTypeJoin )
                    ->setTimestamp( new DateTime() )
                    ->setUser1( $user )
            );
            $this->entity_manager->persist($shoutbox);
        }

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param UserGroupAssociation $target_coalition
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/kick/{id<\d+>}', name: 'soul_kick_coalition')]
    public function api_soul_kick_coalition(UserGroupAssociation $target_coalition): Response
    {
        $user = $this->getUser();
        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->user_handler->getCoalitionMembership($user);
        if ($user_coalition === null || !in_array($user_coalition->getAssociationType(), [UserGroupAssociation::GroupAssociationTypeCoalitionMember,UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($target_coalition->getAssociation() !== $user_coalition->getAssociation())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($target_coalition->getAssociationType() !== UserGroupAssociation::GroupAssociationTypeCoalitionInvitation && $user_coalition->getAssociationLevel() !== UserGroupAssociation::GroupAssociationLevelFounder)
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $this->entity_manager->remove( $target_coalition );
        /** @var Shoutbox|null $shoutbox */
        if ($shoutbox = $this->user_handler->getShoutbox($user_coalition)) {
            $shoutbox->addEntry(
                (new ShoutboxEntry())
                    ->setType( ShoutboxEntry::SBEntryTypeLeave )
                    ->setTimestamp( new DateTime() )
                    ->setUser1( $user )
                    ->setUser2( $target_coalition->getUser() )
            );
            $this->entity_manager->persist($shoutbox);
        }

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param UserGroupAssociation $target_coalition
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/promote/{id<\d+>}', name: 'soul_promote_coalition')]
    public function api_soul_promote_coalition(UserGroupAssociation $target_coalition): Response
    {
        $user = $this->getUser();
        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );


        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->user_handler->getCoalitionMembership($user);
        if (
            $user_coalition === null ||
            !in_array($user_coalition->getAssociationType(), [UserGroupAssociation::GroupAssociationTypeCoalitionMember,UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]) ||
            $user_coalition->getAssociationLevel() !== UserGroupAssociation::GroupAssociationLevelFounder
        ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($target_coalition->getAssociation() !== $user_coalition->getAssociation())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $this->entity_manager->persist( $target_coalition->setAssociationLevel( UserGroupAssociation::GroupAssociationLevelFounder ) );
        $this->entity_manager->persist( $user_coalition->setAssociationLevel( UserGroupAssociation::GroupAssociationLevelDefault ) );

        /** @var Shoutbox|null $shoutbox */
        if ($shoutbox = $this->user_handler->getShoutbox($user_coalition)) {
            $shoutbox->addEntry(
                (new ShoutboxEntry())
                    ->setType( ShoutboxEntry::SBEntryTypePromote )
                    ->setTimestamp( new DateTime() )
                    ->setUser1( $user )
                    ->setUser2( $target_coalition->getUser() )
            );
            $this->entity_manager->persist($shoutbox);
        }

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/rename', name: 'soul_rename_coalition')]
    public function api_soul_rename_coalition(JSONRequestParser $parser): Response
    {
        $user = $this->getUser();
        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->user_handler->getCoalitionMembership($user);
        if ($user_coalition === null || $user_coalition->getAssociationLevel() !== UserGroupAssociation::GroupAssociationLevelFounder)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $new_name = $parser->trimmed('name');
        if (mb_strlen( $new_name ) < 3 || mb_strlen( $new_name ) > 32)
            return AjaxResponse::error( mb_strlen( $new_name ) > 32 ? self::ErrorCoalitionNameTooLong : self::ErrorCoalitionNameTooShort );

        $this->entity_manager->persist( $user_coalition->getAssociation()->setName( $new_name ) );

        /** @var Shoutbox|null $shoutbox */
        if ($shoutbox = $this->user_handler->getShoutbox($user_coalition)) {
            $shoutbox->addEntry(
                (new ShoutboxEntry())
                    ->setType( ShoutboxEntry::SBEntryTypeNameChange )
                    ->setTimestamp( new DateTime() )
                    ->setUser1( $user )
                    ->setText( $new_name )
            );
            $this->entity_manager->persist($shoutbox);
        }

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param ConfMaster $conf
     * @param int $id
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/invite/{id<\d+>}', name: 'soul_invite_coalition')]
    public function api_soul_invite_coalition(ConfMaster $conf, int $id): Response
    {
        $user = $this->getUser();

        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($id === $user->getId()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        /** @var UserGroupAssociation|null $user_coalition */
        if (($user_coalition = $this->user_handler->getCoalitionMembership($user)) === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $all_users = $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'association' => $user_coalition->getAssociation(),
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive, UserGroupAssociation::GroupAssociationTypeCoalitionInvitation] ]
        );

        if (count($all_users) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_COA_MAX_NUM, 5))
            return AjaxResponse::error( self::ErrorCoalitionFull );

        /** @var User $target */
        $target = $this->entity_manager->getRepository(User::class)->find($id);
        if ($target === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($target->getEmail() === 'crow' || $target->getEmail() === 'anim' || $target->getEmail() === $target->getUsername() || mb_substr($target->getEmail(), -10) === '@localhost')
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var UserGroupAssociation|null $user_coalition */
        $target_coalition = $this->user_handler->getCoalitionMembership($target);

        /** @var UserGroupAssociation|null $user_coalition */
        $self_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
                'user' => $target,
                'association' => $user_coalition->getAssociation()
        ] );


        if ($target_coalition || $self_coalition) return AjaxResponse::error( self::ErrorCoalitionUserAlreadyMember );

        $this->entity_manager->persist(
            (new UserGroupAssociation)
                ->setUser($target)
                ->setAssociation( $user_coalition->getAssociation() )
                ->setAssociationType( UserGroupAssociation::GroupAssociationTypeCoalitionInvitation )
                ->setAssociationLevel( UserGroupAssociation::GroupAssociationLevelDefault )
        );

        /** @var Shoutbox|null $shoutbox */
        if ($shoutbox = $this->user_handler->getShoutbox($user_coalition)) {
            $shoutbox->addEntry(
                (new ShoutboxEntry())
                    ->setType( ShoutboxEntry::SBEntryTypeInvite )
                    ->setTimestamp( new DateTime() )
                    ->setUser1( $user )
                    ->setUser2( $target )
            );
            $this->entity_manager->persist($shoutbox);
        }


        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/soul/coalition/shout', name: 'soul_shout_coalition')]
    public function api_coalition_shout(JSONRequestParser $parser, HTMLService $html): Response
    {
        $user = $this->getUser();
        $shoutbox = $this->user_handler->getShoutbox($user);

        if (!$shoutbox || $this->user_handler->isRestricted( $user, AccountRestriction::RestrictionOrganization )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $last_chat_entries = $this->entity_manager->getRepository(ShoutboxEntry::class)->findBy(
            ['type' => ShoutboxEntry::SBEntryTypeChat], ['timestamp' => 'DESC'], 10
        );
        if (count($last_chat_entries) === 10 && $last_chat_entries[9]->getTimestamp()->getTimestamp() > (time() - 30))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $text = $parser->trimmed('text', '');
        if (!$html->htmlPrepare( $user, 0, ['core_rp','core_rp_coa'],  $text, null, $insight ) || $insight->text_length < 2 || $insight->text_length > 256)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $shoutbox = $this->user_handler->getShoutbox($user);
        $shoutbox->addEntry(
            (new ShoutboxEntry())
                ->setType( ShoutboxEntry::SBEntryTypeChat )
                ->setTimestamp( new DateTime() )
                ->setUser1( $user )
                ->setText( $text )
        );
        $this->entity_manager->persist($shoutbox);

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }
}
