<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\PictoRollup;
use App\Entity\Season;
use App\Entity\User;
use App\Enum\HeroXPType;
use App\Enum\UserSetting;
use App\Interfaces\Entity\PictoRollupInterface;
use App\Service\JSONRequestParser;
use App\Service\Locksmith;
use App\Service\User\PictoService;
use App\Service\User\UserCapabilityService;
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use App\Structures\Entity\PictoRollupStructure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/user/soul/skills', name: 'rest_user_soul_skills_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class SkillController extends CustomAbstractCoreController
{
    /**
     * @param HeroSkillPrototype $skill
     * @param UserUnlockableService $unlockableService
     * @param Locksmith $locksmith
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '/{id}', name: 'debit_unlock', methods: ['PATCH'])]
    public function list(
        HeroSkillPrototype $skill,
        UserUnlockableService $unlockableService,
        Locksmith $locksmith
    ): JsonResponse {
        $user = $this->getUser();

        if (!$skill->isEnabled() || $skill->isLegacy())
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $lock = $locksmith->waitForLock("debit_unlock_{$user->getId()}");

        $validSkills = $unlockableService->getUnlockableHeroicSkillsByUser( $user );
        if (!in_array($skill, $validSkills))
            return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        $xp = $unlockableService->getHeroicExperience( $user );
        if ($skill->getDaysNeeded() > $xp)
            return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        if (!$unlockableService->recordHeroicExperience($user, HeroXPType::Global, -$skill->getDaysNeeded(), 'hxp_debit_base', season: true))
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);

        if (!$unlockableService->unlockSkillForUser( $user, $skill, true ))
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);

        $lock->release();

        $this->addFlash('notice', $this->translator->trans( 'Du hast eine neue Fähigkeit erworben. Herzlichen Glückwunsch!', [], 'game'));
        return new JsonResponse(['success' => true]);
    }
}
