<?php

namespace App\Controller\REST\User\Settings;

use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\User;
use App\Entity\PinnedForum;
use App\Enum\UserSetting;
use App\Service\PermissionHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


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
    public function toggleForumFlag(Forum $forum, string $flag, bool $value, PermissionHandler $perm, EntityManagerInterface $em): JsonResponse 
    {
        $isUnpin = $flag === 'pin' && $value === false;
        $user = $this->getUser();

        if (!$isUnpin && !$perm->checkEffectivePermissions($user, $forum, ForumUsagePermissions::PermissionRead)) {
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);
        }

        if ($forum->getTown()) {
            return new JsonResponse(status: Response::HTTP_FORBIDDEN);
        }

        switch ($flag) {
            case 'mute':
                $collection = $user->getMutedForums();
                if ($value && !$collection->contains($forum)) {
                    $collection->add($forum);
                } elseif (!$value && $collection->contains($forum)) {
                    $collection->removeElement($forum);
                }
                break;

            case 'pin':
                $pinnedRepository = $em->getRepository(PinnedForum::class);
                $pinnedForum = $pinnedRepository->findOneBy([
                    'user' => $user,
                    'forum' => $forum
                ]);

                if ($value && !$pinnedForum) {
                    if ($user->getPinnedForums()->count() >= 6) {
                        return new JsonResponse(status: Response::HTTP_NOT_ACCEPTABLE);
                    }
    
                    $newPinnedForum = new PinnedForum();
                    $newPinnedForum->setUser($user);
                    $newPinnedForum->setForum($forum);
                    $newPinnedForum->setPosition($user->getPinnedForums()->count() + 1);
                    $em->persist($newPinnedForum);
                } elseif (!$value && $pinnedForum) {
                    $em->remove($pinnedForum);
                }
                break;

            default:
                return new JsonResponse(status: Response::HTTP_NOT_FOUND);
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/pinned-forums-reorder', name: 'reorder_pinned_forums', methods: ['POST'])]
    public function reorderPinnedForums(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !is_array($data)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        foreach ($data as $item) {
            $pinnedForum = $em->getRepository(PinnedForum::class)->findOneBy([
                'user' => $user,
                'forum' => $item['id']
            ]);

            if ($pinnedForum) {
                $pinnedForum->setPosition($item['position']);
                $em->persist($pinnedForum);
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Order updated successfully']);
    }
}
