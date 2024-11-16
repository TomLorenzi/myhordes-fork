<?php

namespace App\Controller\REST\Town\Core;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Building;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Traits\Controller\EventChainProcessor;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/rest/v1/town/core/building', name: 'rest_town_core_building_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
class BuildingController extends CustomAbstractCoreController
{
    use EventChainProcessor;

    public function __construct(
        ConfMaster $conf,
        TranslatorInterface $translator,
        //private readonly TagAwareCacheInterface $gameCachePool,
    )
    {
        parent::__construct($conf, $translator);
    }

    /**
     * @param Packages $asset
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(Packages $asset, EntityManagerInterface $em): JsonResponse {
        return new JsonResponse([
            'common' => [
                'defense' => $this->translator->trans('Verteidigung', [], 'buildings'),
                'defense_base' => $this->translator->trans('Basisverteidigung', [], 'buildings'),
                'defense_broken' => $this->translator->trans('Beschädigte Verteidigung: {defense} / {max}', [], 'game'),
                'defense_bonus' => $this->translator->trans('Bonusverteidigung', [], 'buildings'),
                'defense_temp' => $this->translator->trans('Temporärer Verteidigungsbonus', [], 'buildings'),
                'state' => $this->translator->trans('Zustand:', [], 'game'),
                'level' => $this->translator->trans('Level {lv}', [], 'game'),

                'show_list' => $this->translator->trans('Gebäudeliste einblenden', [], 'game'),
                'close' => $this->translator->trans('Schließen', [], 'global'),
            ],
            'page' => [
                'g1' => $asset->getUrl('build/images/building/small_parent.gif'),
                'g2' => $asset->getUrl('build/images/building/small_parent2.gif'),
                'ap_bar' => $asset->getUrl('build/images/building/building_barStart.gif'),
                'hp_bar' => $asset->getUrl('build/images/building/building_barStartBroken.png'),
                'hp_ratio_help' => $this->translator->trans('Jeder {divap}, der in die Reparatur investiert wird, beseitigt {hprepair} Schadenspunkte an einem Bauwerk.', [], 'game'),
                'ap_ratio_help' => $this->translator->trans('Zum Bau dieses Bauprojekts fehlen noch {ap} AP', [], 'game'),
            ]
        ]);
    }

    public function renderBuilding(Building $building, bool $voted = false): array {
        return [
            'i' => $building->getId(),
            'p' => $building->getPrototype()->getId(),
            'l' => $building->getLevel(),
            'c' => $building->getComplete(),
            'd0' => $building->getDefense(),
            'db' => $building->getDefenseBonus(),
            'dt' => $building->getTempDefenseBonus(),
            'a' => $building->getComplete()
                ? [$building->getHp(), $building->getPrototype()->getHp()]
                : [$building->getAp(), $building->getPrototype()->getAp()],
            ...($voted ? ['v' => true] : [])
        ];
    }

    #[Route(path: '/list', name: 'buildings_get', methods: ['GET'])]
    public function inventory(Request $request,  EntityManagerInterface $em, EventProxyService $proxy): JsonResponse {
        $town = $this->getUser()->getActiveCitizen()->getTown();

        $completed = $request->query->get('completed', '0') === '1';

        $buildings = $completed
            ? $town->getBuildings()->matching((new Criteria())->where(Criteria::expr()->eq('complete', true)))
            : $town->getBuildings();

        $mv = [null, 0];
        if (!$completed)
            $mv = $buildings->reduce( function(array $v, Building $building) {
                if ($building->getComplete()) return $v;
                $votes = $building->getBuildingVotes()->count();
                return $votes > $v[1] ? [$building->getId(), $votes] : $v;
            }, $mv );

        return new JsonResponse([
            'buildings' => $buildings->map(fn(Building $b) => $this->renderBuilding($b, $b->getId() === $mv[0]))->toArray()
        ]);
    }
}
