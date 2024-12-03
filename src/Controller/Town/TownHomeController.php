<?php

namespace App\Controller\Town;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\AccountRestriction;
use App\Entity\Citizen;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\Complaint;
use App\Entity\Item;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Enum\ActionHandler\PointType;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Game\CitizenPersistentCache;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\AdminHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
#[Semaphore('town', scope: 'town')]
class TownHomeController extends TownController
{
    /**
     * @return Response
     */
    #[Route(path: 'jx/town/house/dash', name: 'town_house_dash')]
    public function house_dash(): Response
    {
        $activeCitizen = $this->getActiveCitizen();
        if (!$activeCitizen->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen, town and home objects
        $home = $activeCitizen->getHome();

        // Render
        return $this->render( 'ajax/game/town/home/dashboard.html.twig', $this->addDefaultTwigArgs('house', array_merge(
            [
                'home' => $home,
                'tab' => 'dash',

                'heroics' => $this->getHeroicActions(),
                'home_actions' => $this->getHomeActions(),
                'special_actions' => $this->getSpecialActions(),
                'actions' => $this->getItemActions(),
                'recipes' => $this->getItemCombinations(true),
            ], $this->house_partial_inventory_args(), $this->house_partial_complaints_args())) );
    }

    /**
     * @param string|null $subtab
     * @param Request $request
     * @param TranslatorInterface $trans
     * @return Response
     */
    #[Route(path: 'jx/town/house/messages/{subtab?}', name: 'town_house_messages')]
    public function house_messages(?string $subtab, Request $request, TranslatorInterface $trans): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen, town and home objects
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();
        $home = $citizen->getHome();

        $can_send_global_pm = !$citizen->getBanished() && $citizen->property( CitizenProperties::EnableGroupMessages );

        $possible_dests = [];
        foreach ($town->getCitizens() as $dest) {
            if(!$dest->getAlive()) continue;
            if($dest == $this->getActiveCitizen()) continue;
            $possible_dests[] = $dest;
        }

        $dest_id = $request->query->get('dest');
        $destCitizen = null;

        if($dest_id !== null){
            $destCitizen = $this->entity_manager->getRepository(Citizen::class)->find($dest_id);
        }

        /** @var PrivateMessageThread[] $nonArchivedMessages */
        $nonArchivedMessages = $this->entity_manager->getRepository(PrivateMessageThread::class)->findNonArchived($citizen);
        foreach ($nonArchivedMessages as $thread) {
            foreach ($thread->getMessages() as $message) {
                if($message->getRecipient() === $this->getActiveCitizen() && $message->getNew())
                    $thread->setNew(true);

                switch ($message->getTemplate()) {

                    case PrivateMessage::TEMPLATE_CROW_COMPLAINT_ON:
                        $thread->setTitle( $trans->trans('Anonyme Beschwerde ({num} insgesamt)', ['num' => $message->getAdditionalData() ? $message->getAdditionalData()['num'] ?? 0 : 0], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF:
                        $thread->setTitle( $trans->trans('Beschwerde zurückgezogen (es bleiben noch {num} Stück)', ['num' => $message->getAdditionalData() ? $message->getAdditionalData()['num'] ?? 0 : 0], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_TERROR:
                    case PrivateMessage::TEMPLATE_CROW_NIGHTWATCH_TERROR:
                        $thread->setTitle( $trans->trans('Du bist vor Angst erstarrt!!', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_AVOID_TERROR:
                        $thread->setTitle( $trans->trans('Was für eine schreckliche Nacht!', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_THEFT:
                        $thread->setTitle( $trans->trans('Haltet den Dieb!', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_CATAPULT:
                        $thread->setTitle( $trans->trans('Du bist für das Katapult verantwortlich', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_AGGRESSION_SUCCESS:
                        /** @var Citizen $aggressor */
                        $aggressor = $this->entity_manager->getRepository(Citizen::class)->find( $thread->getMessages()[0]->getForeignID() );
                        $thread->setTitle( $this->translator->trans('{username} hat dich angegriffen und verletzt!', ['{username}' => $aggressor->getName()], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_AGGRESSION_FAIL:
                        /** @var Citizen $aggressor */
                        $aggressor = $this->entity_manager->getRepository(Citizen::class)->find( $thread->getMessages()[0]->getForeignID() );
                        $thread->setTitle( $this->translator->trans('{username} hat dich angegriffen!', ['{username}' => $aggressor->getName()], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_NIGHTWATCH_WOUND:
                        $thread->setTitle( $this->translator->trans('Verletzt', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_INTRUSION:
                        $intruder = $this->entity_manager->getRepository(Citizen::class)->find( $thread->getMessages()[0]->getForeignID() );
                        $thread->setTitle( $this->translator->trans("Alarm (Bürger {citizen})", ['citizen' =>  $intruder ?? '???'], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_BANISHMENT:
                        $thread->setTitle( $this->translator->trans('Du wurdest verbannt', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_REDUCED_AP_REGEN:
                        $thread->setTitle( $this->translator->trans('Du bist erschöpft!', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_GAME_WELCOME:
                        $thread->setTitle( $this->translator->trans('Willkommen in deiner ersten Stadt', [], 'game') );
                        break;

                    default: break;
                }
            }
        }

        $sendable_items = [];

        foreach ($citizen->getInventory()->getItems() as $item) {
            if($item->getEssential()) continue;
            $sendable_items[] = $item;
        }

        foreach ($home->getChest()->getItems() as $item) {
            if($item->getEssential()) continue;
            $sendable_items[] = $item;
        }

        usort($sendable_items, function(Item $a, Item $b) {
            return $a->getPrototype()->getId() <=> $b->getPrototype()->getId();
        });

        // Render
        return $this->render( 'ajax/game/town/home/messages.html.twig', $this->addDefaultTwigArgs('house',
            array_merge([
                'home' => $home,
                'tab' => 'messages',

                'subtab' => $subtab,

                'can_send_global_pm' => $can_send_global_pm,
                'nonArchivedMessages' => $nonArchivedMessages,
                'archivedMessages' => $this->entity_manager->getRepository(PrivateMessageThread::class)->findArchived($citizen),
                'possible_dests' => $possible_dests,
                'dest_citizen' => $destCitizen,
                'sendable_items' => $sendable_items,
            ], $this->house_partial_complaints_args())
        ) );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/town/house/complaints', name: 'town_house_complaints')]
    public function house_complaints(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen, town and home objects
        $citizen = $this->getActiveCitizen();
        $home = $citizen->getHome();

        // Render
        return $this->render( 'ajax/game/town/home/complaints.html.twig', $this->addDefaultTwigArgs('house',
            array_merge([
                'home' => $home,
                'tab' => 'complaints',
            ], $this->house_partial_complaints_args())
        ) );
    }

    /**
     * @param EntityManagerInterface $em
     * @param TownHandler $th
     * @return Response
     */
    #[Route(path: 'jx/town/house/build', name: 'town_house_build')]
    public function house_build(EntityManagerInterface $em, TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen, town and home objects
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();
        $home = $citizen->getHome();
        
        $has_urbanism = $th->hasUrbanism($town);

        // Get the next upgrade level for the house
        $home_next_level = $em->getRepository( CitizenHomePrototype::class )->findOneByLevel(
            $home->getPrototype()->getLevel() + 1
        );

        // Get requirements for the next upgrade
        $home_next_level_requirement = null;
        if ($home_next_level && $home_next_level->getRequiredBuilding()) {
            $home_next_level_requirement = $th->getBuilding( $town, $home_next_level->getRequiredBuilding(), true ) ? null : $home_next_level->getRequiredBuilding();
        }
        $next_level_ap = null;
        $next_level_resources = null;
        if ($home_next_level) {
            $next_level_ap = $has_urbanism ? $home_next_level->getApUrbanism() : $home_next_level->getAp();
            $next_level_resources = $has_urbanism ? $home_next_level->getResourcesUrbanism() : $home_next_level->getResources();
        }

        // Render
        return $this->render( 'ajax/game/town/home/build.html.twig', $this->addDefaultTwigArgs('house',
            array_merge([
                'home' => $home,
                'tab' => 'build',

                'next_level' => $home_next_level,
                'next_level_ap' => $next_level_ap,
                'next_level_resources' => $next_level_resources,
                'next_level_req' => $home_next_level_requirement,
                'devastated' => $town->getDevastated(),
            ], $this->house_partial_upgrade_args(), $this->house_partial_complaints_args())
        ) );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/town/house/values', name: 'town_house_values')]
    public function house_values(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen, town and home objects
        $citizen = $this->getActiveCitizen();
        $home = $citizen->getHome();

        // Render
        return $this->render( 'ajax/game/town/home/values.html.twig', $this->addDefaultTwigArgs('house',
            array_merge([
                'home' => $home,
                'tab' => 'values',

                'protected' => $this->citizen_handler->houseIsProtected($this->getActiveCitizen(), true),
            ], $this->house_partial_deco_args(), $this->house_partial_upgrade_args(), $this->house_partial_complaints_args())
        ) );
    }

    protected function house_partial_upgrade_args(): array {
        $citizen = $this->getActiveCitizen();

        // Home extension caches
        $upgrade_proto = [];
        $upgrade_proto_lv = [];
        $upgrade_cost = [];

        // If the current house level supports extensions ...
        if ($citizen->getHome()->getPrototype()->getAllowSubUpgrades()) {

            // Get all extension prototypes
            $all_protos = $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findAll();

            // Iterate over prototypes to fill caches
            foreach ($all_protos as $proto) {

                // Get the actual extension instance
                $n = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype( $citizen->getHome(), $proto );

                // Add prototype object, current level (0 if not built yet), and building costs for next level
                $upgrade_proto[$proto->getId()] = $proto;
                $upgrade_proto_lv[$proto->getId()] = $n ? $n->getLevel() : 0;
                $upgrade_cost[$proto->getId()] = $this->entity_manager->getRepository(CitizenHomeUpgradeCosts::class)->findOneByPrototype( $proto, $upgrade_proto_lv[$proto->getId()] + 1 );
            }
        }

        return [
            'upgrades' => $upgrade_proto,
            'upgrade_levels' => $upgrade_proto_lv,
            'upgrade_costs' => $upgrade_cost,
        ];
    }

    protected function house_partial_deco_args(): array {
        $citizen = $this->getActiveCitizen();

        // Calculate decoration
        $decoItems = [];
        $deco = $this->citizen_handler->getDecoPoints($citizen, $decoItems);

        // Calculate home defense
        $this->town_handler->calculate_home_def($citizen->getHome(), $summary);

        return [
            'deco' => $deco,
            'decoItems' => $decoItems,
            'def' => $summary,
        ];
    }

    protected function house_partial_inventory_args(): array {
        $citizen = $this->getActiveCitizen();

        return array_merge([
            'home' => $citizen->getHome(),
            'citizen' => $citizen,
            'rucksack' => $citizen->getInventory(),
            'chest' => $citizen->getHome()->getChest(),
            'rucksack_size' => $this->inventory_handler->getSize( $citizen->getInventory() ),
            'chest_size' => $this->inventory_handler->getSize($citizen->getHome()->getChest()),
        ], $this->house_partial_deco_args());
    }

    protected function house_partial_complaints_args(): array {
        $citizen = $this->getActiveCitizen();

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->gte('severity', Complaint::SeverityBanish));
        $criteria->andWhere($criteria->expr()->eq('culprit', $citizen));

        return [
            'complaints' => $this->entity_manager->getRepository(Complaint::class)->matching( $criteria ),
        ];
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/town/partial/house/inventory', name: 'house_partial_inventory')]
    public function house_partial_inventory(): Response
    {
        return $this->render( 'ajax/game/town/partials/inventory.standalone.html.twig', $this->house_partial_inventory_args() );
    }

    /**
     * @param JSONRequestParser $parser
     * @param EventFactory $ef
     * @param EventDispatcherInterface $ed
     * @return Response
     */
    #[Route(path: 'api/town/house/item', name: 'town_house_item_controller')]
    public function item_house_api(JSONRequestParser $parser, EventFactory $ef, EventDispatcherInterface $ed): Response {
        $up_inv   = $this->getActiveCitizen()->getInventory();
        $down_inv = $this->getActiveCitizen()->getHome()->getChest();
        return $this->generic_item_api( $up_inv, $down_inv, true, $parser, $ef, $ed);
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/town/house/action', name: 'town_house_action_controller')]
    public function action_house_api(JSONRequestParser $parser): Response {
        return $this->generic_action_api( $parser );
    }

    /**
     * @param string $sect
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/town/house/action/{sect}', name: 'town_house_special_action_controller')]
    public function special_action_house_api(string $sect, JSONRequestParser $parser): Response {
        return match ($sect) {
            'home' => $this->generic_home_action_api( $parser ),
            'special' => $this->generic_special_action_api( $parser ),
            default => AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable )
        };
    }

    /**
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    #[Route(path: 'api/town/house/heroic', name: 'town_house_heroic_controller')]
    public function heroic_house_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        return $this->generic_heroic_action_api( $parser );
    }

    /**
     * @param JSONRequestParser $parser
     * @param ActionHandler $handler
     * @return Response
     */
    #[Route(path: 'api/town/house/recipe', name: 'town_house_recipe_controller')]
    public function recipe_house_api(JSONRequestParser $parser, ActionHandler $handler): Response {
        return $this->generic_recipe_api($parser, $handler);
    }

    /**
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param TownHandler $th
     * @return Response
     */
    #[Route(path: 'api/town/house/upgrade', name: 'town_house_upgrade_controller')]
    public function upgrade_house_api(EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, TownHandler $th): Response {
        // Get citizen, town and home object
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();
        $home = $citizen->getHome();

        $has_urbanism = $th->hasUrbanism($town);

        // Can't do it when the town is devastated
        if ($town->getDevastated()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Attempt to get the next house level; fail if none exists
        /** @var CitizenHomePrototype $next */
        $next = $em->getRepository(CitizenHomePrototype::class)->findOneBy( ['level' => $home->getPrototype()->getLevel() + 1] );
        if (!$next) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        
        $required_ap = $has_urbanism ? $next->getApUrbanism() : $next->getAp();

        // Make sure the citizen is not tired
        if ($ch->isTired( $citizen ) || $citizen->getAp() < $required_ap) return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Make sure the citizen has not upgraded their home today, only if we're not in chaos
        if ($ch->hasStatusEffect($citizen, 'tg_home_upgrade') && !$town->getChaos() && $town->getType()->getName() !== "panda")
            return AjaxResponse::error( self::ErrorAlreadyUpgraded );

        // Make sure building requirements for the upgrade are fulfilled
        if ($next->getRequiredBuilding() && !$th->getBuilding( $town, $next->getRequiredBuilding(), true ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Fetch upgrade resources; fail if they are missing
        $items = [];
        $required_items = $has_urbanism ? $next->getResourcesUrbanism() : $next->getResources();
        if ($required_items) {
            $items = $ih->fetchSpecificItems( [$home->getChest(),$citizen->getInventory()], $required_items );
            if (!$items)  return AjaxResponse::error( ErrorHelper::ErrorItemsMissing );
        }

        // Set the new home level
        $home->setPrototype($next);

        // Deduct AP and set the has-upgraded status
        $this->citizen_handler->setAP( $citizen, true, -$required_ap );
        $ch->inflictStatus( $citizen, 'tg_home_upgrade' );

        // Consume items
        foreach ($items as $item) {
            $r = $required_items->findEntry( $item->getPrototype()->getName() );
            $this->inventory_handler->forceRemoveItem( $item, $r ? $r->getChance() : 1 );
        }

        // Give picto
        $this->picto_handler->give_picto( $citizen, "r_homeup_#00" );

        $text = [];
        // Herzlichen Glückwunsch! Du hast deine Behausung in ein(e) {home} verwandelt und hast dafür 2 Aktionspunkt(e) ausgegeben.
        $text[] = $this->translator->trans('Herzlichen Glückwunsch! Du hast deine Behausung in ein(e) {home} verwandelt.', ['{home}' => "<span>" . $this->translator->trans($next->getLabel(), [], 'buildings') . "</span>"], 'game');
        if($required_items){
            /** @var ItemGroupEntry $r */
            $resText = " " . $this->translator->trans('Folgenden Dinge wurden dazu gebraucht:', [], 'game');
            foreach ($required_items->getEntries() as $item) {
                $resText .= " " . $this->log->wrap($this->log->iconize($item));
            }
            $text[] = $resText;
        }

        $text[]= " " . $this->translator->trans("Du hast {count} Aktionspunkt(e) benutzt.", ['{count}' => "<strong>" . $required_ap . "</strong>", '{raw_count}' => $required_ap], "game");

        $this->addFlash('notice', implode("<hr />", $text));

        // Create log & persist
        try {
            if (!$this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_HIDE_HOME_UPGRADE, false))
                $em->persist( $this->log->homeUpgrade( $citizen ) );

            $em->persist($home);
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }


        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param Translator $t
     * @return Response
     */
    #[Route(path: 'api/town/house/describe', name: 'town_house_describe_controller')]
    public function describe_house_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t): Response {
        if ($this->user_handler->isRestricted($this->getActiveCitizen()->getUser(), AccountRestriction::RestrictionTownCommunication))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        // Get description and truncate to 64 chars
        $new_desc = $parser->get('desc');
        if ($new_desc !== null) $new_desc = mb_substr($new_desc,0,64);

        // Set new description and persist
        $this->getActiveCitizen()->getHome()->setDescription( $new_desc );
        try {
            $em->persist($this->getActiveCitizen()->getHome());
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        // Show confirmation
        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/town/house/extend', name: 'town_house_extend_controller')]
    public function extend_house_api(EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, JSONRequestParser $parser): Response {
        // Get extension ID; fail if missing
        $id = (int)$parser->get('id', -1);
        if ($id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        // Get the extension prototype; fail if missing
        $proto = $em->getRepository(CitizenHomeUpgradePrototype::class)->find( $id );
        if (!$proto) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        // Get citizen and home objects
        $citizen = $this->getActiveCitizen();
        $home = $citizen->getHome();

        // Make sure the citizen is a hero
        if (!$citizen->getProfession()->getHeroic())
            return AjaxResponse::error(ErrorHelper::ErrorMustBeHero);

        // Can't do it if the home does not allow extensions
        if (!$citizen->getHome()->getPrototype()?->getAllowSubUpgrades()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Can't do it when the town is devastated
        if ($citizen->getTown()->getDevastated()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Get the current extension object
        $current = $em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($home, $proto);

        // Get costs for the next extension level, if there is no current extension object, assume level 1; fail if costs can't be found
        $costs = $em->getRepository(CitizenHomeUpgradeCosts::class)->findOneByPrototype( $proto, $current ? $current->getLevel()+1 : 1 );
        if (!$costs) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        // Make sure the citizen is not tired and has enough AP
        if (/*$ch->isTired( $citizen ) || */($citizen->getAp() + $citizen->getBp()) < $costs->getAp()) return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Fetch upgrade resources; fail if they are missing
        $items = [];
        if ($costs->getResources()) {
            $items = $ih->fetchSpecificItems( [$home->getChest(),$citizen->getInventory()], $costs->getResources() );
            if (!$items)  return AjaxResponse::error( ErrorHelper::ErrorItemsMissing );
        }

        // If no current extension object exists, make a new one and set its level to 1; otherwise, increase level
        if (!$current) $current = (new CitizenHomeUpgrade())->setPrototype($proto)->setHome($home)->setLevel(1);
        else $current->setLevel( $current->getLevel()+1 );

        // Deduct AP
        $this->citizen_handler->deductPointsWithFallback( $citizen, PointType::AP, PointType::CP, $costs->getAp());

        // Give picto
        $pictoPrototype = $em->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_hbuild_#00"]);
        $this->picto_handler->give_picto($citizen, $pictoPrototype);

        // Consume items
        foreach ($items as $item) {
            $r = $costs->getResources()->findEntry( $item->getPrototype()->getName() );
            $this->inventory_handler->forceRemoveItem( $item, $r ? $r->getChance() : 1 );
        }

        $text = $this->translator->trans("Mit dem Bau der(s) {upgrade} hat dein Haus Stufe {level} erreicht!", ['{upgrade}' => $this->translator->trans($proto->getLabel(), [], 'buildings'), '{level}' => $current->getLevel()], 'game');

        $this->addFlash('notice', $text);

        // Persist and flush
        try {
            $em->persist($current);
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/town/house/suicid', name: 'town_home_suicid')]
    public function suicid(AdminHandler $admh): Response
    {
        $message = $admh->suicid($this->getUser()->getId());
        $this->addFlash('notice', $message);
        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/town/house/pm/all_read', name: 'town_home_mark_all_read')]
    public function mark_all_pm_as_read(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();

        /** @var PrivateMessageThread $thread */
        $threads = $em->getRepository(PrivateMessageThread::class)->findBy( ['recipient' => $citizen] );
        foreach ($threads as $thread){
            $posts = $thread->getMessages();

            foreach ($posts as $message) {
                if($message->getRecipient() === $citizen) {
                    $message->setNew(false);
                    $em->persist($message);
                }
            }
        }

        $em->flush();
        return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house_messages', ['subtab' => 'received'])] );
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/town/house/pm/archive_all', name: 'town_home_archive_all_pm')]
    public function archive_all_pm(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();

        /** @var PrivateMessageThread $thread */
        $threads = $em->getRepository(PrivateMessageThread::class)->findBy( ['recipient' => $citizen] );
        foreach ($threads as $thread){
            $thread->setArchived(true);
            $posts = $thread->getMessages();

            foreach ($posts as $message) {
                if($message->getRecipient() === $citizen) {
                    $message->setNew(false);
                    $em->persist($message);
                }
            }
            $em->persist($thread);
        }

        $em->flush();
        return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house_messages', ['subtab' => 'received'])] );
    }
}
