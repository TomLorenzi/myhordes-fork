<?php

namespace App\Controller\REST\User\Settings;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AccountRestriction;
use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\Media\ImageService;
use App\Service\UserHandler;
use App\Structures\Image;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function App\Controller\REST\User\mb_strlen;
use function App\Controller\REST\User\str_contains;


/**
 * @Route("/rest/v1/user/settings/avatar", name="rest_user_settings_avatar_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 * @method User getUser()
 */
class AvatarController extends AbstractController
{

    /**
     * @Route("", name="base", methods={"GET"})
     * @Cache(smaxage="43200", mustRevalidate=false, public=true)
     * @param Packages $assets
     * @param TranslatorInterface $trans
     * @return JsonResponse
     */
    public function index(Packages $assets, TranslatorInterface $trans): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [
                    'no_avatar' => $trans->trans('Damit andere Spieler dich besser erkennen, kannst du hier ein Profilbild hochladen', [], 'soul'),

                    'edit_help' => $trans->trans('Wenn du möchtest, kannst du deinen Avatar vor dem Speichern noch zuschneiden. Ziehe dafür einfach das weiße Auswahlrechteck auf den Bildausschnitt, den du als Avatar verwenden möchtest. Du kannst auch den komprimierten 90/30-Bereich einzeln bearbeiten.', [], 'soul'),
                    'edit_help2' => $trans->trans('Bist du fertig, klicke auf "Profilbild speichern" um deinen Avatar hochzuladen.', [], 'soul'),

                    'format_upload' => $trans->trans('Hochgeladenes Bild', [], 'soul'),
                    'format_default' => $trans->trans('Normale Anzeige', [], 'soul'),
                    'format_round' => $trans->trans('Runde Anzeige', [], 'soul'),
                    'format_small' => $trans->trans('Komprimierte Anzeige', [], 'soul'),

                    'info' => $trans->trans('{x} × {y} Pixel, {size}', [], 'soul'),
                    'dimensions' => $trans->trans('Größe {x} × {y} Pixel', [], 'soul'),
                    'none' => $trans->trans('Nicht hochgeladen', [], 'soul'),
                    'fallback' => $trans->trans('Automatisch erzeugt', [], 'soul'),

                    'action_delete' => $trans->trans('Profilbild löschen', [], 'soul'),
                    'action_edit' => $trans->trans('Neues Profilbild hochladen', [], 'soul'),
                    'action_modify' => $trans->trans('Profilbild bearbeiten', [], 'soul'),
                    'action_create' => $trans->trans('Profilbild hochladen', [], 'soul'),
                    'action_cancel' => $trans->trans('Abbrechen', [], 'global'),
                    'action_upload' => $trans->trans('Profilbild speichern', [], 'soul'),

                    'confirm' => $trans->trans('Bestätigen?', [], 'global'),

                    'error_single_file' => $trans->trans('Bitte wähle nur eine einzige Datei aus.', [], 'soul'),
                    'error_too_large' => $trans->trans('Die Datei ist zu groß.', [], 'soul'),
                    'error_unknown_format' => $trans->trans('Dieses Dateiformat wird nicht unterstützt.', [], 'soul'),

                    'edit_auto' => $trans->trans('Komprimierten Ausschnitt automatisch festlegen', [], 'soul'),
                    'edit_manual' => $trans->trans('Ich möchte den komprimierten Ausschnitt selbst festlegen', [], 'soul'),
                    'edit_now' => $trans->trans('Bearbeiten', [], 'soul')
                ],
            ]
        ]);
    }

    private function renderAvatar(User $user, bool $small): ?array {
        $avatar = $user->getAvatar();
        $stream = ($small && !$avatar?->isClassic()) ? $avatar?->getSmallImage() : $avatar?->getImage();
        if (!$stream || (!$small && $avatar->isClassic())) return null;


        return [
            'url' => $this->generateUrl('app_web_avatar', [
                'uid' => $user->getId(),
                'name' => $small ? $avatar->getSmallName() : $avatar->getFilename(),
                'ext' => $avatar->getFormat()
            ]),
            'format' => $avatar->getFormat(),
            'size' => strlen(stream_get_contents( $stream )),
        ];
    }


    /**
     * @Route("/media", name="list", methods={"GET"})
     * @return JsonResponse
     */
    public function fetchMedia(): JsonResponse {
        return new JsonResponse( [
            'default' => $this->renderAvatar( $this->getUser(), false ),
            'round' => null,
            'small' => $this->renderAvatar( $this->getUser(), true ),
        ] );
    }

    /**
     * @Route("/media", name="delete", methods={"DELETE"})
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function deleteMedia(EntityManagerInterface $em): JsonResponse {

        if ($this->getUser()->getAvatar()) {
            $em->remove($this->getUser()->getAvatar());
            $this->getUser()->setAvatar(null);
            $em->flush();
        }

        return new JsonResponse();
    }

    private function validateCrop(?array $crop, ?Image $image = null): ?array {
        if (!$crop) return null;

        try {
            list('height' => $h, 'width' => $w, 'x' => $x, 'y' => $y) = $crop;
            if ($h < 0 || $w < 0 || $x < 0 || $y < 0) return null;

            if ($image) {
                if (($x + $w > $image->width) || ($y + $h > $image->height)) return null;
                if ($x === 0 && $y === 0 && $w === $image->width && $h === $image->height) return null;
            }

            return $crop;
        } catch (\Throwable $t) {
            return null;
        }

    }

    /**
     * @Route("/media", name="upload", methods={"PUT"})
     * @param JSONRequestParser $parser
     * @param UserHandler $userHandler
     * @param ConfMaster $conf
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function uploadMedia(JSONRequestParser $parser, UserHandler $userHandler, ConfMaster $conf, EntityManagerInterface $em): JsonResponse {
        $payload = $parser->get_base64('data');
        $mime = $parser->get('mime');

        $user = $this->getUser();
        if ($userHandler->isRestricted($user, AccountRestriction::RestrictionProfileAvatar))
            return new JsonResponse(status: Response::HTTP_FORBIDDEN);

        if (!$payload) return new JsonResponse(status: Response::HTTP_BAD_REQUEST);

        if (strlen( $payload ) > $conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_UPLOAD, 3145728))
            return new JsonResponse(['error' => UserHandler::ErrorAvatarTooLarge]);

        $image = ImageService::createImageFromData( $payload );
        if (!$image) return new JsonResponse(['error' => UserHandler::ErrorAvatarFormatUnsupported]);

        $cropDefault = $this->validateCrop( $parser->get_array( 'crop' )['default'] ?? null, $image );
        $cropSmall = $this->validateCrop( $parser->get_array( 'crop' )['small'] ?? null, $image );

        if ($cropSmall) {
            list('height' => $h, 'width' => $w, 'x' => $x, 'y' => $y) = $cropSmall;
            $small_image = ImageService::cloneImage( $image );
            ImageService::crop( $small_image, $x, $y, $w, $h );
            $final_w = max( 90, min( $w, 180 ) );
            $final_h = round( $final_w / 3 );
            ImageService::resize( $small_image, $final_w, $final_h );
        } else $small_image = null;

        if ($cropDefault) {
            list('height' => $h, 'width' => $w, 'x' => $x, 'y' => $y) = $cropDefault;
            ImageService::crop( $image, $x, $y, $w, $h );
        }

        $final_d = min(200, max( $image->width, $image->height ));
        if (max( $image->width, $image->height ) > $final_d)
            ImageService::resize( $image, $final_d, $final_d, bestFit: true );

        $converter_formats = ImageService::getCompressionOptions( $image );

        $format = null;
        $data = null;
        foreach ($converter_formats as $test_format) {
            if (!($test_data = ImageService::save( $image, $test_format ))) continue;
            if ($data === null || strlen( $data ) > strlen( $test_data )) {
                $format = $test_format;
                $data = $test_data;
            }
        }

        if (!$data) return new JsonResponse(['error' => UserHandler::ErrorAvatarFormatUnsupported]);

        if (strlen($data) > $conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_STORAGE, 1048576))
            return new JsonResponse(['error' => UserHandler::ErrorAvatarInsufficientCompression]);

        if (!($avatar = $user->getAvatar())) {
            $avatar = new Avatar();
            $user->setAvatar($avatar);
        }

        $avatar
            ->setChanged(new \DateTime())
            ->setFilename( md5( $data ) )
            ->setFormat( strtolower( $format ?? $image->format ) )
            ->setImage( $data )
            ->setX( $image->width )
            ->setY( $image->height );

        if ($small_image && $small_data = ImageService::save( $small_image, $format ?? $image->format ))
            $avatar->setSmallName( md5($small_data) )->setSmallImage( $small_data );
        else $avatar->setSmallName( null )->setSmallImage( null );

        $em->persist( $user );
        $em->flush();

        return new JsonResponse();
    }
}
