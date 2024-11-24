<?php

namespace App\Controller\REST\User\Settings;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AccountRestriction;
use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\Media\ImageService;
use App\Service\PermissionHandler;
use App\Service\UserHandler;
use App\Structures\Image;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @method User getUser()
 */
#[Route(path: '/rest/v1/user/settings/options', name: 'rest_user_settings_options_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class SettingsController extends AbstractController
{
    private function renderSetting(UserSetting $setting): array {
        return [
            'option' => $setting->value,
            'value' => $this->getUser()->getSetting( $setting ),
            'default' => $setting->defaultValue(),
            'isConfigured' => $this->getUser()->hasConfiguredSetting( $setting ),
        ];
    }

    /**
     * @return JsonResponse
     */
    #[Route(path: '/', name: 'get', methods: ['GET'])]
    public function getSettings(): JsonResponse {
        return new JsonResponse( array_values(array_map(
            fn(UserSetting $s) => $this->renderSetting($s),
            array_filter( UserSetting::cases(), fn(UserSetting $s) => $s->isExposedSetting() )
        ) ) );
    }

    /**
     * @param string $option
     * @param bool $value
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/{option}', name: 'toggle_on', defaults: ['value' => true], methods: ['PUT'])]
    #[Route(path: '/{option}', name: 'toggle_off', defaults: ['value' => false], methods: ['DELETE'])]
    public function toggleSetting(string $option, bool $value, EntityManagerInterface $em): JsonResponse {
        if (!($setting = UserSetting::tryFrom( $option ))?->isToggleSetting())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        if (!$setting->isExposedSetting())
            return new JsonResponse(status: Response::HTTP_FORBIDDEN);

        $em->persist( $this->getUser()->setSetting( $setting, $value ) );
        $em->flush();

        return new JsonResponse( $this->renderSetting( $setting ) );
    }

    #[Route(path: '/forum/{id}/flags/{flag}', name: 'forum_flag_on', defaults: ['value' => true], methods: ['PUT'])]
    #[Route(path: '/forum/{id}/flags/{flag}', name: 'forum_flag_off', defaults: ['value' => false], methods: ['DELETE'])]
    public function toggleForumFlag(Forum $forum, string $flag, bool $value, PermissionHandler $perm, EntityManagerInterface $em): JsonResponse {
        $is_unpin = $flag === 'pin' && $value === false;

        if (!$is_unpin && !$perm->checkEffectivePermissions( $this->getUser(), $forum, ForumUsagePermissions::PermissionRead ))
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        if ($forum->getTown())
            return new JsonResponse(status: Response::HTTP_FORBIDDEN);

        $collection = match ( $flag ) {
            'mute' => $this->getUser()->getMutedForums(),
            'pin' => $this->getUser()->getPinnedForums(),
            default => null
        };

        if ($collection === null) return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        if ($flag === 'pin' && $value && !$collection->contains( $forum ) && $collection->count() >= 6)
            return new JsonResponse(status: Response::HTTP_NOT_ACCEPTABLE);

        if ($value) $collection->add( $forum );
        else $collection->removeElement( $forum );

        $em->persist( $this->getUser() );
        $em->flush();

        return new JsonResponse( ['success' => true] );
    }
}
