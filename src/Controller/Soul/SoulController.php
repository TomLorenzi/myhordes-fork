<?php

namespace App\Controller\Soul;

use App\Controller\CustomAbstractController;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\CitizenRankingProxy;
use App\Entity\ExternalApp;
use App\Entity\FoundRolePlayText;
use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RememberMeTokens;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\RolePlayTextPage;
use App\Entity\Season;
use App\Entity\UserGroupAssociation;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EternalTwinHandler;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Service\AdminActionHandler;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class SoulController extends CustomAbstractController
{
    const ErrorUserEditPasswordIncorrect     = ErrorHelper::BaseSoulErrors + 1;
    const ErrorTwinImportInvalidResponse     = ErrorHelper::BaseSoulErrors + 2;
    const ErrorTwinImportNoToken             = ErrorHelper::BaseSoulErrors + 3;
    const ErrorTwinImportProfileMismatch     = ErrorHelper::BaseSoulErrors + 4;
    const ErrorTwinImportProfileInUse        = ErrorHelper::BaseSoulErrors + 5;
    const ErrorETwinImportProfileInUse       = ErrorHelper::BaseSoulErrors + 6;
    const ErrorETwinImportServerCrash        = ErrorHelper::BaseSoulErrors + 7;

    const ErrorCoalitionAlreadyMember        = ErrorHelper::BaseSoulErrors + 10;
    const ErrorCoalitionNotSet               = ErrorHelper::BaseSoulErrors + 11;
    const ErrorCoalitionUserAlreadyMember    = ErrorHelper::BaseSoulErrors + 12;
    const ErrorCoalitionFull                 = ErrorHelper::BaseSoulErrors + 13;


    protected UserFactory $user_factory;
    protected UserHandler $user_handler;
    protected Packages $asset;

    public function __construct(EntityManagerInterface $em, UserFactory $uf, Packages $a, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator, ConfMaster $conf, CitizenHandler $ch, InventoryHandler $ih)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->user_factory = $uf;
        $this->asset = $a;
        $this->user_handler = $uh;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null ): array {
        $data = parent::addDefaultTwigArgs($section, $data);

        $user = $this->getUser();

        $data = $data ?? [];

        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
            'user' => $user,
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
        ]);

        $user_invitations = $user_coalition ? [] : $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        );

        $sb = $this->user_handler->getShoutbox($user);
        $messages = false;
        if ($sb) {
            $last_entry = $this->entity_manager->getRepository(ShoutboxEntry::class)->findOneBy(['shoutbox' => $sb], ['timestamp' => 'DESC']);
            if ($last_entry) {
                $marker = $this->entity_manager->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $user]);
                if (!$marker || $last_entry !== $marker->getEntry()) $messages = true;
            }
        }

        $data["soul_tab"] = $section;
        $data["new_message"] = !empty($user_invitations) || $messages;

        return $data;
    }

    /**
     * @Route("jx/soul/disabled_message", name="soul_disabled")
     * @return Response
     */
    public function soul_disabled(): Response
    {
        $user = $this->getUser();
        if (!$user->getShadowBan())
            return $this->redirect($this->generateUrl( 'soul_me' ));
        return $this->render( 'ajax/soul/acc_disabled.html.twig', ['ban' => $user->getShadowBan()]);
    }

    /**
     * @Route("jx/soul/me", name="soul_me")
     * @return Response
     */
    public function soul_me(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        // Get all the picto & count points
        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
    	$points = $this->user_handler->getPoints($user);
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($user->getAllHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($user->getAllHeroDaysSpent());

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;

        $progress = $nextSkill !== null ? ($user->getAllHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/me.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'pictos' => $pictos,
            'points' => round($points),
            'latestSkill' => $latestSkill,
            'progress' => floor($progress),
            'seasons' => $this->entity_manager->getRepository(Season::class)->findAll()
        ]));
    }

    /**
     * @Route("jx/soul/fuzzyfind/{url}", name="users_fuzzyfind")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param string $url
     * @return Response
     */
    public function users_fuzzyfind(JSONRequestParser $parser, EntityManagerInterface $em, $url = 'soul_visit'): Response
    {
        $user = $this->getUser();
        if ($user->getShadowBan()) return $this->render( 'ajax/soul/users_list.html.twig', [ 'users' => [] ]);

        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $searchName = $parser->get('name');
        $users = mb_strlen($searchName) >= 3 ? array_filter($em->getRepository(User::class)->findByNameContains($searchName), fn(User $u) =>
            ($u !== $user) && ($u->getEmail() !== 'crow') && (mb_substr($u->getEmail(), -10) !== '@localhost') && ($u->getUsername() !== $u->getEmail())) : [];

        return $this->render( 'ajax/soul/users_list.html.twig', [ 'users' => in_array($url, ['soul_visit','soul_invite_coalition']) ? $users : [], 'route' => $url ]);
    }


    /**
     * @Route("jx/soul/heroskill", name="soul_heroskill")
     * @return Response
     */
    public function soul_heroskill(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        // Get all the picto & count points
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($user->getAllHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($user->getAllHeroDaysSpent());

        $allSkills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findAll();

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;
        $progress = $nextSkill !== null ? ($user->getAllHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/heroskills.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'latestSkill' => $latestSkill,
            'nextSkill' => $nextSkill,
            'progress' => floor($progress),
            'skills' => $allSkills
        ]));
    }

    /**
     * @Route("jx/soul/news/{id}", name="soul_news")
     * @param Request $request
     * @param UserHandler $userHandler
     * @param int $id
     * @return Response
     */
    public function soul_news(Request $request, UserHandler $userHandler, int $id = 0): Response
    {
        $user = $this->getUser();

        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $lang = $user->getLanguage() ?? $request->getLocale() ?? 'de';
        $news = $this->entity_manager->getRepository(Changelog::class)->findByLang($lang);

        $selected = $id > 0 ? $this->entity_manager->getRepository(Changelog::class)->find($id) : null;
        if ($selected === null)
            $selected = $news[0] ?? null;

        try {
            $userHandler->setSeenLatestChangelog( $user, $lang );
            $this->entity_manager->flush();
        } catch (Exception $e) {}

        return $this->render( 'ajax/soul/news.html.twig', $this->addDefaultTwigArgs("soul_news", [
            'news' => $news, 'selected' => $selected
        ]) );
    }

    /**
     * @Route("jx/soul/settings", name="soul_settings")
     * @return Response
     */
    public function soul_settings(EternalTwinHandler $etwin): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        return $this->render( 'ajax/soul/settings.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'et_ready' => $etwin->isReady()
        ]) );
    }

    /**
     * @Route("jx/soul/season", name="soul_season")
     * @return Response
     */
    public function soul_season(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        return $this->render( 'ajax/soul/season.html.twig', $this->addDefaultTwigArgs("soul_season") );
    }

    /**
     * @Route("jx/soul/rps", name="soul_rps")
     * @return Response
     */
    public function soul_rps(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $rps = $this->entity_manager->getRepository(FoundRolePlayText::class)->findByUser($user);
        foreach ($rps as $rp) {
            // We mark as new RPs found in the last 5 minutes
            /** @var FoundRolePlayText $rp */
            $elapsed = $rp->getDateFound()->diff(new \DateTime());
            if ($elapsed->y == 0 && $elapsed->m == 0 && $elapsed->d == 0 && $elapsed->h == 0 && $elapsed->i <= 5)
                $rp->setNew(true);
        }
        return $this->render( 'ajax/soul/rps.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'rps' => $rps
        )));
    }

    /**
     * @Route("jx/soul/rps/read/{id}-{page}", name="soul_rp", requirements={"id"="\d+", "page"="\d+"})
     * @param int $id
     * @param int $page
     * @return Response
     */
    public function soul_view_rp(int $id, int $page): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));
        /** @var FoundRolePlayText $rp */
        $rp = $this->entity_manager->getRepository(FoundRolePlayText::class)->find($id);
        if($rp === null || !$user->getFoundTexts()->contains($rp)){
            return $this->redirect($this->generateUrl('soul_rps'));
        }

        if($page > count($rp->getText()->getPages()))
            return $this->redirect($this->generateUrl('soul_rps'));

        $rp->setNew(false);
        $this->entity_manager->persist($rp);

        $pageContent = $this->entity_manager->getRepository(RolePlayTextPage::class)->findOneByRpAndPageNumber($rp->getText(), $page);

        preg_match('/%asset%([a-zA-Z0-9.\/]+)%endasset%/', $pageContent->getContent(), $matches);

        if(count($matches) > 0) {
            $pageContent->setContent(preg_replace("={$matches[0]}=", "<img src='" . $this->asset->getUrl($matches[1]) . "' alt='' />", $pageContent->getContent()));
        }
        
        $this->entity_manager->flush();

        return $this->render( 'ajax/soul/view_rp.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'page' => $pageContent,
            'rp' => $rp,
            'current' => $page
        )));
    }


    /**
     * @Route("jx/soul/{sid}/town/{idtown}", name="soul_view_town")
     * @param string $sid
     * @param int $idtown
     * @return Response
     */
    public function soul_view_town(int $idtown, $sid = 'me'): Response
    {
        $user = $this->getUser();

        if ($sid !== 'me' && !is_numeric($sid))
            return $this->redirect($this->generateUrl('soul_me'));

        $id = $sid === 'me' ? -1 : (int)$sid;
        if ($id === $user->getId())
            return $this->redirect($this->generateUrl( 'soul_view_town', ['idtown' => $idtown] ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $target_user = $this->entity_manager->getRepository(User::class)->find($id);
        if($target_user === null && $id !== -1)  return $this->redirect($this->generateUrl('soul_me'));

        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($idtown);
        if($town === null)
            return $target_user === null ? $this->redirect($this->generateUrl('soul_me')) : $this->redirect($this->generateUrl('soul_visit', ['id' => $id]));

        if ($target_user === null) $target_user = $user;

        $pictoname = $town->getType()->getName() == 'panda' ? 'r_suhard_#00' : 'r_surlst_#00';
        $proto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoname]);

        $picto = $this->entity_manager->getRepository(Picto::class)->findOneBy(['townEntry' => $town, 'prototype' => $proto]);

        return $this->render(  $user === $target_user ? 'ajax/soul/view_town.html.twig' : 'ajax/soul/view_town_foreign.html.twig', $this->addDefaultTwigArgs("soul_visit", array(
            'user' => $target_user,
            'town' => $town,
            'last_user_standing' => $picto !== null ? $picto->getUser() : null
        )));
    }

    /**
     * @Route("api/soul/town/add_comment", name="soul_add_comment")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_add_comment(JSONRequestParser $parser): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $id = $parser->get("id");
        /** @var CitizenRankingProxy $citizenProxy */
        $citizenProxy = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($id);
        if ($citizenProxy === null || $citizenProxy->getUser() !== $user )
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $comment = $parser->get("comment");
        $citizenProxy->setComment($comment);
        if ($citizenProxy->getCitizen()) $citizenProxy->getCitizen()->setComment($comment);

        $this->entity_manager->persist($citizenProxy);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/generateid", name="api_soul_settings_generateid")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function soul_settings_generate_id(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $user->setExternalId(md5($user->getEmail() . mt_rand()));
        $entityManager->persist( $user );
        $entityManager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/deleteid", name="api_soul_settings_deleteid")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function soul_settings_deleteid(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId('');
        $entityManager->persist( $user );
        $entityManager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/common", name="api_soul_common")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_common(JSONRequestParser $parser): Response {
        $user = $this->getUser();

        $user->setPreferSmallAvatars( (bool)$parser->get('sma', false) );
        $user->setDisableFx( (bool)$parser->get('disablefx', false) );
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/setlanguage", name="api_soul_set_language")
     * @param JSONRequestParser $parser
     * @param Request $request
     * @param UserHandler $userHandler
     * @param SessionInterface $session
     * @return Response
     */
    public function soul_settings_set_language(JSONRequestParser $parser, Request $request, UserHandler $userHandler, SessionInterface $session): Response {
        $user = $this->getUser();

        $validLanguages = ['de','fr','en','es'];
        if (!$parser->has('lang', true))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        
        $lang = $parser->get('lang', 'de');
        if (!in_array($lang, $validLanguages))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);

        // Check if the user has seen all news in the previous language
        $previous_lang = $user->getLanguage() ?? $request->getLocale() ?? 'de';
        $seen_news = $userHandler->hasSeenLatestChangelog($user, $previous_lang);

        $user->setLanguage( $lang );
        $session->set('_user_lang',$lang);

        if ($seen_news) $userHandler->setSeenLatestChangelog($user, $lang);
        else $user->setLatestChangelog(null);

        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/defaultrole", name="api_soul_defaultrole")
     * @param JSONRequestParser $parser
     * @param AdminActionHandler $admh
     * @return Response
     */
    public function soul_settings_default_role(JSONRequestParser $parser, AdminActionHandler $admh): Response {
        $user = $this->getUser();

        $asDev = $parser->get('dev', false);
        if ($admh->setDefaultRoleDev($user->getId(), $asDev))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/soul/settings/avatar", name="api_soul_avatar")
     * @param JSONRequestParser $parser
     * @param ConfMaster $conf
     * @return Response
     */
    public function soul_settings_avatar(JSONRequestParser $parser, ConfMaster $conf): Response {

        $payload = $parser->get_base64('image');
        $upload = (int)$parser->get('up', 1);
        $mime = $parser->get('mime');

        $user = $this->getUser();

        if ($upload) {
            if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
            if (!$payload) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            
            $raw_processing = $conf->getGlobalConf()->get(MyHordesConf::CONF_RAW_AVATARS, false);
            $error = $this->user_handler->setUserBaseAvatar($user, $payload, $raw_processing ? UserHandler::ImageProcessingPreferImagick : UserHandler::ImageProcessingForceImagick, $raw_processing ? $mime : null);
            if ($error !== UserHandler::NoError)
                return AjaxResponse::error($error);

            $this->entity_manager->persist( $user );
        } elseif ($user->getAvatar()) {

            $this->entity_manager->remove($user->getAvatar());
            $user->setAvatar(null);
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/avatar/crop", name="api_soul_small_avatar")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_small_avatar(JSONRequestParser $parser): Response
    {
        if (!$parser->has_all(['x', 'y', 'dx', 'dy'], false))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $x  = (int)floor((float)$parser->get('x', 0));
        $y  = (int)floor((float)$parser->get('y', 0));
        $dx = (int)floor((float)$parser->get('dx', 0));
        $dy = (int)floor((float)$parser->get('dy', 0));

        $user = $this->getUser();
        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $error = $this->user_handler->setUserSmallAvatar($user, null, $x, $y, $dx, $dy);
        if ($error !== UserHandler::NoError) return AjaxResponse::error( $error );

        $this->entity_manager->persist($user);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/change_password", name="api_soul_change_password")
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_change_pass(UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, TokenStorageInterface $token): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY') && !$this->isGranted( 'ROLE_CROW' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->isGranted('ROLE_ETERNAL'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $new_pw = $parser->trimmed('pw_new', '');
        if (mb_strlen($new_pw) < 6) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $user
            ->setPassword( $passwordEncoder->encodePassword($user, $parser->trimmed('pw_new')) )
            ->setCheckInt($user->getCheckInt() + 1);

        if ($rm_token = $this->entity_manager->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user]))
            $this->entity_manager->remove($rm_token);

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Dein Passwort wurde erfolgreich geändert. Bitte logge dich mit deinem neuen Passwort ein.', [], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/unremember_me", name="api_soul_unremember_me")
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_unremember(TokenStorageInterface $token): Response
    {
        $user = $this->getUser();
        $user->setCheckInt($user->getCheckInt() + 1);

        if ($rm_token = $this->entity_manager->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user]))
            $this->entity_manager->remove($rm_token);

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Du wurdest erfolgreich von allen Geräten abgemeldet.', [], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/delete_account", name="api_soul_delete_account")
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param UserHandler $userhandler
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_delete_account(UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, UserHandler $userhandler, TokenStorageInterface $token): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan() || $this->isGranted('ROLE_ETERNAL') || $this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $name = $user->getUsername();
        //$userhandler->deleteUser($user);
        $user->setDeleteAfter( new DateTime('+24hour') );
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Auf wiedersehen, %name%. Wir werden dich vermissen und hoffen, dass du vielleicht doch noch einmal zurück kommst.', ['%name%' => $name], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("jx/soul/{id}", name="soul_visit", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function soul_visit(int $id, Request $r): Response
    {
        $current_user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($current_user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var User $user */
    	$user = $this->entity_manager->getRepository(User::class)->find($id);
    	if($user === null || $user === $current_user) 
            return $this->redirect($this->generateUrl('soul_me'));

        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
    	$points = $this->user_handler->getPoints($user);

        $returnUrl = null; // TODO: get the referer, it can be empty!
        if(empty($returnUrl))
            $returnUrl = $this->generateUrl('soul_me');

        $cac = $current_user->getActiveCitizen();
        $uac = $user->getActiveCitizen();
        $citizen_id = ($cac && $uac && $cac->getAlive() && !$cac->getZone() && $cac->getTown() === $uac->getTown()) ? $uac->getId() : null;

        return $this->render( 'ajax/soul/visit.html.twig', $this->addDefaultTwigArgs("soul_visit", [
        	'user' => $user,
            'pictos' => $pictos,
            'points' => round($points),
            'seasons' => $this->entity_manager->getRepository(Season::class)->findAll(),
            'returnUrl' => $returnUrl,
            'citizen_id' => $citizen_id,
        ]));
    }

    /**
     * @Route("api/soul/unsubscribe", name="api_unsubscribe")
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @return Response
     */
    public function unsubscribe_api(JSONRequestParser $parser, SessionInterface $session): Response {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);



        if ($nextDeath->getCod()->getRef() != CauseOfDeath::Poison && $nextDeath->getCod()->getRef() != CauseOfDeath::GhulEaten)
            $last_words = $parser->get('lastwords');
        else $last_words = $this->translator->trans("...der Mörder .. ist.. IST.. AAARGHhh..", [], "game");

        // Here, we delete picto with persisted = 0,
        // and definitively validate picto with persisted = 1
        /** @var Picto[] $pendingPictosOfUser */
        $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUser($user);
        foreach ($pendingPictosOfUser as $pendingPicto) {
            if($pendingPicto->getPersisted() == 0)
                $this->entity_manager->remove($pendingPicto);
            else {
                $pendingPicto->setPersisted(2);
                $this->entity_manager->persist($pendingPicto);
            }
        }

        //$awardRepo = $this->entity_manager->getRepository(AwardPrototype::class);
        //foreach ($pendingPictosOfUser as $pendingPicto) {
        //    if($awardRepo->getAwardsByPicto($pendingPicto->getPrototype()->getLabel()) != null) {
        //        $this->checkAwards($user, $pendingPicto->getPrototype()->getLabel());
        //    }
        //}


        if ($active = $nextDeath->getCitizen()) {
            $active->setActive(false);
            $active->setLastWords( $user->getShadowBan() ? '' : $last_words);
            $nextDeath = CitizenRankingProxy::fromCitizen( $active, true );
            $this->entity_manager->persist( $active );
        }
        
        $nextDeath->setConfirmed(true)->setLastWords( $last_words );

        $this->entity_manager->persist( $nextDeath );
        $this->entity_manager->flush();

        if ($session->has('_town_lang')) {
            $session->remove('_town_lang');
            return AjaxResponse::success()->setAjaxControl(AjaxResponse::AJAX_CONTROL_RESET);
        } else return AjaxResponse::success();
    }

    private function checkAwards(User $user, string $award) {
        //$repo = $this->entity_manager->getRepository(Award::class);
        //$awardList = $this->entity_manager->getRepository(AwardPrototype::class)->getAwardsByPicto($award);
        //$pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByLabel($award);
        //$numPicto = 0;

        //foreach($this->entity_manager->getRepository(Picto::class)->getAllByUserAndPicto($user, $pictoPrototype) as $item) {
        //    /** @var Picto $item */
        //    $numPicto += $item->getCount();
        //}

        //foreach($awardList as $item) {
        //    /** @var AwardPrototype $item */
        //    if($numPicto >= $item->getUnlockQuantity() && !$repo->hasAward($user, $item)) {
        //        $newAward = new Award();
        //        $newAward->setUser($user);
        //        $newAward->setPrototype($item);
        //        $this->entity_manager->persist($newAward);
        //    }
        //}
    }


    /**
     * @Route("jx/soul/death", name="soul_death")
     * @return Response
     */
    public function soul_death_page(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return $this->redirect($this->generateUrl('initial_landing'));

        $pictosDuringTown = $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($user, $nextDeath->getTown());
        $pictosWonDuringTown = [];
        $pictosNotWonDuringTown = [];

        foreach ($pictosDuringTown as $picto) {
            if ($picto->getPrototype()->getName() == "r_ptame_#00") continue;
            if ($picto->getPersisted() > 0)
                $pictosWonDuringTown[] = $picto;
            else
                $pictosNotWonDuringTown[] = $picto;
        }

        $canSeeGazette = $nextDeath->getTown() !== null;
        if($canSeeGazette){
            $citizensAlive = false;
            foreach ($nextDeath->getTown()->getCitizens() as $citizen) {
                if($citizen->getCod() === null){
                    $citizensAlive = true;
                    break;
                }
            }
            if($citizensAlive || $nextDeath->getCod()->getRef() === CauseOfDeath::Radiations) {
                $canSeeGazette = true;
            } else {
                $canSeeGazette = false;
            }
        }


        return $this->render( 'ajax/soul/death.html.twig', [
            'citizen' => $nextDeath,
            'sp' => $nextDeath->getPoints(),
            'pictos' => $pictosWonDuringTown,
            'gazette' => $canSeeGazette,
            'denied_pictos' => $pictosNotWonDuringTown
        ] );
    }

    /**
     * @Route("api/soul/{user_id}/towns_all", name="soul_get_towns")
     * @param int $user_id
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_town_list(int $user_id, JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($user_id);
        if ($user === null) return new Response("");

        $season_id = $parser->get('season', '');
        if(empty($season_id)) return new Response("");

        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['id' => $season_id]);

        $limit = (bool)$parser->get('limit10', true);

        return $this->render( 'ajax/soul/town_list.html.twig', [
            'towns' => $this->entity_manager->getRepository(CitizenRankingProxy::class)->findPastByUserAndSeason($user, $season, $limit),
            'editable' => $user->getId() === $this->getUser()->getId()
        ]);
    }

    /**
     * @Route("jx/help", name="help_me")
     * @return Response
     */
    public function help_me(): Response
    {
        return $this->render( 'ajax/help/shell.html.twig');
    }

    /**
     * @Route("api/soul/app/{id<\d+>}", name="soul_own_app_update")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param RandomGenerator $rand
     * @return Response
     */
    public function api_update_own_app(int $id, JSONRequestParser $parser, RandomGenerator $rand) {
        /** @var ExternalApp $app */
        $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);

        if ($app === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if ($app->getOwner() === null || $app->getOwner() !== $this->getUser()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$parser->has_all( ['contact','url'], true )) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $violations = Validation::createValidator()->validate( $parser->all( true ), new Constraints\Collection([
            'url' => [ new Constraints\Url( ['relativeProtocol' => false, 'protocols' => ['http', 'https'], 'message' => 'a' ] ) ],
            'contact' => [ new Constraints\Email( ['message' => 'v']) ],
            'sk' => [  ]
        ]) );

        if ($violations->count() > 0) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $app->setUrl( $parser->trimmed('url') )->setContact( $parser->trimmed('contact') );
        if ( !$app->getLinkOnly() && $parser->get('sk', null) ) {
            $s = '';
            for ($i = 0; $i < 32; $i++) $s .= $rand->pick(['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f']);
            $app->setSecret( $s );
        }

        $this->entity_manager->persist($app);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}
