<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\ActionCounter;
use App\Entity\AdminReport;
use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenRankingProxy;
use App\Entity\FoundRolePlayText;
use App\Entity\HelpNotificationMarker;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeActionPrototype;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemCategory;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\Recipe;
use App\Entity\RuinZone;
use App\Entity\SpecialActionPrototype;
use App\Enum\AdminReportSpecification;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Game\CitizenPersistentCache;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Event\Game\Items\TransferItemEvent;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\DoctrineCacheService;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\HookExecutor;
use App\Service\InventoryHandler;
use App\Service\PictoHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Structures\BankItem;
use App\Structures\FriendshipActionTarget;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use App\Traits\Controller\EventChainProcessor;
use App\Translation\T;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Class InventoryAwareController
 * @package App\Controller
 */
#[GateKeeperProfile(only_alive: true, only_with_profession: true)]
#[Semaphore('town', scope: 'town')]
class InventoryAwareController extends CustomAbstractController
    implements HookedInterfaceController
{
    use EventChainProcessor;

    protected DeathHandler $death_handler;
    protected ActionHandler $action_handler;
    protected PictoHandler $picto_handler;
    protected LogTemplateHandler $log;
    protected RandomGenerator $random_generator;
    protected ZoneHandler $zone_handler;
    protected LogTemplateHandler $logTemplateHandler;
    protected UserHandler $user_handler;
    protected CrowService $crow;
    protected TownHandler $town_handler;
    protected Packages $asset;

    protected DoctrineCacheService $doctrineCache;
    protected EventProxyService $events;

    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, DeathHandler $dh, PictoHandler $ph,
        TranslatorInterface $translator, LogTemplateHandler $lt, TimeKeeperService $tk, RandomGenerator $rd, ConfMaster $conf,
        ZoneHandler $zh, UserHandler $uh, CrowService $armbrust, TownHandler $th, Packages $asset, DoctrineCacheService $doctrineCache,
        EventProxyService $events, HookExecutor $hookExecutor)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator, $hookExecutor);
        $this->action_handler = $ah;
        $this->picto_handler = $ph;
        $this->log = $lt;
        $this->random_generator = $rd;
        $this->zone_handler = $zh;
        $this->death_handler = $dh;
        $this->logTemplateHandler = $lt;
        $this->user_handler = $uh;
        $this->crow = $armbrust;
        $this->town_handler = $th;
        $this->asset = $asset;
        $this->doctrineCache = $doctrineCache;
        $this->events = $events;
    }

    public function before(): bool
    {
        $activeCitizen = $this->getActiveCitizen();
        if ($this->citizen_handler->hasRole($activeCitizen, 'ghoul') && !$activeCitizen->hasSeenHelpNotification('ghoul')) {
            $this->addFlash('popup-ghoul', $this->renderView('ajax/game/notifications/ghoul.html.twig'));
            $activeCitizen->addHelpNotification( $this->doctrineCache->getEntityByIdentifier(HelpNotificationMarker::class, 'ghoul') );
            $this->entity_manager->persist($activeCitizen);
            $this->entity_manager->flush();
        } else if ($this->citizen_handler->hasRole($activeCitizen, 'shaman') && !$activeCitizen->hasSeenHelpNotification('shaman')) {
            $this->addFlash('popup-shaman', $this->renderView('ajax/game/notifications/shaman.html.twig'));
            $activeCitizen->addHelpNotification( $this->doctrineCache->getEntityByIdentifier(HelpNotificationMarker::class, 'shaman') );
            $this->entity_manager->persist($activeCitizen);
            $this->entity_manager->flush();
        } else if ($this->citizen_handler->hasRole($activeCitizen, 'guide') && !$activeCitizen->hasSeenHelpNotification('guide')) {
            $this->addFlash('popup-shaman', $this->renderView('ajax/game/notifications/guide.html.twig'));
            $activeCitizen->addHelpNotification( $this->doctrineCache->getEntityByIdentifier(HelpNotificationMarker::class, 'guide') );
            $this->entity_manager->persist($activeCitizen);
            $this->entity_manager->flush();
        } else if ($activeCitizen->getTown()->getInsurrectionProgress() >= 100 && !$activeCitizen->hasSeenHelpNotification('insurrection') ) {
            $this->addFlash('popup-insurrection', $this->renderView('ajax/game/notifications/insurrection.html.twig', ['revolutionist' => $activeCitizen->hasStatus('tg_revolutionist')]));
            $activeCitizen->addHelpNotification( $this->doctrineCache->getEntityByIdentifier(HelpNotificationMarker::class, 'insurrection') );
            $this->entity_manager->persist($activeCitizen);
            $this->entity_manager->flush();
        } else if ($activeCitizen->hasStatus('tg_unban_altar') && !$activeCitizen->hasSeenHelpNotification('altar') ) {
            $this->addFlash('popup-altar', $this->renderView('ajax/game/notifications/altar.html.twig'));
            $activeCitizen->addHelpNotification( $this->doctrineCache->getEntityByIdentifier(HelpNotificationMarker::class, 'altar') );
            $this->entity_manager->persist($activeCitizen);
            $this->entity_manager->flush();
        } else if ($activeCitizen->getTown()->getForceStartAhead() && !$activeCitizen->hasSeenHelpNotification('stranger') ) {
            $this->addFlash('popup-stranger', $this->renderView('ajax/game/notifications/stranger.html.twig', ['population' => $activeCitizen->getTown()->getPopulation()]));
            $activeCitizen->addHelpNotification( $this->doctrineCache->getEntityByIdentifier(HelpNotificationMarker::class, 'stranger') );
            $this->entity_manager->persist($activeCitizen);
            $this->entity_manager->flush();
        } else if ( !empty( $records = array_filter( $activeCitizen->getSpecificActionCounter( ActionCounter::ActionTypeReceiveHeroic )->getAdditionalData() ?? [],
            fn($record) => is_array($record) && !( $record['seen'] ?? true ) && ( $record['valid'] ?? false )
        ) ) ) {
            $key = array_key_first( $records );
            $record = $records[$key];
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find( (int)$record['from'] ?? 0 );
            if ($citizen && $citizen->getTown() !== $activeCitizen->getTown()) $citizen = null;

            $action = $this->doctrineCache->getEntityByIdentifier(HeroicActionPrototype::class, $record['action'] ?? '');
            if ($citizen && $action)
                $this->addFlash('popup-general', $this->renderView('ajax/game/notifications/hero_donation.html.twig', [
                                                                                                                        'citizen' => $citizen, 'action' => $action]
                ));

            $this->entity_manager->persist(
                $activeCitizen->getSpecificActionCounter( ActionCounter::ActionTypeReceiveHeroic )->setRecord( $key, true, 'seen' )
            );
            $this->entity_manager->flush();
        }
        return true;
    }

    /**
     * @param Inventory[] $inventories
     * @param ItemTargetDefinition $definition
     * @param Citizen|null $reference
     * @return array
     */
    private function decodeActionItemTargets( array $inventories, ItemTargetDefinition $definition, ?Citizen $reference = null ): array
    {
        $targets = [];

        switch ($definition->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType: case ItemTargetDefinition::ItemSelectionTypePoison:
                foreach ($inventories as &$inv)
                    foreach ($inv->getItems() as &$item)
                        if ($this->action_handler->targetDefinitionApplies($item, $definition,true, $reference))
                            $targets[] = [ $item->getId(), $this->translator->trans( $item->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$item->getPrototype()->getIcon()}.gif" ];

                break;
            case ItemTargetDefinition::ItemTypeChestSelectionType:
                if ($reference)
                    foreach ($reference->getHome()->getChest()->getItems() as $item)
                        if ($this->action_handler->targetDefinitionApplies($item, $definition,true, $reference))
                            $targets[] = [ $item->getId(), $this->translator->trans( $item->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$item->getPrototype()->getIcon()}.gif" ];
                break;
            case ItemTargetDefinition::ItemTypeSelectionType:
                if ($definition->getTag())
                    foreach ($this->inventory_handler->resolveItemProperties($definition->getTag()) as &$prop)
                        /** @var $prop ItemPrototype */
                        $targets[] = [ $prop->getId(), $this->translator->trans( $prop->getLabel(), [], 'items' ), "build/images/item/item_{$prop->getIcon()}.gif", $prop ];

                if ($definition->getPrototype())
                    $targets[] = [ $definition->getPrototype()->getId(), $this->translator->trans( $definition->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$definition->getPrototype()->getIcon()}.gif", $definition->getPrototype() ];

                break;
            case ItemTargetDefinition::ItemHeroicRescueType:

                foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                    if ($citizen->getAlive() && $citizen->getZone() && $citizen->getZone()->getDistance() <= $this->getActiveCitizen()->property(CitizenProperties::HeroRescueRange))
                        $targets[] = [ $citizen->getId(), $citizen->getName(), "build/images/item/item_cart.gif" ];

                break;
            case ItemTargetDefinition::ItemCitizenType: case ItemTargetDefinition::ItemCitizenVoteType:

            foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                if ($citizen->getAlive() && $citizen != $this->getActiveCitizen())
                    $targets[] = [ $citizen->getId(), $citizen->getName(), "build/images/professions/{$citizen->getProfession()->getIcon()}.gif" ];

            break;
            case ItemTargetDefinition::ItemCitizenOnZoneType: case ItemTargetDefinition::ItemCitizenOnZoneSBType:

            foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                if ($citizen->getAlive() && $citizen != $this->getActiveCitizen() && $citizen->getZone() === $this->getActiveCitizen()->getZone()) {
                    if ($definition->getSpawner() !== ItemTargetDefinition::ItemCitizenOnZoneSBType || $citizen->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit)->getLast() === null || $citizen->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit)->getLast()->getTimestamp() < (time() - 1800))
                        $targets[] = [ $citizen->getId(), $citizen->getName(), "build/images/professions/{$citizen->getProfession()->getIcon()}.gif" ];
                }
            break;
            case ItemTargetDefinition::ItemFriendshipType:

                foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                    if ($citizen !== $this->getActiveCitizen() && $citizen->getAlive() && $citizen->getZone() === $this->getActiveCitizen()->getZone() && !$this->citizen_handler->hasStatusEffect( $citizen, 'tg_rec_heroic' ))
                        $targets[] = [ $citizen->getId(), $citizen->getName(), "build/images/item/item_cart.gif", null, 'Player' ];

                //$giftedActions = array_values(array_column( array_filter(
                //    $this->getActiveCitizen()->getSpecificActionCounter( ActionCounter::ActionTypeReceiveHeroic )->getAdditionalData(),
                //    fn($entry) => $entry['valid']
                //), 'action'));
                $giftedActions = [];

                foreach ($this->getActiveCitizen()->getHeroicActions() as $action)
                    if ($action->getName() !== 'hero_generic_friendship' && !in_array( $action->getName(), $giftedActions ))
                        $targets[] = [$action->getId(), $this->translator->trans($action->getAction()->getLabel(), [], 'items'), "build/images/actions/hero.gif", null, 'Action'];

                break;
        }

        // Sort target by display name
        usort($targets, function($a, $b) { return strcmp($a[1], $b[1]);});
        return $targets;
    }

    protected function getHeroicActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $this->action_handler->getAvailableIHeroicActions( $this->getActiveCitizen(),  $available, $crossed, $used );
        if (empty($available) && empty($crossed) && empty($used) ) return [];

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'action' => $a->getAction(), 'renderer' => null, 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget(), $this->getActiveCitizen() ) : null, 'target_mode' => $a->getAction()->getTarget()?->getSpawner() ?? 0, 'target_note' => $a->getAction()->getTarget()?->getNote(), 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'action' => $c->getAction(), 'renderer' => null, 'targets' => null, 'target_mode' => 0, 'crossed' => true ];
        foreach ($used as $c)      $ret[] = [ 'id' => $c->getId(), 'action' => $c->getAction(), 'renderer' => null, 'targets' => null, 'target_mode' => 0, 'crossed' => true, 'used' => true, 'used_message' => $c->getUsedMessage() ?? T::__('Du hast schon genug den Helden gespielt! Diese Heldentat kannst du erst im nächsten Leben wieder verbringen.', 'items') ];

        return $ret;
    }

    protected function getSpecialActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $this->action_handler->getAvailableISpecialActions( $this->getActiveCitizen(),  $available, $crossed );
        if (empty($available) && empty($crossed)) return [];

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'icon' => $a->getIcon(), 'action' => $a->getAction(), 'renderer' => null, 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget(), $this->getActiveCitizen() ) : null, 'target_mode' => $a->getAction()?->getTarget()?->getSpawner() ?? 0, 'target_note' => $a->getAction()->getTarget()?->getNote(), 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'icon' => $c->getIcon(), 'action' => $c->getAction(), 'renderer' => null, 'targets' => null, 'target_mode' => 0, 'crossed' => true ];
        return $ret;

    }

    protected function getCampingActions(): array {
        $ret = [];
        if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_CAMPING, false)) return $ret;

        $this->action_handler->getAvailableCampingActions( $this->getActiveCitizen(), $available, $crossed );

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'item' => null, 'action' => $a, 'renderer' => null, 'targets' => null, 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'item' => null, 'action' => $c, 'renderer' => null, 'targets' => null, 'crossed' => true ];

        usort($ret, fn($a,$b) => $a['id'] <=> $b['id']);

        return $ret;
    }

    protected function getHomeActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getHome()->getChest()];
        $this->action_handler->getAvailableHomeActions( $this->getActiveCitizen(), $available, $crossed );

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'icon' => $a->getIcon(), 'item' => null, 'action' => $a->getAction(), 'renderer' => null, 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget(), $this->getActiveCitizen() ) : null, 'target_mode' => $a->getAction()?->getTarget()?->getSpawner() ?? 0, 'target_note' => $a->getAction()->getTarget()?->getNote(), 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'icon' => $c->getIcon(), 'item' => null, 'action' => $c->getAction(), 'renderer' => null, 'targets' => null, 'target_mode' => 0, 'crossed' => true ];

        return $ret;
    }

    protected function getItemActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory() ];

        if($this->getActiveCitizen()->getZone()) {
            if ($this->conf->getTownConfiguration($this->getActiveCitizen()->getTown())->get(TownConf::CONF_MODIFIER_FLOOR_ASMBLY, false)) {
                if(!$this->getActiveCitizen()->activeExplorerStats())
                    $av_inv[] =  $this->getActiveCitizen()->getZone()->getFloor();
                else {
                    $ex = $this->getActiveCitizen()->activeExplorerStats();
                    $ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByPosition($this->getActiveCitizen()->getZone(), $ex->getX(), $ex->getY());
                    $av_inv[] = $ruinZone->getFloor();
                }
            }
        } else {
            $av_inv[] = $this->getActiveCitizen()->getHome()->getChest();
        }

        $items = [];
        foreach ($this->getActiveCitizen()->getInventory()->getItems() as $item) $items[] = $item;
        if ($this->getActiveCitizen()->getZone() === null) foreach ($this->getActiveCitizen()->getHome()->getChest()->getItems() as $item) $items[] = $item;

        foreach ($items as $item) if (!$item->getBroken() || $this->getActiveCitizen()->getZone()) {

            $this->action_handler->getAvailableItemActions( $this->getActiveCitizen(), $item, $available, $crossed, $messages, $this->getActiveCitizen()->getZone() !== null );
            if (empty($available) && empty($crossed)) continue;

            foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'random' => mt_rand(), 'item' => $item, 'broken' => $item->getBroken(), 'action' => $a, 'renderer' => $a->getRenderer(), 'targets' => $item->getBroken() ? null : ($a->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getTarget(), $this->getActiveCitizen() ) : null), 'target_mode' => $item->getBroken() ? 0 : ($a->getTarget()?->getSpawner() ?? 0), 'target_note' => $a->getTarget()?->getNote(), 'crossed' => false, 'message' => null ];
            foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'random' => mt_rand(), 'item' => $item, 'broken' => $item->getBroken(), 'action' => $c, 'renderer' => $c->getRenderer(), 'targets' => null, 'target_mode' => 0, 'crossed' => true, 'message' => $item->getBroken() ? null : ($messages[$c->getId()] ?? null) ];
        }

        return $ret;
    }

    protected function getItemCombinations(bool $inside): array {
        $town = $this->getActiveCitizen()->getTown();
        $source_inv = $this->getActiveCitizen()->getZone() ? [ $this->getActiveCitizen()->getInventory() ] : [ $this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getHome()->getChest() ];

        if ($this->getActiveCitizen()->getZone() && $this->getTownConf()->get( TownConf::CONF_MODIFIER_FLOOR_ASMBLY, false )) $source_inv[] = $this->getActiveCitizen()->getZone()->getFloor();

        $recipes = $this->entity_manager->getRepository(Recipe::class)->findByType( [Recipe::ManualAnywhere, $inside ? Recipe::ManualInside : Recipe::ManualOutside] );
        $out = [];
        $source_db = [];
        foreach ($recipes as $recipe) {
            /** @var Recipe $recipe */
            $found_provoking = 0;
            $provoking = null;
            foreach ($recipe->getProvoking() as $proto)
                if ($c = $this->inventory_handler->countSpecificItems( $source_inv, $proto )) {
                    $found_provoking = $c;
                    $provoking = $proto;
                    break;
                }

            if ($found_provoking <= 0) continue;

            $uses_provoking = $recipe->getSource()->getEntries()
                ->filter( fn(ItemGroupEntry $i) => $i->getPrototype() === $provoking )
                ->map( fn(ItemGroupEntry $i) => $i->getChance() )
                ->reduce( fn(int $c, int $a) => $c + $a, 0 );

            for ($i = 0; $i < max(1,($found_provoking - max(0, $uses_provoking - 1))); ++$i) $out[] = $recipe;

            if ($recipe->getSource())
                foreach ($recipe->getSource()->getEntries() as $entry)
                    /** @var ItemGroupEntry $entry */
                    if (!isset( $source_db[ $entry->getPrototype()->getId() ] ))
                        $source_db[ $entry->getPrototype()->getId() ] = $this->inventory_handler->countSpecificItems( $source_inv, $entry->getPrototype() );
        }

        return [ 'recipes' => $out, 'source_items' => $source_db ];
    }

    public function generic_devour_api(Citizen $aggressor, Citizen $victim): AjaxResponse {
        if (!$aggressor->hasRole('ghoul'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($aggressor->getId() === $victim->getId())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($aggressor->getZone()) {
            if (!$victim->getZone() || $victim->getZone()->getId() !== $aggressor->getZone()->getId())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        } else {
            if ($victim->getZone())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $notes = [];

        if ($victim->getAlive()) {

            if ($this->citizen_handler->hasStatusEffect($aggressor, 'tg_ghoul_eat'))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if ($victim->hasRole('ghoul')) {
                $this->addFlash('notice', $this->translator->trans('Du kannst diesen Bürger nicht angreifen... er riecht nicht wie die anderen. Moment... Dieser Bürger ist ein Ghul, genau wie du!', [], 'game'));
                return AjaxResponse::success();
            }

            $notes[] = $this->translator->trans( 'Mit weit aufgerissenem Maul stürzt du dich auf {citizen}. Unter der Wucht deiner brutalen Schläge und Tritte sackt er ziemlich schnell zusammen.', ['{citizen}' => $victim->getName()], 'game' );
            $notes[] = $this->translator->trans( 'Mit ein paar unschönen Tritten gegen seinen Kopf vergewisserst du dich, dass er garantiert nicht mehr aufstehen wird. Na los! Bring deinen Job zuende und verspeise ihn!', [], 'game' );

            $give_ap = $this->citizen_handler->getMaxAP( $aggressor ) + 1;

            if ($aggressor->getZone()) {
                $this->entity_manager->persist($this->log->citizenBeyondGhoulAttack($aggressor, $victim, true));
                $this->entity_manager->persist($this->log->citizenBeyondGhoulAttack($aggressor, $victim, false));
            } else {
                $cc = 0;
                foreach ($aggressor->getTown()->getCitizens() as $c)
                    if ($c->getAlive() && !$c->getZone() && $c->getId() !== $aggressor->getId() && $c->getId() !== $victim->getId()) $cc++;
                $cc = (float)$cc / (float)$aggressor->getTown()->getPopulation(); // Completely arbitrary

                if ($this->random_generator->chance($cc)) {

                    $this->entity_manager->persist($this->log->citizenTownGhoulAttack($aggressor,$victim));
                    $notes[] = $this->translator->trans( 'Gut gemacht!', [], 'game' );

                } else $notes[] = $this->translator->trans( 'Du wurdest beobachtet! Die anderen Bürger wurden gewarnt!', [], 'game' );
            }

            if ($give_ap > $aggressor->getAp())
                $this->citizen_handler->setAP($aggressor, false, $give_ap, null );

            $aggressor->setGhulHunger( max(0, $aggressor->getGhulHunger() - 65) );
            $this->picto_handler->give_picto($aggressor, 'r_cannib_#00');
            $this->citizen_handler->removeStatus($aggressor, 'tg_air_ghoul');
            if ($this->getTownConf()->get(TownConf::CONF_FEATURE_GIVE_ALL_PICTOS, true))
                $aggressor
                    ->giveGenerosityBonus( $this->getTownConf()->get( TownConf::CONF_MODIFIER_GENEROSITY_GHOUL, 1 ) )
                    ->registerPropInPersistentCache( CitizenPersistentCache::Ghoul_Aggression );

            $stat_down = false;
            if (!$this->citizen_handler->hasStatusEffect($aggressor, 'drugged') && $this->citizen_handler->hasStatusEffect($victim, 'drugged')) {
                $stat_down = true;
                $this->citizen_handler->inflictStatus( $aggressor, 'drugged' );
            }

            if (!$this->citizen_handler->hasStatusEffect($aggressor, 'addict') && $this->citizen_handler->hasStatusEffect($victim, 'addict')) {
                $stat_down = true;
                $this->citizen_handler->inflictStatus( $aggressor, 'addict' );
            }

            if (!$this->citizen_handler->hasStatusEffect($aggressor, 'drunk') && $this->citizen_handler->hasStatusEffect($victim, 'drunk')) {
                $stat_down = true;
                $this->citizen_handler->inflictStatus( $aggressor, 'drunk' );
                $this->citizen_handler->inflictStatus( $aggressor, 'tg_no_hangover' );
            }

            if ($stat_down)
                $notes[] = $this->translator->trans( 'Einige gesundheitliche Askekte deines Opfers sind auf dich übergegangen ...', [], 'game' );

            $this->citizen_handler->inflictStatus( $aggressor, 'tg_ghoul_eat' );

            $this->death_handler->kill($victim, CauseOfDeath::GhulEaten);
            $this->entity_manager->persist($this->log->citizenDeath( $victim ) );

        } else {

            if ($this->citizen_handler->hasStatusEffect($aggressor, 'tg_ghoul_corpse'))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if ($aggressor->getZone() || !$victim->getHome()->getHoldsBody())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            // $this->entity_manager->persist( $this->log->citizenDisposal($aggressor, $victim, Citizen::Ghoul) );

            $aggressor->setGhulHunger( max(0, $aggressor->getGhulHunger() - 10) );
            $victim->getHome()->setHoldsBody(false);
            $victim->setDisposed(Citizen::Ghoul);
            $this->picto_handler->give_picto($aggressor, 'r_cannib_#00');

            $notes[] = $this->translator->trans('Nicht so appetitlich wie frisches Menschenfleisch, aber es stillt nichtsdestotrotz deinen Hunger... zumindest ein bisschen. Wenigstens war das Fleisch noch halbwegs zart.', [], 'game');
            $this->citizen_handler->inflictStatus( $aggressor, 'tg_ghoul_corpse' );
        }

        if ($notes)
            $this->addFlash('notice', implode('<hr />', $notes));

        $this->entity_manager->persist($aggressor);
        $this->entity_manager->persist($victim);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    public function generic_attack_api(Citizen $aggressor, Citizen $defender): Response {
        if ($aggressor->getId() === $defender->getId() || !$defender->getAlive())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($aggressor->getZone()) {
            if (!$defender->getZone() || $defender->getZone()->getId() !== $aggressor->getZone()->getId())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        } else {
            if ($defender->getZone())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $ap = $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_AP, 5);
        if ($this->citizen_handler->isTired($aggressor) || $aggressor->getAp() < $ap)
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $attack_protect = $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_PROTECT, false) ||
            ($aggressor->getUser()->getAllSoulPoints() < $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_SP, 20));
        if ($attack_protect) {
            foreach ($aggressor->getTown()->getCitizens() as $c)
                if ($c->getAlive() && $c->hasRole('ghoul'))
                    $attack_protect = false;
        }

        if ($defender->hasRole('ghoul')) {

            $this->citizen_handler->setAP($aggressor, true, -$ap);
            $this->addFlash('notice',
                            $this->translator->trans('Mit aller Gewalt greifst du {citizen} an! Du hast den Überraschungsmoment auf deiner Seite und am Ende trägt {citizen} eine schwere Verletzung davon.', ['{citizen}' => $defender->getName()], 'game')
                            . "<hr />" .
                            $this->translator->trans('Plötzlich sackt {citizen} in sich zusammen, seine Augen drehen sich nach hinten, und mit einem schauerhaften Gurgeln löst sich sein ganzer Körper vor deinen Augen auf und hinterlässt nur den üblen Geruch von Tod und Verwesung! Es gibt keinen Zweifel mehr: {citizen} war ein Ghul!!', ['{citizen}' => $defender->getName()], 'game')
            );
            $this->entity_manager->persist($this->log->citizenAttack($aggressor, $defender, true));
            $this->death_handler->kill($defender, CauseOfDeath::GhulBeaten);
            $this->entity_manager->persist($this->log->citizenDeath( $defender ) );

        } elseif ($attack_protect) {

            $this->addFlash('error', $this->translator->trans('Bleib mal ganz geschmeidig! In dieser Stadt gibt es keine Ghule, also solltest du auch nicht herumlaufen und grundlos Leute verprügeln. M\'Kay?', [], 'game'));

        } elseif ( $this->citizen_handler->isWounded( $defender ) ) {

            $this->addFlash('error', $this->translator->trans('{citizen} ist bereits verletzt; ihn erneut anzugreifen wird dir nichts bringen.', ['{citizen}' => $defender->getName()], 'game'));

        } else {

            $this->citizen_handler->setAP( $aggressor, true, -$ap );
            $wound = $this->random_generator->chance( $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_CHANCE, 0.5) );
            $this->entity_manager->persist($this->log->citizenAttack($aggressor, $defender, $wound));
            if ($wound) {
                $this->addFlash('notice',
                                $this->translator->trans('Mit aller Gewalt greifst du {citizen} an! Du hast den Überraschungsmoment auf deiner Seite und am Ende trägt {citizen} eine schwere Verletzung davon.', ['{citizen}' => '<span>' . $defender->getName() . '</span>'], 'game')
                );
                $this->citizen_handler->inflictWound($defender);
            } else {
                $this->addFlash('notice',
                                $this->translator->trans('Mit aller Gewalt greifst du {citizen} an! Ihr tauscht für eine Weile Schläge aus, bis ihr euch schließlich größtenteils unverletzt voneinander trennt.', ['{citizen}' => '<span>' . $defender->getName() . '</span>'], 'game')
                );
            }

            if (!$defender->getZone()) {
                $this->crow->postAsPM( $defender, '', '', $wound ? PrivateMessage::TEMPLATE_CROW_AGGRESSION_SUCCESS : PrivateMessage::TEMPLATE_CROW_AGGRESSION_FAIL, $aggressor->getId() );
            }
        }

        $this->entity_manager->persist($aggressor);
        $this->entity_manager->persist($defender);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success( );
    }

    public function generic_recipe_api(JSONRequestParser $parser, ActionHandler $handler, ?callable $trigger_after = null): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Recipe $recipe */
        $recipe = $this->entity_manager->getRepository(Recipe::class)->find( $id );
        if ($recipe === null || !in_array($recipe->getType(), [Recipe::ManualAnywhere, Recipe::ManualOutside, Recipe::ManualInside]))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($recipe->getName() === 'com027' && !$citizen->getZone() ) {

            $lab = $this->doctrineCache->getEntityByIdentifier(CitizenHomeUpgradePrototype::class, 'lab');
            $home_lab_upgrade = $lab ? $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($citizen->getHome(), $lab) : null;
            if ($home_lab_upgrade) {
                $this->addFlash("error", $this->translator->trans('Dafür solltest du dein Labor verwenden...', [], 'game'));
                if (!$this->citizen_handler->hasStatusEffect($citizen, 'tg_tried_pp')) {
                    $this->citizen_handler->inflictStatus($citizen, 'tg_tried_pp');
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                }
            }
        }

        if (($error = $handler->execute_recipe( $citizen, $recipe, $remove, $message )) !== ActionHandler::ErrorNone ) {
            if ($error === ErrorHelper::ErrorItemsMissing && in_array($recipe->getType(), [Recipe::ManualAnywhere, Recipe::ManualInside, Recipe::ManualOutside])) {
                $items = "";
                $count = 0;
                foreach ($recipe->getSource()->getEntries() as $entry) {
                    if (!empty($items)) {
                        if (++$count < $recipe->getSource()->getEntries()->count()-1)
                            $items .= ", ";
                        else
                            $items .= " " . $this->translator->trans("und", [], "global") . " ";
                    }
                    $items .= $this->log->wrap($this->log->iconize($entry->getPrototype()));
                }
                $this->addFlash("error", $this->translator->trans('Du brauchst noch folgende Gegenstände: {list}.', ["{list}" => $items], 'game'));
                return AjaxResponse::success();
            } else {
                return AjaxResponse::success($error);
            }
        } else try {

            if ($trigger_after) $trigger_after($recipe);

            $this->entity_manager->persist($town);
            $this->entity_manager->persist($citizen);
            foreach ($remove as $e) $this->entity_manager->remove( $e );
            $this->entity_manager->flush();
            if ($message) $this->addFlash( 'notice', $message );
            return AjaxResponse::success();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
    }

    /**
     * @param int|string $id
     * @param ItemTargetDefinition|null $target
     * @param Citizen $citizen
     * @param Inventory[] $inventories
     * @param object|null $return
     * @return bool
     */
    private function extract_target_object(int|string $id, ?ItemTargetDefinition $target, Citizen $citizen, array $inventories, ?object &$return): bool {
        $return = null;
        if (!$target) return true;

        switch ($target->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType: case ItemTargetDefinition::ItemSelectionTypePoison:case ItemTargetDefinition::ItemTypeChestSelectionType:
                $return = $this->entity_manager->getRepository(Item::class)->find( (int)$id );
                if (!$return) return false;

                if ($target->getSpawner() === ItemTargetDefinition::ItemTypeChestSelectionType)
                    $inventories = [$citizen->getHome()->getChest()];

                foreach ($inventories as $inventory)
                    if ($inventory->getItems()->contains( $return ))
                        return true;

            return false;
            case ItemTargetDefinition::ItemTypeSelectionType:
                $return = $this->entity_manager->getRepository(ItemPrototype::class)->find( (int)$id );
                if (!$return) return false;
                return true;
            case ItemTargetDefinition::ItemHeroicRescueType:
                $return = $this->entity_manager->getRepository(Citizen::class)->find( (int)$id );
                if ($return->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId()) {
                    $return = null;
                    return false;
                }
                return true;
            case ItemTargetDefinition::ItemCitizenType: case ItemTargetDefinition::ItemCitizenVoteType:
            $return = $this->entity_manager->getRepository(Citizen::class)->find( (int)$id );
            if (!$return->getAlive() || $return->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId()) {
                $return = null;
                return false;
            }
            return true;
            case ItemTargetDefinition::ItemCitizenOnZoneType: case ItemTargetDefinition::ItemCitizenOnZoneSBType:
            $return = $this->entity_manager->getRepository(Citizen::class)->find( (int)$id );
            if (!$return->getAlive() || $return->getZone() !== $this->getActiveCitizen()->getZone()) {
                $return = null;
                return false;
            } else if ( $target->getSpawner() === ItemTargetDefinition::ItemCitizenOnZoneSBType && $return->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit)->getLast() !== null && $return->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit)->getLast()->getTimestamp() >= (time() - 1800) ) {
                $return = null;
                return false;
            }
            return true;
            case ItemTargetDefinition::ItemFriendshipType:
                $action = null;
                $player = null;

                foreach ( explode( ';', $id ) as $section) {
                    $data = explode( '-', $section, 2 );
                    if (count($data) !== 2) return false;
                    if ($data[0] === 'Action') $action = (int)$data[1];
                    elseif ($data[0] === 'Player') $player = (int)$data[1];
                }

                if (!$action || !$player) return false;
                $action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->find( $action );
                $player = $this->entity_manager->getRepository(Citizen::class)->find( $player );
                if (!$action || !$player) return false;

                if (!$player->getAlive() || $player->getZone() !== $this->getActiveCitizen()->getZone() || $player === $this->getActiveCitizen() || !$this->getActiveCitizen()->getHeroicActions()->contains($action) || $this->citizen_handler->hasStatusEffect( $player, 'tg_rec_heroic' ))
                    return false;

                //$giftedActions = array_values(array_column( array_filter(
                //                                                $player->getSpecificActionCounter( ActionCounter::ActionTypeReceiveHeroic )->getAdditionalData(),
                //                                                fn($entry) => $entry['valid']
                //                                            ), 'action'));
                $giftedActions = [];

                if ($action->getName() === 'hero_generic_friendship' || in_array( $action->getName(), $giftedActions ))
                    return false;

                $return = new FriendshipActionTarget( $action, $player );
                return true;
            default: return false;
        }
    }

    public function generic_heroic_action_api(JSONRequestParser $parser, ?callable $trigger_after = null): Response {
        $target_id = $parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var HeroicActionPrototype|null $heroic */
        $heroic = ($action_id < 0) ? null : $this->entity_manager->getRepository(HeroicActionPrototype::class)->find( $action_id );

        if ( !$heroic ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if (!$citizen->getProfession()->getHeroic() || !$citizen->getHeroicActions()->contains( $heroic )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $heroic->getAction()->getTarget(), $citizen, [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $heroic->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

            $heroic_action = $heroic->getAction();
            if ($trigger_after) $trigger_after($heroic_action);
            $citizen->removeHeroicAction($heroic);
            $citizen->addUsedHeroicAction($heroic);

            // Add the picto Heroic Action
            $picto = $this->doctrineCache->getEntityByIdentifier(PictoPrototype::class, "r_heroac_#00");
            $this->picto_handler->give_picto($citizen, $picto);

            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_special_action_api(JSONRequestParser $parser, ?callable $trigger_after = null): Response {
        $target_id = $parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var SpecialActionPrototype|null $special */
        $special = ($action_id < 0) ? null : $this->entity_manager->getRepository(SpecialActionPrototype::class)->find( $action_id );

        if ( !$special ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if (!$citizen->getSpecialActions()->contains( $special )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $special->getAction()->getTarget(), $citizen, [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $special->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

            $special_action = $special->getAction();
            if ($trigger_after) $trigger_after($special_action);
            if ( $special->getConsumable() ) $citizen->removeSpecialAction($special);
            if ( $special->getProxyFor() ) {
                $citizen->removeHeroicAction($special->getProxyFor());
                $citizen->addUsedHeroicAction($special->getProxyFor());
            }

            // Special handler for the ARMA action
            $arma_actions = ['special_armag','special_armag_d','special_armag_n'];
            if (in_array( $special->getName(), $arma_actions))
                foreach ($citizen->getSpecialActions() as $specialAction)
                    if (in_array( $specialAction->getName(), $arma_actions))
                        $citizen->removeSpecialAction($specialAction);

            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_home_action_api(JSONRequestParser $parser): Response {
        $target_id = $parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var HomeActionPrototype|null $home_action */
        $home_action = ($action_id < 0) ? null : $this->entity_manager->getRepository(HomeActionPrototype::class)->find( $action_id );

        if ( !$home_action ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        if ($citizen->getZone()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $home_action->getAction()->getTarget(), $citizen, [ $citizen->getInventory(), $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $home_action->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {
            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_camping_action_api(JSONRequestParser $parser): Response {
        if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_CAMPING, false))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var CampingActionPrototype|null $heroic */
        $camping = ($action_id < 0) ? null : $this->entity_manager->getRepository(CampingActionPrototype::class)->find( $action_id );

        if ( !$camping ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if ($zone && $zone->getX() === 0 && $zone->getY() === 0 ) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $camping->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

            switch($camping->getName()){
                case 'cm_campsite_improve':
                    $this->entity_manager->persist($this->log->beyondCampingImprovement($citizen));
                    break;
                case 'cm_campsite_hide':
                case 'cm_campsite_tomb':
                    // Remove citizen from escort
                    foreach ($citizen->getLeadingEscorts() as $escorted_citizen) {
                        $escorted_citizen->getCitizen()->getEscortSettings()->setLeader( null );
                        $this->entity_manager->persist($escorted_citizen);
                    }

                    if ($citizen->getEscortSettings()) $this->entity_manager->remove($citizen->getEscortSettings());
                    $citizen->setEscortSettings(null);

                    $this->entity_manager->persist($this->log->beyondCampingHide($citizen));
                    break;
                case 'cm_campsite_unhide':
                case 'cm_campsite_untomb':
                    $this->entity_manager->persist($this->log->beyondCampingUnhide($citizen));
                    break;
            }

            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_action_api(JSONRequestParser $parser, ?callable $trigger_after = null, ?Citizen $base_citizen = null): Response {
        $item_id =   (int)$parser->get('item',   -1);
        $target_id = $parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|null $item */
        $item   = ($item_id < 0)   ? null : $this->entity_manager->getRepository(Item::class)->find( $item_id );
        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var ItemAction|null $action */
        $action = ($action_id < 0) ? null : $this->entity_manager->getRepository(ItemAction::class)->find( $action_id );

        $escort_mode = $base_citizen !== null;
        if ( !$item || !$action || $item->getBroken() ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if ( $escort_mode && $item->getPoison()->poisoned() ) return AjaxResponse::error( BeyondController::ErrorEscortActionRefused );
        $citizen = $base_citizen ?? $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if ($zone && !$this->zone_handler->isZoneUnderControl($zone) && !$action->getAllowWhenTerrorized() && $this->citizen_handler->hasStatusEffect($citizen, 'terror') && !$this->zone_handler->isZoneUnderControl($this->getActiveCitizen()->getZone()))
            return AjaxResponse::error( $citizen === $this->getActiveCitizen() ? BeyondController::ErrorTerrorized : BeyondController::ErrorEscortTerrorized );

        if (!$action->getAllowedAtGate() && $zone && $zone->isTownZone())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // $secondary_inv = $zone ? $zone->getFloor() : $citizen->getHome()->getChest();
        if($zone) {
            if($this->conf->getTownConfiguration($this->getActiveCitizen()->getTown())->get(TownConf::CONF_MODIFIER_FLOOR_ASMBLY, false)) {
                if(!$this->getActiveCitizen()->activeExplorerStats()) {
                    $secondary_inv = $zone->getFloor();
                } else {
                    $ex = $this->getActiveCitizen()->activeExplorerStats();
                    $ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByPosition($this->getActiveCitizen()->getZone(), $ex->getX(), $ex->getY());
                    $secondary_inv = $ruinZone->getFloor();
                }
            } else {
                $secondary_inv = null;
            }
        } else {
            $secondary_inv = $citizen->getHome()->getChest();
        }

        if (!$citizen->getInventory()->getItems()->contains( $item ) && (!$secondary_inv || !$secondary_inv->getItems()->contains( $item ))) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if (!$this->extract_target_object( $target_id, $action->getTarget(), $citizen, [ $citizen->getInventory(), $secondary_inv ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $url = null;

        if (($error = $this->action_handler->execute( $citizen, $item, $target, $action, $msg, $remove, false, $escort_mode )) === ActionHandler::ErrorNone) {

            if ($trigger_after) $trigger_after($action);

            if ($action->getName() == 'improve') {
                $this->entity_manager->persist($this->log->beyondCampingItemImprovement($citizen, $item->getPrototype()));
            }

            $this->entity_manager->persist($citizen);
            if ($item->getInventory())
                $this->entity_manager->persist($item);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            if ($msg) $this->addFlash( 'notice', $msg );

            if($text = $this->entity_manager->getRepository(FoundRolePlayText::class)->findNextUnreadText($this->getUser())){
                /** @var FoundRolePlayText $text */
                $url = $this->generateUrl("soul_rp", ['page' => 1, 'id' => $text->getId()]);
            }

        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'items');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );
        return AjaxResponse::success( true, ['url' => $url] );
    }
}
