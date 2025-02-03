<?php

namespace App\Controller\REST\Town\Core;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Controller\Town\TownController;
use App\Entity\Building;
use App\Entity\BuildingVote;
use App\Entity\PictoPrototype;
use App\Enum\ActionHandler\PointType;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\EventStages\BuildingValueQuery;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\DoctrineCacheService;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Traits\Controller\ActiveCitizen;
use App\Traits\Controller\EventChainProcessor;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/rest/v1/town/core/building', name: 'rest_town_core_building_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
class BuildingController extends CustomAbstractCoreController
{
    use EventChainProcessor;
    use ActiveCitizen;

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
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(Packages $asset): JsonResponse {
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
                'display_all' => $this->translator->trans('Normale Ansicht', [], 'game'),
                'display_needed' => $this->translator->trans('Materialienverbrauch ansehen', [], 'game'),

                'all' => $this->translator->trans('Alle', [], 'game'),
                'g1' => $asset->getUrl('build/images/building/small_parent.gif'),
                'g2' => $asset->getUrl('build/images/building/small_parent2.gif'),
                'ap_bar' => $asset->getUrl('build/images/building/building_barStart.gif'),
                'hp_bar' => $asset->getUrl('build/images/building/building_barStartBroken.png'),
                'action_build'  => $asset->getUrl('build/images/icons/small_more2.gif'),
                'action_repair' => $asset->getUrl('build/images/icons/small_refine.gif'),
                'temp' => [
                    'icon' => $asset->getUrl('build/images/icons/small_warning.gif'),
                    'title' => $this->translator->trans('Temporäre Konstruktion!', [], 'buildings'),
                    'text' => $this->translator->trans('Diese Konstruktion kann nur einmal verwendet werden. Sie wird nach dem Angriff der Zombiehorde wieder abgerissen.', [], 'buildings'),
                ],
                'hp_ratio_info' => $this->translator->trans('Jeder {divap}, der in die Reparatur investiert wird, beseitigt {hprepair} Schadenspunkte an einem Bauwerk.', [], 'game'),
                'hp_ratio_help' => $this->translator->trans('Für die vollständige Instandsetzung dieses Gebäudes sind noch {remaining} AP erforderlich.', [], 'game'),
                'ap_ratio_help' => $this->translator->trans('Zum Bau dieses Bauprojekts fehlen noch {ap} AP', [], 'game'),

                'vote' => [
                    'help' => $this->translator->trans('Du kannst den Bürgern der Stadt die Errichtung eines bestimmten Gebäudes empfehlen. Klicke dafür auf den Namen des Bauprojektes, das du empfehlen willst.', [], 'game'),
                    'current' => $this->translator->trans('Das folgende Bauprojekt wurde empfohlen:', [], 'game'),
                    'tooltip' => $this->translator->trans('Diese Empfehlung stammt von einem oder mehreren Helden deiner Stadt.', [], 'game'),
                    'can' => $this->translator->trans('Klicke hier, um deinen Mitbürgern die Errichtung dieses Bauwerks zu empfehlen.', [], 'game'),
                ],

                'participate' => $this->translator->trans('Teilnehmen', [], 'game'),
                'abort' => $this->translator->trans('Abbrechen', [], 'global'),
            ]
        ]);
    }

    public function renderBuilding(Building $building, bool $voted = false): array {
        return [
            'i' => $building->getId(),
            'p' => $building->getPrototype()->getId(),
            'l' => $building->getLevel(),
            'c' => $building->getComplete(),
            't' => $building->getPrototype()->getTemp(),
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
    public function inventory(Request $request): JsonResponse {
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

    #[Route(path: '/{id}', name: 'buildings_participate', methods: ['PATCH'])]
    public function participate(
        Building $building, JSONRequestParser $parser, TownHandler $townHandler, EventProxyService $events,
        CitizenHandler $citizenHandler, InventoryHandler $inventoryHandler, EntityManagerInterface $em,
        LogTemplateHandler $log, GameProfilerService $gps, DoctrineCacheService $doctrineCache,
        PictoHandler $pictoHandler,
    ): JsonResponse {
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($town->getId() !== $building->getTown()->getId())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        if ($citizen->hasStatus('wound3'))
            return new JsonResponse(['error' => ErrorHelper::ErrorActionNotAvailableWounded], Response::HTTP_NOT_ACCEPTABLE);

        $ap = $parser->get_int('ap', 0);
        if ($ap < 0) return new JsonResponse(status: Response::HTTP_NOT_ACCEPTABLE);

        // Check if all parent buildings are completed
        $current = $building->getPrototype();
        while ($parent = $current->getParent()) {
            if (!$townHandler->getBuilding($town, $parent, true))
                return new JsonResponse(status: Response::HTTP_NOT_ACCEPTABLE);
            $current = $parent;
        }

        // Check if slave labor is allowed (ministry of slavery must be built)
        $slavery_allowed = $townHandler->getBuilding($town, 'small_slave_#00', true) !== null;

        // If no slavery is allowed, block banished citizens from working on the construction site (except for repairs)
        // If slavery is allowed and the citizen is banished, permit slavery bonus
        if (!$slavery_allowed && $citizen->getBanished() && !$building->getComplete())
            return new JsonResponse(['error' => ErrorHelper::ErrorActionNotAvailableBanished], Response::HTTP_NOT_ACCEPTABLE);

        $slave_bonus = $citizen->getBanished() && !$building->getComplete();

        $workshopBonus = $events->queryTownParameter( $town, BuildingValueQuery::ConstructionAPRatio );
        $hpToAp = $events->queryTownParameter( $town, BuildingValueQuery::RepairAPRatio );

        // Remember if the building has already been completed (i.e. this is a repair action)
        $was_completed = $building->getComplete();

        // Check out how much AP is missing to complete the building; restrict invested AP to not exceed this
        if (!$was_completed) {
            $missing_ap = ceil( (round($building->getPrototype()->getAp()*$workshopBonus) - $building->getAp()) * ( $slave_bonus ? (2.0/3.0) : 1 )) ;
        } else {
            $missing_ap = ceil(($building->getPrototype()->getHp() - $building->getHp()) / $hpToAp);
        }

        if ($ap === 0 && $missing_ap > 0)
            return new JsonResponse(status: Response::HTTP_NOT_ACCEPTABLE);

        $ap = max(0, min( $ap, $missing_ap ) );

        if ($ap <= 0 && $was_completed)
            return new JsonResponse(['error' => TownController::ErrorAlreadyFinished], Response::HTTP_NOT_ACCEPTABLE);

        // If the citizen has not enough AP, fail
        if (($ap > 0 && !$citizenHandler->checkPointsWithFallback($citizen, PointType::AP, PointType::CP, $ap)) || $citizenHandler->isTired( $citizen ))
            return new JsonResponse(['error' => ErrorHelper::ErrorNoAP], Response::HTTP_NOT_ACCEPTABLE);

        // Get all resources needed for this building
        $res = $items = [];
        if (!$building->getComplete() && $building->getPrototype()->getResources())
            foreach ($building->getPrototype()->getResources()->getEntries() as $entry)
                if (!isset($res[ $entry->getPrototype()->getName() ]))
                    $res[ $entry->getPrototype()->getName() ] = new ItemRequest( $entry->getPrototype()->getName(), $entry->getChance(), false, false, false );
                else $res[ $entry->getPrototype()->getName() ]->addCount( $entry->getChance() );

        // If the building needs resources, check if they are present in the bank; otherwise fail
        if (!empty($res)) {
            $items = $inventoryHandler->fetchSpecificItems($town->getBank(), $res);
            if (empty($items)) return new JsonResponse(['error' => TownController::ErrorNotEnoughRes], Response::HTTP_NOT_ACCEPTABLE);
        }

        // Create a log entry
        if ($townHandler->getBuilding($town, 'item_rp_book2_#00', true)) {
            // TODO: Create an option to include AP in Log entries as a town parameter?
            if (!$was_completed)
                $em->persist( $log->constructionsInvest( $citizen, $building->getPrototype(), $ap, $slave_bonus ) );
            else
                $em->persist( $log->constructionsInvestRepair( $citizen, $building->getPrototype(), $ap, $slave_bonus ) );
        }

        // Calculate the amount of AP that will be invested in the construction
        $ap_effect = floor( $ap * ( $slave_bonus ? 1.5 : 1 ) );

        // Deduct AP and increase completion of the building
        $usedap = $usedbp = 0;
        $citizenHandler->deductPointsWithFallback( $citizen, PointType::AP, PointType::CP, $ap, $usedap, $usedbp);

        if ($was_completed)
            $gps->recordBuildingRepairInvested( $building->getPrototype(), $town, $citizen, $usedap, $usedbp );
        else $gps->recordBuildingConstructionInvested( $building->getPrototype(), $town, $citizen, $usedap, $usedbp );

        if($missing_ap <= 0 || $missing_ap - $ap <= 0){
            // Missing ap == 0, the building has been completed by the workshop upgrade.
            $building->setAp($building->getPrototype()->getAp());
        } else {
            $building->setAp($building->getAp() + $ap_effect);
        }

        $messages[] = "";

        // Notice
        $plan = "<strong>{$this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings')}</strong>";
        if(!$was_completed) {
            if($building->getAp() < $building->getPrototype()->getAp()){
                $messages[] = $this->translator->trans("Du hast am Bauprojekt {plan} mitgeholfen.", ["{plan}" => $plan], 'game');
            } else {
                $messages[] = $this->translator->trans("Hurra! Folgendes Gebäude wurde fertiggestellt: {plan}!", ['{plan}' => $plan], 'game');
            }
        } else $messages[] = $this->translator->trans("Du hast bei der Reparatur des Gebäudes {plan} mitgeholfen.", ["{plan}" => $plan], 'game');

        // If the building was not previously completed but reached 100%, complete the building and trigger the completion handler
        $building->setComplete( $building->getComplete() || $building->getAp() >= $building->getPrototype()->getAp() );

        if (!$was_completed && $building->getComplete()) {
            // Remove resources, create a log entry, trigger
            foreach ($items as $item) if ($res[$item->getPrototype()->getName()]->getCount() > 0) {
                $cc = $item->getCount();
                $inventoryHandler->forceRemoveItem($item, $res[$item->getPrototype()->getName()]->getCount());
                $res[$item->getPrototype()->getName()]->addCount(-$cc);
            }

            $em->persist( $log->constructionsBuildingComplete( $citizen, $building->getPrototype() ) );
            $events->buildingConstruction( $building, $citizen );
            $votes = $building->getBuildingVotes();
            foreach ($votes as $vote) {
                $vote->getCitizen()->setBuildingVote(null);
                $vote->getBuilding()->removeBuildingVote($vote);
                $em->remove($vote);
            }
        } else if ($was_completed) {
            $newHp = min($building->getPrototype()->getHp(), $building->getHp() + $ap * $hpToAp);
            $building->setHp($newHp);
            if($building->getPrototype()->getDefense() > 0) {
                $newDef = min($building->getPrototype()->getDefense(), $building->getPrototype()->getDefense() * $building->getHp() / $building->getPrototype()->getHp());
                $building->setDefense((int)floor($newDef));
            }
        }
        if($usedbp > 0)
            $messages[] = $this->translator->trans("Du hast dafür {count} Baupunkt(e) verbraucht.", ['{count}' => "<strong>$usedbp</strong>", 'raw_count' => $usedbp], "game");
        if($usedap > 0)
            $messages[] = $this->translator->trans("Du hast dafür {count} Aktionspunkt(e) verbraucht.", ['{count}' => "<strong>$usedap</strong>", 'raw_count' => $usedap], "game");


        if ($slave_bonus && !$was_completed)
            $messages[] = $this->translator->trans("Die in das Gebäude investierten APs zählten <strong>50% mehr</strong> (Sklaverei).", [], "game");

        // Set the activity status
        $citizenHandler->inflictStatus($citizen, 'tg_chk_build');

        // Give picto to the citizen
        if(!$was_completed){
            $pictoPrototype = $doctrineCache->getEntityByIdentifier(PictoPrototype::class,"r_buildr_#00");
        } else {
            $pictoPrototype = $doctrineCache->getEntityByIdentifier(PictoPrototype::class,"r_brep_#00");
        }
        $pictoHandler->give_picto($citizen, $pictoPrototype, $ap);

        // For a repaired building, we need to update the construction date to flush FE etags
        $now = new \DateTime();
        if ($was_completed)
            $building->setConstructionDate($now);

        // Persist
        try {
            $em->persist($citizen);
            $em->persist($building);
            $em->persist($town);
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorHelper::ErrorDatabaseException], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $messages = array_filter($messages);
        return new JsonResponse([
            'success' => true,
            'message' => empty($messages) ? null : implode('<hr />', $messages),
            'building' => $this->renderBuilding($building, false),
            'incidentals' => [
                'ap' => $citizen->getAp(),
                'bp' => $citizen->getBp(),
                ...($was_completed ? ['town-building-list' => "{$now->getTimestamp()}"] : [])
            ]
        ]);
    }

    #[Route(path: '/{id}', name: 'buildings_vote', methods: ['POST'])]
    public function vote(
        Building $building, CitizenHandler $citizenHandler, EntityManagerInterface $em
    ): JsonResponse {
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($town->getId() !== $building->getTown()->getId() || $building->getComplete())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        if (!$citizen->property(CitizenProperties::EnableBuildingRecommendation))
            return new JsonResponse(['error' => ErrorHelper::ErrorActionNotAvailable], status: Response::HTTP_NOT_ACCEPTABLE);

        if ($citizen->getBuildingVote())
            return new JsonResponse(['error' => ErrorHelper::ErrorActionNotAvailable], status: Response::HTTP_NOT_ACCEPTABLE);

        if ($citizen->getBanished())
            return new JsonResponse(['error' => ErrorHelper::ErrorActionNotAvailableBanished], status: Response::HTTP_NOT_ACCEPTABLE);

        try {
            $citizen->setBuildingVote( (new BuildingVote())->setBuilding( $building ) );
            $citizenHandler->inflictStatus($citizen, 'tg_build_vote');
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorHelper::ErrorDatabaseException], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $mv = [null, 0];
        $mv = $town->getBuildings()->reduce( function(array $v, Building $building) {
            if ($building->getComplete()) return $v;
            $votes = $building->getBuildingVotes()->count();
            return $votes > $v[1] ? [$building->getId(), $votes] : $v;
        }, $mv );

        return new JsonResponse([
            'success' => true,
            'building' => $this->renderBuilding($building, $mv[0] === $building->getId()),
        ]);
    }
}
