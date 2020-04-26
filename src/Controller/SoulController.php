<?php

namespace App\Controller;

use App\Entity\Avatar;
use App\Entity\Citizen;
use App\Entity\User;
use App\Entity\Picto;
use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayTextPage;
use App\Exception\DynamicAjaxResetException;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Imagick;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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

    const ErrorAvatarBackendUnavailable      = ErrorHelper::BaseAvatarErrors + 1;
    const ErrorAvatarTooLarge                = ErrorHelper::BaseAvatarErrors + 2;
    const ErrorAvatarFormatUnsupported       = ErrorHelper::BaseAvatarErrors + 3;
    const ErrorAvatarImageBroken             = ErrorHelper::BaseAvatarErrors + 4;
    const ErrorAvatarResolutionUnacceptable  = ErrorHelper::BaseAvatarErrors + 5;
    const ErrorAvatarProcessingFailed        = ErrorHelper::BaseAvatarErrors + 6;
    const ErrorAvatarInsufficientCompression = ErrorHelper::BaseAvatarErrors + 7;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
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
        $points = 0;

        if($this->getUser()->getSoulPoints() >= 100) {
            $points += 13;
        }
        if($this->getUser()->getSoulPoints() >= 500) {
            $points += 33;
        }
        if($this->getUser()->getSoulPoints() >= 1000) {
            $points += 66;
        }
        if($this->getUser()->getSoulPoints() >= 2000) {
            $points += 132;
        }
        if($this->getUser()->getSoulPoints() >= 3000) {
            $points += 198;
        }

        foreach ($pictos as $picto) {
            switch($picto["name"]){
                case "r_heroac_#00": case "r_explor_#00":
                    if ($picto["c"] >= 15)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_cookr_#00": case "r_cmplst_#00": case "r_camp_#00": case "r_drgmkr_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 25)
                        $points += 6.5;
                    break;
                case "r_animal_#00":
                    if ($picto["c"] >= 30)
                        $points += 3.5;
                    if ($picto["c"] >= 60)
                        $points += 6.5;
                    break;
                case "r_chstxl_#00": case "r_ruine_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    break;
                case "r_build_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 200)
                        $points += 6.5;
                    break;
                case "status_clean_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 75)
                        $points += 6.5;
                    break;
                case "r_ebuild_#00":
                    if ($picto["c"] >= 1)
                        $points += 3.5;
                    if ($picto["c"] >= 3)
                        $points += 6.5;
                    break;
                case "r_digger_#00":
                    if ($picto["c"] >= 50)
                        $points += 3.5;
                    if ($picto["c"] >= 300)
                        $points += 6.5;
                    break;
                case "r_deco_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 250)
                        $points += 6.5;
                    break;
                case "r_explo2_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    break;
                case "r_guide_#00":
                    if ($picto["c"] >= 300)
                        $points += 3.5;
                    if ($picto["c"] >= 1000)
                        $points += 6.5;
                    break;
                case "r_theft_#00": case "r_jtamer_#00": case "r_jrangr_#00": case "r_jguard_#00": case "r_jermit_#00":
                case "r_jtech_#00": case "r_jcolle_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_maso_#00": case "r_guard_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 40)
                        $points += 6.5;
                    break;
                case "r_surlst_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    if ($picto["c"] >= 30)
                        $points += 10;
                    if ($picto["c"] >= 50)
                        $points += 13;
                    if ($picto["c"] >= 100)
                        $points += 16.5;
                    break;
                case "r_suhard_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    if ($picto["c"] >= 20)
                        $points += 10;
                    if ($picto["c"] >= 40)
                        $points += 13;
                    break;
                case "r_doutsd_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    break;
                case "r_door_#00":
                    if($picto["c"] >= 1)
                        $points += 3.5;
                    if($picto["c"] >= 5)
                        $points += 6.5;
                    break;
                case "r_wondrs_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    if($picto["c"] >= 50)
                        $points += 6.5;
                    break;
                case "r_rp_#00":
                    if($picto["c"] >= 5)
                        $points += 3.5;
                    if($picto["c"] >= 10)
                        $points += 6.5;
                    if($picto["c"] >= 20)
                        $points += 10;
                    if($picto["c"] >= 30)
                        $points += 13;
                    if($picto["c"] >= 40)
                        $points += 16.5;
                    if($picto["c"] >= 60)
                        $points += 20;
                    break;
                case "r_winbas_#00":
                    if($picto["c"] >= 2)
                        $points += 13;
                    if($picto["c"] >= 5)
                        $points += 20;
                    break;
                case "r_wintop_#00":
                    if($picto["c"] >= 1)
                        $points += 20;
                    break;
                case "small_zombie_#00":
                    if($picto["c"] >= 100)
                        $points += 3.5;
                    if($picto["c"] >= 200)
                        $points += 6.5;
                    if($picto["c"] >= 300)
                        $points += 10;
                    if($picto["c"] >= 800)
                        $points += 13;
                    break;
            }
        }

        return $this->render( 'ajax/soul/me.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'pictos' => $pictos,
            'points' => round($points, 0)
        ]));
    }

    /**
     * @Route("jx/soul/news", name="soul_news")
     * @return Response
     */
    public function soul_news(): Response
    {
        return $this->render( 'ajax/soul/news.html.twig', $this->addDefaultTwigArgs("soul_news", null) );
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
        $rp = $this->entity_manager->getRepository(FoundRolePlayText::class)->findOneById($id);
        if($rp === null || !$this->getUser()->getFoundTexts()->contains($rp)){
            return $this->redirect($this->generateUrl('soul_rps'));
        }

        if($page > count($rp->getText()->getPages()))
            return $this->redirect($this->generateUrl('soul_rps'));

        $pageContent = $this->entity_manager->getRepository(RolePlayTextPage::class)->findOneByRpAndPageNumber($rp->getText(), $page);

        return $this->render( 'ajax/soul/view_rp.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'page' => $pageContent,
            'rp' => $rp,
            'current' => $page
        )));
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

                $im_image->coalesceImages();
                $im_image->resetImagePage('0x0');
                $w = $im_image->getImageWidth();
                $h = $im_image->getImageHeight();

                if ($w / $h < 0.1 || $h / $w < 0.1 || $h < 30 || $w < 90)
                    return AjaxResponse::error( self::ErrorAvatarResolutionUnacceptable );

                if ( max($w,$h) > 200 &&
                    !$im_image->resizeImage(
                        min(200,max(100,$w,$h)),
                        min(200,max(100,$w,$h)),
                        imagick::FILTER_SINC, 1, true )
                ) return AjaxResponse::error( self:: ErrorAvatarProcessingFailed );

                $im_image->setImageBackgroundColor('black');
                $im_image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                if ($im_image->getImageFormat() !== "GIF")
                    $im_image = $im_image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

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

            if ($im_image->getImageFormat() === 'GIF') {
                $im_image->coalesceImages();
                $im_image->resetImagePage('0x0');
                $im_image->setFirstIterator();
            }

            if (!$im_image->cropImage( $dx, $dy, $x, $y ))
                return AjaxResponse::error(self::ErrorAvatarProcessingFailed);

            if ($im_image->getImageFormat() === 'GIF') {
                $im_image->resetImagePage('0x0');
                $im_image->setFirstIterator();
            }

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
}
