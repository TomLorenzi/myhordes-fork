<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Service\JSONRequestParser;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/rest/v1/user/soul/editor', name: 'rest_user_soul_editor_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class EditorController extends CustomAbstractCoreController
{

    /**
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    public function index(Packages $assets): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [
                    'header' => $this->translator->trans('Statistiken', [], 'global'),
                ],
            ],
        ]);
    }

    /**
     * @param User $user
     * @param string $source
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '/{id}/unlocks/emotes', name: 'list_emotes', methods: ['GET'])]
    public function list(
        User $user,
        UserHandler $userHandler,
        EntityManagerInterface $em,
        Packages $assets
    ): JsonResponse {

        $results = [];

        $repo = $em->getRepository(Emotes::class);
        $emotes = $repo->getDefaultEmotes();

        $awards = $em->getRepository(Award::class)->getAwardsByUser($user);

        foreach($awards as $entry) {
            /** @var $entry Award */
            if (!$entry->getPrototype() || $entry->getPrototype()->getAssociatedTag() === null) continue;
            $emote = $repo->findByTag($entry->getPrototype()->getAssociatedTag());
            if(!in_array($emote, $emotes)) {
                $emotes[] = $emote;
            }
        }

        foreach($emotes as $entry) {
            /** @var $entry Emotes */
            if ($entry === null) continue;
            $results[$entry->getTag()] = [
                'path' => $entry->getPath(),
                'url' => $assets->getUrl( $entry->getPath() ),
                'i18n' => $entry->getI18n(),
                'orderIndex' => $entry->getOrderIndex()
            ];
        }

        return new JsonResponse([
            'result' => $results,
        ]);
    }

}
