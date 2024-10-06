<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\HeroExperienceEntry;
use App\Entity\HeroSkillPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\PictoRollup;
use App\Entity\Season;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\HeroXPType;
use App\Enum\UserSetting;
use App\Interfaces\Entity\PictoRollupInterface;
use App\Service\JSONRequestParser;
use App\Service\Locksmith;
use App\Service\LogTemplateHandler;
use App\Service\User\PictoService;
use App\Service\User\UserCapabilityService;
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use App\Structures\Entity\PictoRollupStructure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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

    /**
     * @param UserUnlockableService $unlockableService
     * @param Locksmith $locksmith
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '', name: 'debit_delete', methods: ['DELETE'])]
    public function reset(
        UserUnlockableService $unlockableService,
        Locksmith $locksmith
    ): JsonResponse {
        $user = $this->getUser();

        $lock = $locksmith->waitForLock("debit_unlock_{$user->getId()}");

        $xp = $unlockableService->getHeroicExperience( $user );
        $all_xp = $unlockableService->getHeroicExperience( $user, include_deductions: false );

        if ($all_xp - $xp < 150)
            return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        if (!$unlockableService->performSkillResetForUser($user, true))
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);

        $lock->release();

        $this->addFlash('notice', $this->translator->trans( 'Du hast deine Fähigkeiten und Heldenerfahrung zurückgesetzt und dafür einen zusätzlichen Fähigkeiten-Punkt erhalten!', [], 'game'));
        return new JsonResponse(['success' => true]);
    }

    /**
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '/hxp/index', name: 'hxp_index', methods: ['GET'])]
    public function index(Packages $assets): JsonResponse {
        return new JsonResponse([
            'common' => [
                'empty'  => $this->translator->trans( 'Keine Heldenerfahrung gesammelt', [], 'soul'),
                'unique' => $this->translator->trans( 'Einmal pro Saison & Reset', [], 'soul'),
                'reset'  => $this->translator->trans( 'Zurückgesetzt!', [], 'soul')
            ],
        ]);
    }

    /**
     * @param Collection<int, HeroExperienceEntry> $entries
     * @param LogTemplateHandler $handler
     * @return array
     */
    protected function renderLogEntries(Collection $entries, LogTemplateHandler $handler): array {
        return array_values($entries->map( function( HeroExperienceEntry $entry) use ($handler): ?array {
            /** @var LogEntryTemplate $template */
            $template = $entry->getLogEntryTemplate();

            $entityVariables = $entry->getVariables();
            $json = [
                'id'         => $entry->getId(),
                'timestamp'  => $entry->getCreated()->getTimestamp(),
                'value'      => $entry->getValue(),
                'type'       => $entry->getType()->value,
                'reset'      => $entry->getReset() > 0,
                'past'       => $entry->getSeason()?->getCurrent() ? null : (
                    $entry->getSeason() === null
                        ? "BETA"
                        : "S{$entry->getSeason()->getNumber()} - " . $this->translator->trans("Saison {$entry->getSeason()->getNumber()}.{$entry->getSeason()->getSubNumber()}", [], 'season')
                ),
            ];

            if ($entry->isDisabled()) $json['text'] = null;
            elseif (!$template) $json['text'] = "-- error: [{$entry->getId()}] unable to load template --";
            else {
                $variableTypes = $template->getVariableTypes();
                $transParams = $handler->parseTransParams($variableTypes, $entityVariables);
                try {
                    $json['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
                }
                catch (\Throwable) {
                    $json['text'] = "null";
                }
            }

            return $json;
        } )->filter(fn($v) => $v !== null)->toArray());
    }


    #[Route(path: '/hxp', name: 'hxp_list', methods: ['GET'])]
    public function hxp_list(Request $request, EntityManagerInterface $em, LogTemplateHandler $handler, TagAwareCacheInterface $gameCachePool): JsonResponse {

        static $elements = 50;

        $after = (int)$request->query->get('after', '0');
        $focus = (int)$request->query->get('focus', '0');

        $key = "hxplog_{$this->getUser()->getId()}_n{$elements}_a{$after}_f{$focus}";

        $data = $gameCachePool->get($key, function (ItemInterface $item) use ($after, $focus, $em, $handler, $elements) {
            $item->expiresAfter(86400)->tag(["user-{$this->getUser()->getId()}-hxp", 'hxp']);

            $qb = $em->createQueryBuilder()->select('x')
                ->from(HeroExperienceEntry::class, 'x')
                // Join ranking proxies so we can observe their DISABLED state
                ->leftJoin(TownRankingProxy::class, 't', 'WITH', 'x.town = t.id')
                ->leftJoin(CitizenRankingProxy::class, 'c', 'WITH', 'x.citizen = c.id')
                // Scope to given user
                ->where('x.user = :user')->setParameter('user', $this->getUser())
                // Disregard legacy
                ->andWhere('x.type != :legacy')->setParameter('legacy', HeroXPType::Legacy)
                // Disregard disabled entries
                ->andWhere('x.disabled = 0')
                ->andWhere('(t.disabled = 0 OR t.disabled IS NULL)')
                ->andWhere('(c.disabled = 0 OR c.disabled IS NULL)')
                ->orderBy('x.id', 'DESC')->setMaxResults($elements + 1);

            if ($after > 0)
                $qb->andWhere('x.id < :after')->setParameter('after', $after);

            if ($focus > 0)
                $qb->andWhere('x.town = :tid')->setParameter('tid', $focus);

            return $this->renderLogEntries(new ArrayCollection($qb->getQuery()->getResult()), $handler);
        });

        return new JsonResponse([
            'entries' => $data,
            'additional' => count($data) > $elements
        ]);

    }
}
