<?php

namespace App\Controller;

use App\Entity\Avatar;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\HeroSkillPrototype;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\Picto;
use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayTextPage;
use App\Exception\DynamicAjaxResetException;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use Imagick;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class SoulController extends AbstractController
{
    protected $entity_manager;
    protected $user_factory;
    private $asset;

    const ErrorAvatarBackendUnavailable      = ErrorHelper::BaseAvatarErrors + 1;
    const ErrorAvatarTooLarge                = ErrorHelper::BaseAvatarErrors + 2;
    const ErrorAvatarFormatUnsupported       = ErrorHelper::BaseAvatarErrors + 3;
    const ErrorAvatarImageBroken             = ErrorHelper::BaseAvatarErrors + 4;
    const ErrorAvatarResolutionUnacceptable  = ErrorHelper::BaseAvatarErrors + 5;
    const ErrorAvatarProcessingFailed        = ErrorHelper::BaseAvatarErrors + 6;
    const ErrorAvatarInsufficientCompression = ErrorHelper::BaseAvatarErrors + 7;
    const ErrorUserEditPasswordIncorrect   = ErrorHelper::BaseAvatarErrors + 8;

    public function __construct(EntityManagerInterface $em, UserFactory $uf, Packages $a)
    {
        $this->entity_manager = $em;
        $this->user_factory = $uf;
        $this->asset = $a;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null ): array {
        $data = $data ?? [];

        $data["soul_tab"] = $section;

        return $data;
    }

    /**
     * @Route("jx/soul/me", name="soul_me")
     * @return Response
     */
    public function soul_me(): Response
    {
        // Get all the picto & count points
        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($this->getUser());
    	$points = $this->user_factory->getPoints($this->getUser());
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($this->getUser()->getHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($this->getUser()->getHeroDaysSpent());

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;

        $progress = $nextSkill !== null ? ($this->getUser()->getHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/me.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'pictos' => $pictos,
            'points' => round($points, 0),
            'latestSkill' => $latestSkill,
            'progress' => floor($progress),
        ]));
    }

    /**
     * @Route("jx/soul/heroskill", name="soul_heroskill")
     * @return Response
     */
    public function soul_heroskill(): Response
    {
        // Get all the picto & count points
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($this->getUser()->getHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($this->getUser()->getHeroDaysSpent());

        $allSkills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findAll();

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;
        $progress = $nextSkill !== null ? ($this->getUser()->getHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/heroskills.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'latestSkill' => $latestSkill,
            'nextSkill' => $nextSkill,
            'progress' => floor($progress),
            'skills' => $allSkills
        ]));
    }

    /**
     * @Route("jx/soul/news", name="soul_news")
     * @return Response
     */
    public function soul_news(): Response
    {
        $news = $this->entity_manager->getRepository(Changelog::class)->findByLang($this->getUser()->getLanguage());
        return $this->render( 'ajax/soul/news.html.twig', $this->addDefaultTwigArgs("soul_news", [
            'news' => $news
        ]) );
    }

    /**
     * @Route("jx/soul/settings", name="soul_settings")
     * @return Response
     */
    public function soul_settings(): Response
    {
        return $this->render( 'ajax/soul/settings.html.twig', $this->addDefaultTwigArgs("soul_settings", null) );
    }

    /**
     * @Route("jx/soul/rps", name="soul_rps")
     * @return Response
     */
    public function soul_rps(): Response
    {
        $rps = $this->entity_manager->getRepository(FoundRolePlayText::class)->findByUser($this->getUser());
        return $this->render( 'ajax/soul/rps.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'rps' => $rps
        )));
    }

    /**
     * @Route("jx/soul/rps/read/{id}-{page}", name="soul_rp", requirements={"id"="\d+", "page"="\d+"})
     * @return Response
     */
    public function soul_view_rp(int $id, int $page): Response
    {
        $rp = $this->entity_manager->getRepository(FoundRolePlayText::class)->find($id);
        if($rp === null || !$this->getUser()->getFoundTexts()->contains($rp)){
            return $this->redirect($this->generateUrl('soul_rps'));
        }

        if($page > count($rp->getText()->getPages()))
            return $this->redirect($this->generateUrl('soul_rps'));

        $pageContent = $this->entity_manager->getRepository(RolePlayTextPage::class)->findOneByRpAndPageNumber($rp->getText(), $page);

        preg_match('/%asset%([a-zA-Z0-9.\/]+)%endasset%/', $pageContent->getContent(), $matches);

        if(count($matches) > 0) {
            $pageContent->setContent(preg_replace("={$matches[0]}=", "<img src='" . $this->asset->getUrl($matches[1]) . "' alt='' />", $pageContent->getContent()));
        }

        return $this->render( 'ajax/soul/view_rp.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'page' => $pageContent,
            'rp' => $rp,
            'current' => $page
        )));
    }

    /**
     * @Route("jx/soul/town/{id}", name="soul_view_town", requirements={"id"="\d+"})
     * @return Response
     */
    public function soul_view_town(int $id): Response
    {
        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($id);
        if($town === null){
            return $this->redirect($this->generateUrl('soul_me'));
        }

        return $this->render( 'ajax/soul/view_town.html.twig', $this->addDefaultTwigArgs("soul_me", array(
            'town' => $town,
        )));
    }

    /**
     * @Route("jx/soul/town/add_comment", name="soul_add_comment")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_add_comment(JSONRequestParser $parser): Response
    {
        $id = $parser->get("id");
        /** @var CitizenRankingProxy $citizenProxy */
        $citizenProxy = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($id);
        if ($citizenProxy === null || $citizenProxy->getUser() !== $this->getUser() )
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
     * @return Response
     */
    public function soul_settings_generateid(EntityManagerInterface $entityManager): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId(md5($user->getEmail() . mt_rand()));
        $entityManager->persist( $user );
        $entityManager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/deleteid", name="api_soul_settings_deleteid")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function soul_settings_deleteid(EntityManagerInterface $entityManager): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId('');
        $entityManager->persist( $user );
        $entityManager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/common", name="api_soul_common")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_common(JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->getUser();

        $user->setPreferSmallAvatars( (bool)$parser->get('sma', false) );
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/setlanguage", name="api_soul_set_language")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_set_language(JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->getUser();

        $validLanguages = ['de','fr','en','es'];
        if (!$parser->has('lang', true))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        
        $lang = $parser->get('lang', 'de');
        if (!in_array($lang, $validLanguages))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);

        $user->setLanguage( $lang );
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/defaultrole", name="api_soul_defaultrole")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_defaultrole(JSONRequestParser $parser, AdminActionHandler $admh): Response {
        /** @var User $user */
        $user = $this->getUser();

        $asDev = $parser->get('dev', false);
        if ($admh->setDefaultRoleDev($user->getId(), $asDev))
            return new AjaxResponse( ['success' => true] );

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/soul/settings/avatar", name="api_soul_avatar")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_avatar(JSONRequestParser $parser): Response {

        if (!extension_loaded('imagick')) return AjaxResponse::error(self::ErrorAvatarBackendUnavailable );

        $payload = $parser->get_base64('image', null);
        $upload = (int)$parser->get('up', 1);

        /** @var User $user */
        $user = $this->getUser();

        if ($upload) {

            if (!$payload) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            // Processing limit: 3MB
            if (strlen( $payload ) > 3145728) return AjaxResponse::error( self::ErrorAvatarTooLarge );

            $im_image = new Imagick();
            $processed_image_data = null;

            try {
                if (!$im_image->readImageBlob($payload))
                    return AjaxResponse::error( self::ErrorAvatarImageBroken );

                if (!in_array($im_image->getImageFormat(), ['GIF','JPEG','BMP','PNG','WEBP']))
                    return AjaxResponse::error( self::ErrorAvatarFormatUnsupported );

                if ($im_image->getImageFormat() === 'GIF') {
                    $im_image->coalesceImages();
                    $im_image->resetImagePage('0x0');
                    $im_image->setFirstIterator();
                }

                $w = $im_image->getImageWidth();
                $h = $im_image->getImageHeight();

                if ($w / $h < 0.1 || $h / $w < 0.1 || $h < 16 || $w < 16)
                    return AjaxResponse::error( self::ErrorAvatarResolutionUnacceptable, [$w,$h,$im_image->getImageFormat() ] );

                if ( (max($w,$h) > 200 || min($w,$h < 90)) &&
                    !$im_image->resizeImage(
                        min(200,max(90,$w,$h)),
                        min(200,max(90,$w,$h)),
                        imagick::FILTER_SINC, 1, true )
                ) return AjaxResponse::error( self:: ErrorAvatarProcessingFailed );

                if ($im_image->getImageFormat() === 'GIF')
                    $im_image->setFirstIterator();

                $w_final = $im_image->getImageWidth();
                $h_final = $im_image->getImageHeight();

                switch ($im_image->getImageFormat()) {
                    case 'JPEG':
                        $im_image->setImageCompressionQuality ( 90 );
                        break;
                    case 'PNG':
                        $im_image->setOption('png:compression-level', 9);
                        break;
                    case 'GIF':
                        $im_image->setOption('optimize', true);
                        break;
                    default: break;
                }

                $processed_image_data = $im_image->getImagesBlob();
                if (strlen($processed_image_data) > 1048576) return AjaxResponse::error( self::ErrorAvatarInsufficientCompression );
            } catch (Exception $e) {
                return AjaxResponse::error( self::ErrorAvatarProcessingFailed );
            }

            if (!($avatar = $user->getAvatar())) {
                $avatar = new Avatar();
                $user->setAvatar($avatar);
            }

            $name = md5( $processed_image_data );

            $avatar
                ->setChanged(new DateTime())
                ->setFilename( $name )
                ->setSmallName( $name )
                ->setFormat( strtolower( $im_image->getImageFormat() ) )
                ->setImage( $processed_image_data )
                ->setX( $w_final )
                ->setY( $h_final )
                ->setSmallImage( null );

            $this->entity_manager->persist( $user );
            $this->entity_manager->persist( $avatar );
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

        /** @var User $user */
        $user = $this->getUser();
        $avatar = $user->getAvatar();

        if (!$avatar || $avatar->isClassic())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (
            $x < 0 || $dx < 0 || $x + $dx > $avatar->getX() ||
            $y < 0 || $dy < 0 || $y + $dy > $avatar->getY()
        ) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest, [$x,$y,$dx,$dy,$avatar->getX(),$avatar->getY()]);

        $im_image = new Imagick();
        $processed_image_data = null;

        try {
            if (!$im_image->readImageBlob(stream_get_contents( $avatar->getImage() )))
                return AjaxResponse::error(self::ErrorAvatarImageBroken);

            $im_image->setFirstIterator();

            if (!$im_image->cropImage( $dx, $dy, $x, $y ))
                return AjaxResponse::error(self::ErrorAvatarProcessingFailed);

            $im_image->setFirstIterator();

            $iw = $im_image->getImageWidth(); $ih = $im_image->getImageHeight();
            if ($iw < 90 || $ih < 30 || ($ih/$iw != 3)) {
                $new_height = max(30,$ih);
                $new_width = $new_height * 3;
                if (!$im_image->resizeImage(
                    $new_width, $new_height,
                    imagick::FILTER_SINC, 1, true ))
                    return AjaxResponse::error(self::ErrorAvatarProcessingFailed);
            }

            if ($im_image->getImageFormat() === 'GIF')
                $im_image->setOption('optimize', true);

            $processed_image_data = $im_image->getImagesBlob();
            if (strlen($processed_image_data) > 1048576) return AjaxResponse::error( self::ErrorAvatarInsufficientCompression );

        } catch (Exception $e) {
            return AjaxResponse::error( self::ErrorAvatarProcessingFailed );
        }

        $name = md5( (new DateTime())->getTimestamp() );

        $avatar
            ->setSmallName( $name )
            ->setSmallImage( $processed_image_data );

        $this->entity_manager->persist($avatar);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/change_password", name="api_soul_change_password")
     * @param TranslatorInterface $trans
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_change_pass(TranslatorInterface $trans, UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, TokenStorageInterface $token): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $new_pw = $parser->trimmed('pw_new', '');
        if (mb_strlen($new_pw) < 6) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $user->setPassword( $passwordEncoder->encodePassword($user, $parser->trimmed('pw_new')) );

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $trans->trans('Dein Passwort wurde erfolgreich geändert. Bitte logge dich mit deinem neuen Passwort ein.', [], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/delete_account", name="api_soul_delete_account")
     * @param TranslatorInterface $trans
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param DeathHandler $death
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_delete_account(TranslatorInterface $trans, UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, DeathHandler $death, TokenStorageInterface $token): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $name = $user->getUsername();
        $user->setEmail("$ deleted <{$user->getId()}>")->setName("$ deleted <{$user->getId()}>")->setPassword(null)->setRightsElevation(0);
        if ($user->getAvatar()) {
            $this->entity_manager->remove($user->getAvatar());
            $user->setAvatar(null);
        }
        $citizen = $user->getActiveCitizen();
        if ($citizen) {
            $death->kill( $citizen, CauseOfDeath::Headshot, $r );
            foreach ($r as $re) $this->entity_manager->remove($re);
        }

        $this->entity_manager->flush();

        $this->addFlash( 'notice', $trans->trans('Auf wiedersehen, %name%. Wir werden dich vermissen und hoffen, dass du vielleicht doch noch einmal zurück kommst.', ['%name%' => $name], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("jx/soul/{id}", name="soul_visit", requirements={"id"="\d+"})
     * @return Response
     */
    public function soul_visit(int $id): Response
    {
    	$user = $this->entity_manager->getRepository(User::class)->find($id);
    	if($user === null || $user === $this->getUser()){
            return $this->redirect($this->generateUrl('soul_me'));
        }

        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
    	$points = $this->user_factory->getPoints($user);

        return $this->render( 'ajax/soul/visit.html.twig', $this->addDefaultTwigArgs("soul_visit", [
        	'user' => $user,
            'pictos' => $pictos,
            'points' => round($points, 0)
        ]));
    }

    /**
     * @Route("jx/soul/{id}/town/{idtown}", name="soul_view_town_foreign", requirements={"id"="\d+", "idtown"="\d+"})
     * @param int $id
     * @param int $idtown
     * @return Response
     */
    public function soul_view_town_foreign(int $id, int $idtown): Response
    {
    	$user = $this->entity_manager->getRepository(User::class)->find($id);
    	if ($user === null) return $this->redirect($this->generateUrl('soul_me'));
        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($idtown);
        if($town === null)
            return $this->redirect($this->generateUrl('soul_visit', ['id' => $id]));

        return $this->render( 'ajax/soul/view_town_foreign.html.twig', $this->addDefaultTwigArgs("soul_visit", array(
        	'user' => $user,
            'town' => $town,
        )));
    }

    /**
     * @Route("api/soul/unsubscribe", name="api_unsubscribe")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param SessionInterface $session
     * @param TranslatorInterface $trans
     * @return Response
     */
    public function unsubscribe_api(JSONRequestParser $parser, EntityManagerInterface $em, SessionInterface $session, TranslatorInterface $trans): Response {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);



        if ($nextDeath->getCod()->getRef() != CauseOfDeath::Poison && $nextDeath->getCod()->getRef() != CauseOfDeath::GhulEaten)
            $last_words = $parser->get('lastwords');
        else $last_words = $trans->trans("...der Mörder .. ist.. IST.. AAARGHhh..", [], "game");

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

          /** @var User|null $user */
        if ($active = $nextDeath->getCitizen()) {
            $active->setActive(false);
            $active->setLastWords($last_words);
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

    /**
     * @Route("jx/soul/death", name="soul_death")
     * @return Response
     */
    public function soul_deathpage(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return $this->redirect($this->generateUrl('initial_landing'));

        $pictosDuringTown = $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($user, $nextDeath->getTown());
        $pictosWonDuringTown = [];
        $pictosNotWonDuringTown = [];

        foreach ($pictosDuringTown as $picto)
            if ($picto->getPersisted() > 0)
                $pictosWonDuringTown[] = $picto;
            else
                $pictosNotWonDuringTown[] = $picto;

        return $this->render( 'ajax/soul/death.html.twig', [
            'citizen' => $nextDeath,
            'sp' => $nextDeath->getPoints(),
            'pictos' => $pictosWonDuringTown,
            'gazette' => $nextDeath->getTown() !== null,
            'denied_pictos' => $pictosNotWonDuringTown
        ] );
    }
}
