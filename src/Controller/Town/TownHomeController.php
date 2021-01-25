<?php

namespace App\Controller\Town;

use App\Controller\InventoryAwareController;
use App\Controller\TownInterfaceController;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\Complaint;
use App\Entity\Emotes;
use App\Entity\ExpeditionRoute;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\AdminActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class TownHomeController extends TownController
{

    /**
     * @Route("jx/town/house/{tab?}/{subtab?}", name="town_house")
     * @param string|null $tab
     * @param string|null $subtab
     * @param EntityManagerInterface $em
     * @param TownHandler $th
     * @param Request $request
     * @return Response
     */
    public function house(?string $tab, ?string $subtab, EntityManagerInterface $em, TownHandler $th, Request $request, TranslatorInterface $trans): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen, town and home objects
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();
        $home = $citizen->getHome();

        // Get the next upgrade level for the house
        $home_next_level = $em->getRepository( CitizenHomePrototype::class )->findOneByLevel(
            $home->getPrototype()->getLevel() + 1
        );

        // Get requirements for the next upgrade
        $home_next_level_requirement = null;
        if ($home_next_level && $home_next_level->getRequiredBuilding())
            $home_next_level_requirement = $th->getBuilding( $town, $home_next_level->getRequiredBuilding(), true ) ? null : $home_next_level->getRequiredBuilding();

        // Home extension caches
        $upgrade_proto = [];
        $upgrade_proto_lv = [];
        $upgrade_cost = [];

        // If the current house level supports extensions ...
        if ($home->getPrototype()->getAllowSubUpgrades()) {

            // Get all extension prototypes
            $all_protos = $em->getRepository(CitizenHomeUpgradePrototype::class)->findAll();

            // Iterate over prototypes to fill caches
            foreach ($all_protos as $proto) {

                // Get the actual extension instance
                $n = $em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype( $home, $proto );

                // Add prototype object, current level (0 if not built yet), and building costs for next level
                $upgrade_proto[$proto->getId()] = $proto;
                $upgrade_proto_lv[$proto->getId()] = $n ? $n->getLevel() : 0;
                $upgrade_cost[$proto->getId()] = $em->getRepository(CitizenHomeUpgradeCosts::class)->findOneByPrototype( $proto, $upgrade_proto_lv[$proto->getId()] + 1 );
            }
        }

        // Calculate home defense
        $th->calculate_home_def($home, $summary);

        // Calculate decoration
        $deco = 0;
        foreach ($home->getChest()->getItems() as $item)
            $deco += $item->getPrototype()->getDeco();

        $can_send_global_pm = !$citizen->getBanished() && $citizen->getProfession()->getHeroic() && $this->user_handler->hasSkill($citizen->getUser(), 'writer');

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
                        $thread->setTitle( $trans->trans('Anonyme Beschwerde', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF:
                        $thread->setTitle( $trans->trans('Beschwerde zurückgezogen', [], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_TERROR:
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
                        $thread->setTitle( $this->translator->trans('%username% hat dich angegriffen und verletzt!', ['%username%' => $aggressor->getUser()->getName()], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_AGGRESSION_FAIL:
                        /** @var Citizen $aggressor */
                        $aggressor = $this->entity_manager->getRepository(Citizen::class)->find( $thread->getMessages()[0]->getForeignID() );
                        $thread->setTitle( $this->translator->trans('%username% hat dich angegriffen!', ['%username%' => $aggressor->getUser()->getName()], 'game') );
                        break;
                    case PrivateMessage::TEMPLATE_CROW_NIGHTWATCH_WOUND:
                        $thread->setTitle( $this->translator->trans('Verletzt', [], 'game') );
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

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->gte('severity', Complaint::SeverityBanish));
        $criteria->andWhere($criteria->expr()->eq('culprit', $citizen));

        // Render
        return $this->render( 'ajax/game/town/home.html.twig', $this->addDefaultTwigArgs('house', [
            'home' => $home,
            'tab' => $tab,
            'subtab' => $subtab,
            'heroics' => $this->getHeroicActions(),
            'special_actions' => $this->getHomeActions(),
            'actions' => $this->getItemActions(),
            'recipes' => $this->getItemCombinations(true),
            'chest' => $home->getChest(),
            'chest_size' => $this->inventory_handler->getSize($home->getChest()),
            'next_level' => $home_next_level,
            'next_level_req' => $home_next_level_requirement,
            'upgrades' => $upgrade_proto,
            'upgrade_levels' => $upgrade_proto_lv,
            'upgrade_costs' => $upgrade_cost,
            'complaints' => $this->entity_manager->getRepository(Complaint::class)->matching( $criteria ),

            'def' => $summary,
            'deco' => $deco,

            'log' => $this->renderLog( -1, $citizen, false, null, 10 )->getContent(),
            'day' => $town->getDay(),

            'can_send_global_pm' => $can_send_global_pm,
            'nonArchivedMessages' => $nonArchivedMessages,
            'archivedMessages' => $this->entity_manager->getRepository(PrivateMessageThread::class)->findArchived($citizen),
            'possible_dests' => $possible_dests,
            'dest_citizen' => $destCitizen,
            'sendable_items' => $sendable_items,
        ]) );
    }

    /**
     * @Route("api/town/house/log", name="town_house_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_house_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), $this->getActiveCitizen(), false, null, null);
    }

    /**
     * @Route("api/town/house/item", name="town_house_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function item_house_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $up_inv   = $this->getActiveCitizen()->getInventory();
        $down_inv = $this->getActiveCitizen()->getHome()->getChest();
        return $this->generic_item_api( $up_inv, $down_inv, true, $parser, $handler);
    }

    /**
     * @Route("api/town/house/action", name="town_house_action_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function action_house_api(JSONRequestParser $parser): Response {
        return $this->generic_action_api( $parser );
    }

    /**
     * @Route("api/town/house/special_action", name="town_house_special_action_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function special_action_house_api(JSONRequestParser $parser): Response {
        return $this->generic_home_action_api( $parser );
    }

    /**
     * @Route("api/town/house/heroic", name="town_house_heroic_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function heroic_house_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        return $this->generic_heroic_action_api( $parser );
    }

    /**
     * @Route("api/town/house/recipe", name="town_house_recipe_controller")
     * @param JSONRequestParser $parser
     * @param ActionHandler $handler
     * @return Response
     */
    public function recipe_house_api(JSONRequestParser $parser, ActionHandler $handler): Response {
        return $this->generic_recipe_api( $parser, $handler);
    }

    /**
     * @Route("api/town/house/upgrade", name="town_house_upgrade_controller")
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param TownHandler $th
     * @return Response
     */
    public function upgrade_house_api(EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, TownHandler $th): Response {
        // Get citizen, town and home object
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();
        $home = $citizen->getHome();

        // Attempt to get the next house level; fail if none exists
        /** @var CitizenHomePrototype $next */
        $next = $em->getRepository(CitizenHomePrototype::class)->findOneBy( ['level' => $home->getPrototype()->getLevel() + 1] );
        if (!$next) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Make sure the citizen is not tired
        if ($ch->isTired( $citizen ) || ($citizen->getAp() + $citizen->getBp()) < $next->getAp()) return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Make sure the citizen has not upgraded their home today, only if we're not in chaos
        if ($ch->hasStatusEffect($citizen, 'tg_home_upgrade') && !$town->getChaos())
            return AjaxResponse::error( self::ErrorAlreadyUpgraded );

        // Make sure building requirements for the upgrade are fulfilled
        if ($next->getRequiredBuilding() && !$th->getBuilding( $town, $next->getRequiredBuilding(), true ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Fetch upgrade resources; fail if they are missing
        $items = [];
        if ($next->getResources()) {
            $items = $ih->fetchSpecificItems( [$home->getChest(),$citizen->getInventory()], $next->getResources() );
            if (!$items)  return AjaxResponse::error( ErrorHelper::ErrorItemsMissing );
        }

        // Set the new home level
        $home->setPrototype($next);

        // Deduct AP and set the has-upgraded status
        $this->citizen_handler->deductAPBP( $citizen, $next->getAp() );
        $ch->inflictStatus( $citizen, 'tg_home_upgrade' );

        // Consume items
        foreach ($items as $item) {
            $r = $next->getResources()->findEntry( $item->getPrototype()->getName() );
            $this->inventory_handler->forceRemoveItem( $item, $r ? $r->getChance() : 1 );
        }

        // Give picto
        $this->picto_handler->give_picto( $citizen, "r_homeup_#00" );

        $text = [];
        // Herzlichen Glückwunsch! Du hast deine Behausung in ein(e) %home% verwandelt und hast dafür 2 Aktionspunkt(e) ausgegeben.
        $text[] = $this->translator->trans('Herzlichen Glückwunsch! Du hast deine Behausung in ein(e) %home% verwandelt.', ['%home%' => "<span>" . $this->translator->trans($next->getLabel(), [], 'buildings') . "</span>"], 'game');
        if($next->getResources()){
            /** @var ItemGroupEntry $r */
            $resText = " " . $this->translator->trans('Folgenden Dinge wurden dazu gebraucht:', [], 'game');
            foreach ($next->getResources()->getEntries() as $item) {
                $resText .= " " . $this->log->wrap($this->log->iconize($item));
            }
            $text[] = $resText;
        }

        $text[]= " " . $this->translator->trans("Du hast %count% Aktionspunkt(e) benutzt.", ['%count%' => "<strong>" . $next->getAp() . "</strong>"], "game");

        $this->addFlash('notice', implode("<hr />", $text));

        // Create log & persist
        try {
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
     * @Route("api/town/house/describe", name="town_house_describe_controller")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param Translator $t
     * @return Response
     */
    public function describe_house_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t): Response {
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
        $this->addFlash( 'notice', $t->trans('Du hast deine Beschreibung geändert.', [], 'game') );
        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/house/extend", name="town_house_extend_controller")
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param JSONRequestParser $parser
     * @return Response
     */
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

        // Get the current extension object
        $current = $em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($home, $proto);

        // Get costs for the next extension level, if there is no current extension object, assume level 1; fail if costs can't be found
        $costs = $em->getRepository(CitizenHomeUpgradeCosts::class)->findOneByPrototype( $proto, $current ? $current->getLevel()+1 : 1 );
        if (!$costs) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        // Make sure the citizen is not tired and has enough AP
        if ($ch->isTired( $citizen ) || ($citizen->getAp() + $citizen->getBp()) < $costs->getAp()) return AjaxResponse::error( ErrorHelper::ErrorNoAP );

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
        $this->citizen_handler->deductAPBP( $citizen, $costs->getAp() );

        // Give picto
        $pictoPrototype = $em->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_hbuild_#00"]);
        $this->picto_handler->give_picto($citizen, $pictoPrototype);

        // Consume items
        foreach ($items as $item) {
            $r = $costs->getResources()->findEntry( $item->getPrototype()->getName() );
            $this->inventory_handler->forceRemoveItem( $item, $r ? $r->getChance() : 1 );
        }

        // Mit dem Bau der(s) %name% hat dein Haus %level% erreicht!
        $text = $this->translator->trans("Mit dem Bau der(s) %upgrade% hat dein Haus Stufe %level% erreicht!", ['%upgrade%' => $this->translator->trans($proto->getLabel(), [], 'buildings'), '%level%' => $current->getLevel()], 'game');

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
     * @Route("api/town/house/suicid", name="town_home_suicid")
     * @return Response
     */
    public function suicid(AdminActionHandler $admh): Response
    {
        $message = $admh->suicid($this->getUser()->getId());
        $this->addFlash('notice', $message);
        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/house/pm/all_read", name="town_home_mark_all_read")
     * @param EntityManagerInterfacce $em
     * @return Response
     */
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
        return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house', ['tab' => 'messages', 'subtab' => 'received'])] );
    }

    /**
     * @Route("api/town/house/pm/archive_all", name="town_home_archive_all_pm")
     * @param EntityManagerInterfacce $em
     * @return Response
     */
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
        return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house', ['tab' => 'messages', 'subtab' => 'received'])] );
    }
}
