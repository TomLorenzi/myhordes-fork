<?php


namespace App\Service;


use App\Entity\ActionCounter;
use App\Entity\BuildingPrototype;
use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\ChatSilenceTimer;
use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenVote;
use App\Entity\EscapeTimer;
use App\Entity\EscortActionGroup;
use App\Entity\EventActivationMarker;
use App\Entity\FoundRolePlayText;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeActionPrototype;
use App\Entity\HomeIntrusion;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\Requirement;
use App\Entity\Result;
use App\Entity\RolePlayText;
use App\Entity\RuinZone;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Enum\ActionHandler\ActionValidity;
use App\Enum\ActionHandler\CountType;
use App\Enum\ActionHandler\PointType;
use App\Enum\Game\TransferItemModality;
use App\Enum\ItemPoisonType;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\Actions\Game\AtomProcessors\Effect\AtomEffectProcessor;
use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use App\Service\Actions\Game\WrapObjectsForOutputAction;
use App\Service\Maps\MazeMaker;
use App\Structures\ActionHandler\Evaluation;
use App\Structures\ActionHandler\Execution;
use App\Structures\EscortItemActionSet;
use App\Structures\FriendshipActionTarget;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use App\Translation\T;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ItemRequirement;
use MyHordes\Fixtures\DTO\Actions\EffectsDataContainer;
use MyHordes\Fixtures\DTO\Actions\RequirementsDataContainer;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entity_manager,
        private readonly CitizenHandler $citizen_handler,
        private readonly UserHandler $user_handler,
        private readonly DeathHandler $death_handler,
        private readonly InventoryHandler $inventory_handler,
        private readonly RandomGenerator $random_generator,
        private readonly ItemFactory $item_factory,
        private readonly TranslatorInterface $translator,
        private readonly TownHandler $town_handler,
        private readonly ZoneHandler $zone_handler,
        private readonly PictoHandler $picto_handler,
        private readonly Packages $assets,
        private readonly LogTemplateHandler $log,
        private readonly ConfMaster $conf,
        private readonly MazeMaker $maze,
        private readonly GameProfilerService $gps,
        private readonly ContainerInterface $container,
        private readonly EventProxyService $proxyService,
        private readonly InvalidateTagsInAllPoolsAction $clearCache,
        private readonly WrapObjectsForOutputAction $wrapObjectsForOutputAction,
    ) {}

    protected function evaluate( Citizen $citizen, ?Item $item, $target, ItemAction $action, ?string &$message, ?Evaluation &$cache = null ): ActionValidity {

        if ($item && !$item->getPrototype()->getActions()->contains( $action )) return ActionValidity::None;
        if ($target && (!$action->getTarget() || !$this->targetDefinitionApplies($target, $action->getTarget())))
            return ActionValidity::None;

        $cache = new Evaluation($this->entity_manager, $citizen, $item, $target, $this->conf->getTownConfiguration( $citizen->getTown() ), $this->conf->getGlobalConf());

        $current_state = ActionValidity::Full;
        foreach ($action->getRequirements() as $meta_requirement) {
            $last_state = $current_state;

            $this_state = ActionValidity::None;
            switch ($meta_requirement->getFailureMode()) {
                case Requirement::MessageOnFail: $this_state = ActionValidity::Allow; break;
                case Requirement::CrossOnFail: $this_state = ActionValidity::Crossed; break;
                case Requirement::HideOnFail: $this_state = ActionValidity::Hidden; break;
            }

            if ($meta_requirement->getAtoms()) {
                $container = (new RequirementsDataContainer())->fromArray([['atomList' => $meta_requirement->getAtoms()]]);
                foreach ( $container->all() as $requirementsDataElement )
                    if (!AtomRequirementProcessor::process( $this->container, $cache, $requirementsDataElement->atomList ))
                        $current_state = $current_state->merge($this_state);
            }

            if (!$current_state->thatOrAbove($last_state) && $thisMessage = $meta_requirement->getFailureText())
                $cache->addMessage($thisMessage, translationDomain: 'items');
        }

        $messages = $cache->getMessages( $this->translator, $this->wrapObjectsForOutputAction);
        $message = !empty($messages) ? implode('<hr />', $messages) : null;

        return $current_state;

    }

    /**
     * @param ItemPrototype[] $list
     * @return array
     */
    private function reformat_prototype_list(array $list): array {

        $cache = [];
        foreach ( $list as $entry )
            if (!isset( $cache[$entry->getId()] )) $cache[$entry->getId()] = [1,$entry];
            else $cache[$entry->getId()][0]++;

        return $cache;
    }

    /**
     * @param Citizen $citizen
     * @param Item $item
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     * @param array|null $messages
     */
    public function getAvailableItemActions(Citizen $citizen, Item $item, ?array &$available, ?array &$crossed, ?array &$messages = null, bool $ignore_broken_flag = false ) {

        $available = $crossed = $messages = [];
        if ($item->getBroken() && !$ignore_broken_flag) return;

        $is_at_00 = $citizen->getZone() && $citizen->getZone()->isTownZone();
        foreach ($item->getPrototype()->getActions() as $action) {
            if ($is_at_00 && !$action->getAllowedAtGate()) continue;
            $mode = $this->evaluate( $citizen, $item, null, $action, $tx );
            if ($mode->thatOrAbove( ActionValidity::Allow )) $available[] = $action;
            else if ($mode->thatOrAbove(ActionValidity::Crossed)) $crossed[] = $action;
            if (!empty($tx)) $messages[$action->getId()] = $tx;
        }
    }

    /**
     * @param Citizen $citizen
     * @return EscortItemActionSet[]
     */
    public function getAvailableItemEscortActions(Citizen $citizen, ?EscortActionGroup $limit = null ): array {

        $is_at_00 = $citizen->getZone() && $citizen->getZone()->isTownZone();

        $list = [];
        /** @var EscortActionGroup[] $escort_actions */
        $escort_actions = $this->entity_manager->getRepository(EscortActionGroup::class)->findAll();

        foreach ($escort_actions as $escort_action) if ($limit === null || $escort_action === $limit) {
            $struct = new EscortItemActionSet( $escort_action );

            foreach ($citizen->getInventory()->getItems() as $item)
                foreach ($item->getPrototype()->getActions() as $action)
                    if ($escort_action->getActions()->contains($action)) {
                        if ($is_at_00 && !$action->getAllowedAtGate()) continue;
                        $mode = $this->evaluate( $citizen, $item, null, $action, $tx );
                        if ($mode->thatOrAbove( ActionValidity::Allow )) $struct->addAction( $action, $item, true );
                        else if ($mode->thatOrAbove(ActionValidity::Crossed)) $struct->addAction( $action, $item, false );
                    }

            $list[] = $struct;
        }

        return $list;
    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableCampingActions(Citizen $citizen, ?array &$available, ?array &$crossed ) {

      $available = $crossed = [];
      $campingActions = $this->entity_manager->getRepository(CampingActionPrototype::class)->findAll();

      foreach ($campingActions as $action) {
        $mode = $this->evaluate( $citizen, null, null, $action->getAction(), $tx );
        if ($mode->thatOrAbove( ActionValidity::Allow )) $available[] = $action;
        else if ($mode->thatOrAbove(ActionValidity::Crossed)) $crossed[] = $action;
      }

    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableHomeActions(Citizen $citizen, ?array &$available, ?array &$crossed ) {

        $available = $crossed = [];
        $home_actions = $this->entity_manager->getRepository(HomeActionPrototype::class)->findAll();

        foreach ($home_actions as $action) {
            $mode = $this->evaluate( $citizen, null, null, $action->getAction(), $tx );
            if ($mode->thatOrAbove( ActionValidity::Allow )) $available[] = $action;
            else if ($mode->thatOrAbove(ActionValidity::Crossed)) $crossed[] = $action;
        }

    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     * @param ItemAction[] $used
     */
    public function getAvailableIHeroicActions(Citizen $citizen, ?array &$available, ?array &$crossed, ?array &$used ) {
        $available = $crossed = $used = [];

        if (!$citizen->getProfession()->getHeroic()) return;
        $is_at_00 = $citizen->getZone() && $citizen->getZone()->isTownZone();

        foreach ($citizen->getHeroicActions() as $heroic) {
            if ($is_at_00 && !$heroic->getAction()->getAllowedAtGate()) continue;
            $mode = $this->evaluate( $citizen, null, null, $heroic->getAction(), $tx );
            if ($mode->thatOrAbove( ActionValidity::Allow )) $available[] = $heroic;
            else if ($mode->thatOrAbove(ActionValidity::Crossed)) $crossed[] = $heroic;
        }

        foreach ($citizen->getUsedHeroicActions() as $used_heroic) {
            if ($citizen->getHeroicActions()->contains($used_heroic) || ($is_at_00 && !$used_heroic->getAction()->getAllowedAtGate())) continue;
            $mode = $this->evaluate( $citizen, null, null, $used_heroic->getAction(), $tx );
            if ($mode->thatOrAbove(ActionValidity::Crossed)) $used[] = $used_heroic;
        }

    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableISpecialActions(Citizen $citizen, ?array &$available, ?array &$crossed ) {
        $available = $crossed = [];

        foreach ($citizen->getSpecialActions() as $special) {
            $mode = $this->evaluate( $citizen, null, null, $special->getAction(), $tx );
            if ($mode->thatOrAbove( ActionValidity::Allow )) $available[] = $special;
            else if ($mode->thatOrAbove(ActionValidity::Crossed)) $crossed[] = $special;
        }

    }

    /**
     * @param Item|ItemPrototype|Citizen|FriendshipActionTarget $target
     * @param ItemTargetDefinition $definition
     * @return bool
     */
    public function targetDefinitionApplies($target, ItemTargetDefinition $definition, bool $forSelection = false): bool {
        switch ($definition->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:case ItemTargetDefinition::ItemSelectionTypePoison:
                if (!is_a( $target, Item::class )) return false;
                if ($definition->getHeavy() !== null && $target->getPrototype()->getHeavy() !== $definition->getHeavy()) return false;
                if ($definition->getBroken() !== null && $target->getBroken() !== $definition->getBroken()) return false;
                if (($definition->getPoison() !== null && !$forSelection) && $target->getPoison()->poisoned() !== $definition->getPoison()) return false;
                if ($definition->getPrototype() !== null && $target->getPrototype()->getId() !== $definition->getPrototype()->getId()) return false;
                if ($definition->getTag() !== null && !$target->getPrototype()->getProperties()->contains($definition->getTag())) return false;
                break;
            case ItemTargetDefinition::ItemTypeSelectionType:
                if (!is_a( $target, ItemPrototype::class )) return false;
                if ($definition->getPrototype() && $target->getId() !== $definition->getPrototype()->getId()) return false;
                if ($definition->getTag() && !$target->getProperties()->contains( $definition->getTag() ) ) return false;
                break;
            case ItemTargetDefinition::ItemHeroicRescueType:
                if (!is_a( $target, Citizen::class )) return false;
                if (!$target->getZone() || !$target->getAlive()) return false;
                if ( round( sqrt(pow($target->getZone()->getX(),2 ) + pow($target->getZone()->getY(),2 )) ) > 2 ) return false;
                break;
            case ItemTargetDefinition::ItemCitizenType: case ItemTargetDefinition::ItemCitizenVoteType: case ItemTargetDefinition::ItemCitizenOnZoneType: case ItemTargetDefinition::ItemCitizenOnZoneSBType:
                if (!is_a( $target, Citizen::class )) return false;
                if (!$target->getAlive()) return false;
                break;
            case ItemTargetDefinition::ItemFriendshipType:
                if (!is_a( $target, FriendshipActionTarget::class )) return false;
                if (!$target->citizen()->getAlive() || $target->action()->getName() === 'hero_generic_friendship' || $this->citizen_handler->hasStatusEffect( $target->citizen(), 'tg_rec_heroic' )) return false;
                break;
            default: return false;
        }

        return true;
    }

    /**
     * @param ItemPrototype|BuildingPrototype|Citizen|ZonePrototype|string $o
     * @param int $c
     * @return string
     */
    private function wrap($o, int $c=1): string {
        $i = null;
        if (is_array($o))
            return implode( ', ', array_map( fn($e) => $this->wrap($e, $c), $o ));
        else if (is_a($o, ItemPrototype::class)) {
            $s = $this->translator->trans($o->getLabel(), [], 'items');
            $i = 'build/images/item/item_' . $o->getIcon() . '.gif';
        } else if (is_a($o, BuildingPrototype::class)) {
            $s =  $this->translator->trans($o->getLabel(), [], 'buildings');
            $i = 'build/images/building/' . $o->getIcon() . '.gif';
        } else if (is_a($o, Citizen::class)) {
            $s =  $o->getName();
            $i = 'build/images/professions/' . $o->getProfession()->getIcon() . '.gif';
        } else if (is_a($o, ZonePrototype::class)) {
            $s =  $this->translator->trans($o->getLabel(), [], 'game');
        }
        else if (is_string($o)) $s = $o;
        else if (is_null($o)) $s = 'NULL';
        else $s = '_UNKNOWN_';

        if (!empty($i)) $i = $this->assets->getUrl( $i );
        return '<span>' . ($c > 1 ? "$c x " : '') . ($i ? "<img alt='' src='$i' />" : '') . $s .  '</span>';
    }

    private function wrap_concat(array $c): string {
        return implode(', ', array_map(function(array $e): string {
            return $this->wrap( $e[1], $e[0] );
        }, $this->reformat_prototype_list($c)));
    }

    private function wrap_concat_hierarchy(array $c): string {
        return implode(' > ', array_map(function(array $e): string {
            return $this->wrap( $e[1], $e[0] );
        }, $this->reformat_prototype_list($c)));
    }

    const ErrorNone = 0;
    const ErrorActionUnregistered = ErrorHelper::BaseActionErrors + 1;
    const ErrorActionForbidden    = ErrorHelper::BaseActionErrors + 2;
    const ErrorActionImpossible   = ErrorHelper::BaseActionErrors + 3;

    /**
     * @param Citizen $citizen
     * @param Item|null $item
     * @param Item|ItemPrototype|Citizen|FriendshipActionTarget|null $target
     * @param ItemAction $action
     * @param string|null $message
     * @param array|null $remove
     * @param bool $force Do not check if the action is valid
     * @return int
     */
    public function execute( Citizen &$citizen, ?Item &$item, &$target, ItemAction $action, ?string &$message, ?array &$remove, bool $force = false, bool $escort_mode = false ): int {

        $remove = [];

        $kill_by_poison   = $item && ($item->getPoison() === ItemPoisonType::Deadly) && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $infect_by_poison = $item && ($item->getPoison() === ItemPoisonType::Infectious) && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $random_by_poison = $item && ($item->getPoison() === ItemPoisonType::Strange) && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $spread_poison = ItemPoisonType::None;

        $town_conf = $this->conf->getTownConfiguration( $citizen->getTown() );

        /** @var ?Evaluation $evaluation */
        $evaluation = null;

        if (!$force) {
            $mode = $this->evaluate( $citizen, $item, $target, $action, $tx, $evaluation );
            if ($mode->thatOrBelow( ActionValidity::Hidden ) ) return self::ErrorActionUnregistered;
            if ($mode->thatOrBelow( ActionValidity::Crossed ) ) return self::ErrorActionImpossible;
            if ($mode->thatOrBelow( ActionValidity::Allow ) ) {

                if ($item?->getPoison() === ItemPoisonType::Deadly && $action->getPoisonHandler() === ItemAction::PoisonHandlerConsume) {
                    $this->death_handler->kill( $citizen, CauseOfDeath::Poison, $r );
                    $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
                    return self::ErrorNone;
                }

                $message = $tx;
                return self::ErrorActionForbidden;
            }
            if ($mode != ActionValidity::Full) return self::ErrorActionUnregistered;
        }

        $target_item_prototype = null;
        if ($target && is_a( $target, Item::class )) $target_item_prototype = $target->getPrototype();
        if ($target && is_a( $target, ItemPrototype::class )) $target_item_prototype = $target;

        $cache = new Execution($this->entity_manager, $citizen, $item, $target, $this->conf->getTownConfiguration( $citizen->getTown() ), $this->conf->getGlobalConf());
        $default_message = $escort_mode ? $action->getEscortMessage() : $action->getMessage();
        $cache->setEscortMode($escort_mode);
        $cache->setAction($action);

        if ($default_message) $cache->addMessage($default_message, translationDomain: 'items');
        if ($target_item_prototype) $cache->setTargetItemPrototype( $target_item_prototype );
        foreach ($evaluation?->getProcessedItems('item_tool') ?? [] as $tool) $cache->addToolItem( $tool );

        $cache->addTranslationKey('tamer_dog', LogTemplateHandler::generateDogName($citizen->getId(), $this->translator));

        if ($citizen->activeExplorerStats())
            $cache->setTargetRuinZone($ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByExplorerStats($citizen->activeExplorerStats()));
        else $ruinZone = null;

        $floor_inventory = null;
        if (!$citizen->getZone())
            $floor_inventory = $citizen->getHome()->getChest();
        elseif ($citizen->getZone()->getX() === 0 && $citizen->getZone()->getY() === 0)
            $floor_inventory = $citizen->getTown()->getBank();
        elseif (!$ruinZone)
            $floor_inventory = $citizen->getZone()->getFloor();
        else
            $floor_inventory = $ruinZone->getFloor();

        $sort_result_list = function(array &$results) {
            usort( $results, function(Result $a, Result $b) {
                // Results with status effects are handled first
                if (($a->getStatus() === null) !== ($b->getStatus() === null)) return $b->getStatus() ? 1 : -1;
                // Results with custom code are handled second
                if (($a->getCustom() === null) !== ($b->getCustom() === null)) return $b->getCustom() ? 1 : -1;
                // Everything else is handled in "random" order
                return $a->getId() - $b->getId();
            } );
        };

        $execute_result = function(Result $result) use ($citizen, &$item, &$target, &$action, &$message, &$remove, &$cache, &$kill_by_poison, &$infect_by_poison, &$spread_poison, $town_conf, &$floor_inventory, &$ruinZone) {
            /** @var Citizen $citizen */
            if ($status = $result->getStatus()) {

                $p = $status->getProbability();
                if ($p !== null && $status->isModifyProbability()) {

                    if ($status->getRole()?->getName() === 'ghoul') {
                        if ($citizen->getTown()->getType()->getName() === 'panda') $p += 3;
                        if ($this->citizen_handler->hasStatusEffect($citizen, 'tg_home_clean')) $p -= 3;
                    }

                }

                if ($p === null || $this->random_generator->chance( $p / 100 )) {
                    if ($status->getResetThirstCounter())
                        $citizen->setWalkingDistance(0);

                    if ($status->getCounter() !== null)
                        $citizen->getSpecificActionCounter( $status->getCounter() )->increment();

                    if ($status->getCitizenHunger()) {
                        $ghoul_mode = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_GHOUL_MODE, 'normal');
                        $hungry_ghouls = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_GHOULS_HUNGRY, false);
                        if (($hungry_ghouls || $citizen->hasRole('ghoul')) && ($status->getForced() || !in_array($ghoul_mode, ['bloodthirst','airbnb'])))
                            $citizen->setGhulHunger( max(0,$citizen->getGhulHunger() + $status->getCitizenHunger()) );
                    }

                    if ($status->getRole() !== null && $status->getRoleAdd() !== null) {
                        if ($status->getRoleAdd()) {
                            if ($this->citizen_handler->addRole( $citizen, $status->getRole() )) {
                                $cache->addTag('role-up');
                                $cache->addTag("role-up-{$status->getRole()->getName()}");
                            }
                        } else {
                            if ($this->citizen_handler->removeRole( $citizen, $status->getRole() )) {
                                $cache->addTag('role-down');
                                $cache->addTag("role-down-{$status->getRole()->getName()}");
                            }
                        }
                    }

                    if ($status->getInitial() && $status->getResult()) {
                        if ($citizen->getStatus()->contains( $status->getInitial() )) {
                            $this->citizen_handler->removeStatus( $citizen, $status->getInitial() );
                            $this->citizen_handler->inflictStatus( $citizen, $status->getResult() );
                            $cache->addTag('stat-change');
                            $cache->addTag("stat-change-{$status->getInitial()->getName()}-{$status->getResult()->getName()}");
                        }
                    }
                    elseif ($status->getInitial()) {
                        if ($citizen->getStatus()->contains( $status->getInitial() ) && $this->citizen_handler->removeStatus( $citizen, $status->getInitial() )) {
                            $cache->addTag('stat-down');
                            $cache->addTag("stat-down-{$status->getInitial()->getName()}");
                        }
                    }
                    elseif ($status->getResult()) {
                        $inflict = true;

                        if($inflict && $status->getResult()->getName() == "infect" && $this->citizen_handler->hasStatusEffect($citizen, "tg_infect_wtns")) {
                            $inflict = $this->random_generator->chance(0.5);
                            $this->citizen_handler->removeStatus( $citizen, 'tg_infect_wtns' );

                            $cache->addMessage(
                                $inflict
                                    ? T::__('Ein Opfer der Großen Seuche zu sein hat dir diesmal nicht viel gebracht... und es sieht nicht gut aus...', "items")
                                    : T::__('Da hast du wohl Glück gehabt... Als Opfer der Großen Seuche bist du diesmal um eine unangenehme Infektion herumgekommen.', "items"),
                                translationDomain: 'items'
                            );
                        }
                        if ($inflict){
                            if (!$citizen->getStatus()->contains( $status->getResult() ) && $this->citizen_handler->inflictStatus($citizen, $status->getResult())) {
                                $cache->addTag('stat-up');
                                $cache->addTag("stat-up-{$status->getResult()->getName()}");
                            }
                        }
                    }
                }


            }

            if ($ap = $result->getAp()) {
                $old_ap = $citizen->getAp();
                if ($ap->getMax()) {
                    $to = $this->citizen_handler->getMaxAP($citizen) + $ap->getAp();
                    $this->citizen_handler->setAP( $citizen, false, max( $old_ap, $to ), null );
                } else $this->citizen_handler->setAP( $citizen, true, $ap->getAp(), $ap->getAp() < 0 ? null :$ap->getBonus() );

                $cache->addPoints( PointType::AP, $citizen->getAp() - $old_ap );
            }

            if ($pm = $result->getPm()) {
                $old_pm = $citizen->getPm();
                if ($pm->getMax()) {
                    $to = $this->citizen_handler->getMaxPM($citizen) + $pm->getPm();
                    $this->citizen_handler->setPM( $citizen, false, max( $old_pm, $to ) );
                } else $this->citizen_handler->setPM( $citizen, true, $pm->getPm() );

                $cache->addPoints( PointType::MP, $citizen->getPm() - $old_pm );
            }

            if ($cp = $result->getCp()) {
                $old_cp = $citizen->getBp();
                if ($cp->getMax()) {
                    $to = $this->citizen_handler->getMaxBP($citizen) + $cp->getCp();
                    $this->citizen_handler->setBP( $citizen, false, max( $old_cp, $to ) );
                } else $this->citizen_handler->setBP( $citizen, true, $cp->getCp() );

                $cache->addPoints( PointType::CP, $citizen->getBp() - $old_cp );
            }

            if ($death = $result->getDeath()) {
                $this->death_handler->kill( $citizen, $death->getCause(), $r );
                $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
                foreach ($r as $r_entry) $remove[] = $r_entry;
            }

            if ($bp = $result->getBlueprint()) {
                $blocked = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_DISABLED_BUILDINGS);
                $possible = $this->entity_manager->getRepository(BuildingPrototype::class)->findProspectivePrototypes( $citizen->getTown() );
                $filtered = array_filter( $possible, function(BuildingPrototype $proto) use ($bp,$blocked) {
                    if (in_array($proto->getName(), $blocked)) return false;
                    elseif ($bp->getType() !== null && $bp->getType() === $proto->getBlueprint() ) return true;
                    else return $bp->getList()->contains( $proto );
                } );

                if (!empty($filtered)) {
                    /** @var BuildingPrototype $pick */
                    $pick = $this->random_generator->pick( $filtered );
                    $town = $citizen->getTown();
                    if ($this->town_handler->addBuilding( $town, $pick )) {
                        $cache->addDiscoveredBlueprint( $pick );
                        $this->entity_manager->persist( $this->log->constructionsNewSite( $citizen, $pick ) );
                        $this->gps->recordBuildingDiscovered( $pick, $town, $citizen, 'action' );
                    }
                }
            }

            if ($item && $item_result = $result->getItem()) {
                if ($item_result->getConsume()) {
                    $this->inventory_handler->forceRemoveItem( $item );
                    $cache->addConsumedItem($item);
                } else {
                    if ($item_result->getMorph()) {
                        $cache->setItemMorph(  $item->getPrototype(), $item_result->getMorph() );
                        $item->setPrototype( $item_result->getMorph() );
                    }

                    if ($item_result->getBreak()  !== null) $item->setBroken( $item_result->getBreak() );
                    if ($item_result->getPoison() !== null) $item->setPoison( $item_result->getPoison() );
                }
            }

            if ($target && $target_result = $result->getTarget()) {
                if (is_a($target, Item::class)) {
                    if ($target_result->getConsume()) {
                        $this->inventory_handler->forceRemoveItem( $target );
                        $cache->addConsumedItem($target);
                    } else {
                        if ($target_result->getMorph()) {
                            $cache->setItemMorph( $target->getPrototype(),  $target_result->getMorph(), true );
                            $target->setPrototype($target_result->getMorph());
                        }
                        if ($target_result->getBreak()  !== null) $target->setBroken( $target_result->getBreak() );
                        if ($target_result->getPoison() !== null) $target->setPoison( $target_result->getPoison() );
                    }
                } elseif (is_a($target, ItemPrototype::class)) {
                    if ($i = $this->proxyService->placeItem( $citizen, $this->item_factory->createItem( $target ), [ $citizen->getInventory(), $floor_inventory ], true)) {
                        if ($i !== $citizen->getInventory())
                            $cache->addMessage( T::__('Der Gegenstand, den du soeben gefunden hast, passt nicht in deinen Rucksack, darum bleibt er erstmal am Boden...', 'game') );
                        $cache->addSpawnedItem($target);
                    }
                }
            }

            if ($result->getCustom())
            {
                $ap     = false;
                $terror = false;
                switch ($result->getCustom()) {
                    // Dice
                    case 1:
                        $dice = [ mt_rand(1, 6), mt_rand(1, 6), mt_rand(1, 6) ];
                        $cmg = $this->translator->trans('Du hast folgendes gewürfelt: {dc1}, {dc2} und {dc3}.', [
                            '{dc1}' => "<b>{$dice[0]}</b>",
                            '{dc2}' => "<b>{$dice[1]}</b>",
                            '{dc3}' => "<b>{$dice[2]}</b>",
                        ], 'items');
                        sort($dice);

                        if ( $dice[0] === $dice[1] && $dice[0] === $dice[2] ) {
                            $ap = true;
                            $cmg .= ' ' . $this->translator->trans('Wow, du hast einen Trippel geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                        } else if ( $dice[0] === ($dice[1]-1) && $dice[0] === ($dice[2]-2) ) {
                            $ap = true;
                            $cmg .= ' ' . $this->translator->trans('Wow, du hast eine Straße geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                        } else if ( $dice[0] === 1 && $dice[1] === 2 && $dice[2] === 4 ) {
                            $ap = true;
                            $cmg .= ' ' . $this->translator->trans('Wow, du hast beim ersten Versuch eine 4-2-1 geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                        } else if ( $dice[0] === $dice[1] || $dice[1] === $dice[2] )
                            $cmg .= ' ' . $this->translator->trans('Nicht schlecht, du hast einen Pasch geworfen.', [], 'items');
                        else $cmg .= ' ' . $this->translator->trans('Was für ein Spaß!', [], 'items');

                        $cache->addTranslationKey('casino', $cmg);
                        break;
                    // Cards
                    case 2:
                        $card = mt_rand(0, 53);
                        $color = (int)floor($card / 13);
                        $value = $card - ( $color * 13 );

                        if ( $color > 3 ) {
                            if ($value === 0) {
                                $terror = true;
                                $cmg = $this->translator->trans('Du ziehst eine Karte... und stellst fest, dass dein Name darauf mit Blut geschrieben steht! Du erstarrst vor Schreck!', [], 'items');
                            } else {
                                $ap = true;
                                $cmg = $this->translator->trans('Du ziehst eine Karte... und stellst fest, dass du die Karte mit den Spielregeln gezogen hast! Das erheitert dich so sehr, dass du 1AP gewinnst.', [], 'items');
                            }
                        } else {
                            $s_color = $this->translator->trans((['Kreuz','Pik','Herz','Karo'])[$color], [], 'items');
                            $s_value = $value < 9 ? ('' . ($value+2)) : $this->translator->trans((['Bube','Dame','König','Ass'])[$value-9], [], 'items');

                            $cmg = $this->translator->trans('Du ziehst eine Karte... es ist: {color} {value}.', [
                                '{color}' => "<strong>{$s_color}</strong>",
                                '{value}' => "<strong>{$s_value}</strong>",
                            ], 'items');

                            if ( $value === 12 ) {
                                $ap = true;
                                $cmg .= '<hr />' . $this->translator->trans('Das muss ein Zeichen sein! In dieser Welt ist kein Platz für Moral... du erhälst 1AP.', [], 'items');
                            } else if ($value === 10 && $color === 2) {
                                $ap = true;
                                $cmg .= '<hr />' . $this->translator->trans('Das Symbol der Liebe... dein Herz schmilzt dahin und du erhälst 1AP.', [], 'items');
                            }
                        }

                        $cache->addTranslationKey('casino', $cmg);
                        break;
                    // Guitar
                    case 3:
                        $count = 0;
                        foreach ($citizen->getTown()->getCitizens() as $target_citizen) {
                            // Don't give AP to dead citizen 
                            if(!$target_citizen->getAlive())
                                continue;

                            $this->citizen_handler->inflictStatus( $target_citizen, 'tg_guitar' );
                            
                            if ($target_citizen->getZone()) 
                                continue;

                            // Don't give AP if already full
                            if($target_citizen->getAp() >= $this->citizen_handler->getMaxAP($target_citizen)) {
                                continue;
                            } else {
                                $count += $this->citizen_handler->setAP($target_citizen,
                                                                        true,
                                                                        $this->citizen_handler->hasStatusEffect($target_citizen, ['drunk', 'drugged', 'addict'], false) ? 2 : 1,
                                                                        0);
                            }
                        }
                        $cache->addTranslationKey( 'casino', $this->translator->trans('Mit deiner Gitarre hast du die Stadt gerockt! Die Bürger haben {ap} AP erhalten.', ['{ap}' => $count], 'items') );
                        break;

                    // Tamer
                    case 4:case 5:case 16:case 17: {

                        // The tamer does not work if the door is closed
                        if (!$citizen->getTown()->getDoor()) {
                            $cache->addTag('fail');
                            $cache->addTag('door-closed');
                            break;
                        }

                        $heavy = $result->getCustom() === 5 || $result->getCustom() === 17;

                        $source = $citizen->getInventory();
                        $create_log = ($result->getCustom() === 4 || $result->getCustom() === 5);
                        $bank = ($result->getCustom() === 4 || $result->getCustom() === 5) ? $citizen->getTown()->getBank() : $citizen->getHome()->getChest();

                        $heavy_break = false;
                        $item_count = 0; $success_count = 0;

                        foreach ( $citizen->getInventory()->getItems() as $target_item ) {
                            if ($target_item->getEssential()) continue;
                            if ($target_item !== $item) $item_count++;
                            if ($target_item->getPrototype()->getHeavy())
                                if (!$heavy) $heavy_break = true;
                        }

                        if ($heavy_break) {
                            $cache->addTag('fail');
                            $cache->addTag('too-heavy');
                        } elseif ($this->inventory_handler->getFreeSize( $bank ) < $item_count) {
                            $cache->addTag('fail');
                            $cache->addTag('no-room');
                            $cache->addToCounter( CountType::Items, $item_count );
                            $cache->addTranslationKey('size', ($freeSize = $this->inventory_handler->getFreeSize($bank)) > 0 ? $freeSize : 0);
                        } else {
                            foreach ( $citizen->getInventory()->getItems() as $target_item ) if ($target_item !== $item) {
                                if ($this->proxyService->transferItem($citizen, $target_item, $source, $bank, TransferItemModality::Tamer) === InventoryHandler::ErrorNone) {
                                    $success_count++;
                                    if ($create_log) $this->entity_manager->persist($this->log->bankItemTamerLog($citizen, $target_item->getPrototype(), $target_item->getBroken()));
                                }
                            }

                            if ($success_count > 0) {
                                if ($item->getPrototype()->getName() === 'tamed_pet_#00' || $item->getPrototype()->getName() === 'tamed_pet_drug_#00' )
                                    $item->setPrototype( $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'tamed_pet_off_#00']) );
                                $this->entity_manager->persist($this->log->beyondTamerSendLog($citizen, $success_count));
                            } else {
                                $cache->addTag('no-items');
                                $cache->addTag('fail');
                            }
                        }

                        break;
                    }

                    // Survivalist
                    case 6:case 7: {
                        $drink = $result->getCustom() === 6;
                        $chances = 1;
                        if      ($citizen->getTown()->getDay() >= 20)  $chances = .50;
                        else if ($citizen->getTown()->getDay() >= 15)  $chances = .60;
                        else if ($citizen->getTown()->getDay() >= 13)  $chances = .70;
                        else if ($citizen->getTown()->getDay() >= 10)  $chances = .80;
                        else if ($citizen->getTown()->getDay() >= 5)   $chances = .85;

                        if( $citizen->getTown()->getDevastated() ) $chances = max(0.1, $chances - 0.2);

                        $give_ap = false;
                        if ($this->random_generator->chance($chances)) {
                            if ($drink) {
                                $citizen->setWalkingDistance(0);
                                if($citizen->hasRole('ghoul')){
                                    $this->citizen_handler->inflictWound($citizen);
                                } else if($this->citizen_handler->hasStatusEffect($citizen, 'thirst2')){
                                    $this->citizen_handler->removeStatus($citizen, 'thirst2');
                                    $this->citizen_handler->inflictStatus($citizen, 'thirst1');
                                } else {
                                	$this->citizen_handler->removeStatus($citizen, 'thirst1');
                                    if (!$this->citizen_handler->hasStatusEffect($citizen, 'hasdrunk')) {
                                	   $this->citizen_handler->inflictStatus($citizen, 'hasdrunk');
                                       $give_ap = true;
                                    }
                                }
                            } else {
                                if (!$this->citizen_handler->hasStatusEffect($citizen, 'haseaten')) {
                                   $this->citizen_handler->inflictStatus($citizen, 'haseaten');
                                   $give_ap = true;
                                }
                            }

                            if($give_ap){
                                $old_ap = $citizen->getAp();
                                if ($old_ap < 6)
                                    $this->citizen_handler->setAP($citizen, false, 6, 0);
                                $cache->addPoints( PointType::AP, $citizen->getAp() - $old_ap );
                            }

                            $this->entity_manager->persist( $this->log->outsideDigSurvivalist( $citizen ) );
                            $cache->addTranslationKey('casino', $this->translator->trans($drink ? 'Äußerst erfrischend, und sogar mit einer leichten Note von Cholera.' : 'Immer noch besser als das Zeug, was die Köche in der Stadt zubereiten....', [], 'items'));

                        } else
                            $cache->addTranslationKey('casino', $this->translator->trans('So viel zum Survivalbuch. Kein Wunder, dass dieses Buch nicht über die Grundstufe hinausgekommen ist... Du hast absolut nichts gefunden, aber das wusstest du wahrscheinlich schon.', [], 'items'));

                        break;
                    }
                    // Heroic teleport action
                    case 8:case 9: {
                        $zone = null;
                        $jumper = null;
                        if ($result->getCustom() === 8 && $citizen->getZone())
                            $jumper = $citizen;

                        if ($result->getCustom() === 9 && is_a( $target, Citizen::class ))
                            $jumper = $target;

                        if (!$jumper) break;
                        $zone = $jumper->getZone();
                        if (!$zone) break;

                        $this->zone_handler->updateZone( $zone );
                        $cp_ok = $this->zone_handler->isZoneUnderControl( $zone );

                        if ($dig_timer = $jumper->getCurrentDigTimer()) {
                            $dig_timer->setPassive(true);
                            $this->entity_manager->persist( $dig_timer );
                        }

                        foreach ($jumper->getLeadingEscorts() as $escort)
                            $escort->getCitizen()->getEscortSettings()->setLeader(null);

                        if ($jumper->getEscortSettings()) {
                            $remove[] = $jumper->getEscortSettings();
                            $jumper->setEscortSettings(null);
                        }

                        if ($jumper->activeExplorerStats())
                            $jumper->activeExplorerStats()->setActive( false );

                        $this->citizen_handler->removeStatus($jumper, 'tg_hide');
                        $this->citizen_handler->removeStatus($jumper, 'tg_tomb');
                        $jumper->setCampingTimestamp(0);
                        $jumper->setCampingChance(0);

                        $jumper->setZone(null);
                        $zone->removeCitizen( $jumper );

                        ($this->clearCache)("town_{$jumper->getTown()->getId()}_zones_{$zone->getX()}_{$zone->getY()}");

                        foreach ($this->entity_manager->getRepository(HomeIntrusion::class)->findBy(['victim' => $jumper]) as $homeIntrusion)
                            $this->entity_manager->remove($homeIntrusion);

                        /*if ( $zone->getX() !== 0 || $zone->getY() !== 0 ) {
                            $zero_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition( $zone->getTown(), 0, 0 );

                            if ($others_are_here) $this->entity_manager->persist( $this->log->outsideMove( $jumper, $zone, $zero_zone, true ) );
                            $this->entity_manager->persist( $this->log->outsideMove( $jumper, $zero_zone, $zone, false ) );
                        }*/
                        $others_are_here = $zone->getCitizens()->count() > 0;
                        if ( ($result->getCustom() === 8) && $others_are_here )
                            $this->entity_manager->persist( $this->log->heroicReturnLog( $citizen, $zone ) );
                        if ( $result->getCustom() === 9 )
                            $this->entity_manager->persist( $this->log->heroicRescueLog( $citizen, $jumper, $zone ) );
                        $this->entity_manager->persist( $this->log->doorPass( $jumper, true ) );
                        $this->zone_handler->handleCitizenCountUpdate( $zone, $cp_ok, $jumper );

                        break;
                    }
                    // Set campingTimer
                    case 10: {
                        $date = new DateTime();
                        $citizen->setCampingTimestamp( $date->getTimestamp() );
                        $citizen->setCampingChance( $this->citizen_handler->getCampingOdds($citizen) );
                        $dig_timers = $citizen->getDigTimers();
                        foreach ($dig_timers as $timer) {
                            $timer->setPassive(true);
                        }

                        break;
                    }
                    // Reset campingTimer
                    case 11:
                    {
                        $citizen->setCampingTimestamp(0);
                        $citizen->setCampingChance(0);
                        break;
                    }

                    // Discover a random ruin
                    case 12:
                    {
                        $list = [];
                        foreach ($citizen->getTown()->getZones() as $zone)
                            if ($zone->getDiscoveryStatus() === Zone::DiscoveryStateNone && $zone->getPrototype())
                                $list[] = $zone;

                        $selected = $this->random_generator->pick($list);
                        if ($selected) {
                            $upgraded_map = $this->town_handler->getBuilding($citizen->getTown(),'item_electro_#00', true);
                            $cache->setTargetZone($selected);
                            $cache->addTag('zone');
                            $selected->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                            if ($upgraded_map) $selected->setZombieStatus( Zone::ZombieStateExact );
                            else $selected->setZombieStatus( max( $selected->getZombieStatus(), Zone::ZombieStateEstimate ) );
                        }
                        break;

                    }

                    // Increase town temp defense for the watchtower
                    case 13: {
                        $cn = $this->town_handler->getBuilding( $citizen->getTown(), 'small_watchmen_#00', true );
                        $max = $town_conf->get( TownConf::CONF_MODIFIER_GUARDTOWER_MAX, 150 );
                        $use = $town_conf->get( TownConf::CONF_MODIFIER_GUARDTOWER_UNIT, 10 );

                        if ($max <= 0) $max = PHP_INT_MAX;

                        if ($cn) $cn->setTempDefenseBonus(min($max, $cn->getTempDefenseBonus() + $use));
                        break;
                    }

                    // Fill water weapons
                    case 14: {

                        $trans = [
                            'watergun_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_3_#00']),
                            'watergun_2_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_3_#00']),
                            'watergun_1_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_3_#00']),
                            'watergun_opt_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                            'watergun_opt_4_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                            'watergun_opt_3_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                            'watergun_opt_2_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                            'watergun_opt_1_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                            'grenade_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'grenade_#00']),
                            'bgrenade_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'bgrenade_#00']),
                            'kalach_#01' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'kalach_#00']),
                        ];

                        $fill_targets = [];
                        $filled = [];

                        foreach ($citizen->getInventory()->getItems() as $i) if (isset($trans[$i->getPrototype()->getName()]))
                            $fill_targets[] = $i;
                        foreach ($citizen->getHome()->getChest()->getItems() as $i) if (isset($trans[$i->getPrototype()->getName()]))
                            $fill_targets[] = $i;

                        foreach ($fill_targets as $i) {
                            $i->setPrototype($trans[$i->getPrototype()->getName()]);
                            if (!isset($filled[$i->getPrototype()->getId()])) $filled[$i->getPrototype()->getId()] = [$i];
                            else $filled[$i->getPrototype()->getId()][] = $i;
                            $cache->addSpawnedItem($i);
                        }

                        if (empty($filled)) $cache->addTag('fail');
                        break;
                    }

                    // Banned citizen note
                    case 15: {
                        $zones = $this->zone_handler->getZoneWithHiddenItems($citizen->getTown());
                        usort( $zones, fn(Zone $a, Zone $b) => $b->getItemsHiddenAt() <=> $a->getItemsHiddenAt() );
                        if(count($zones) > 0) {
                            $zone = $zones[0];
                            $cache->addTag('bannote_ok');
                            $cache->setTargetZone($zone);
                        } else {
                            $cache->addTag('bannote_fail');
                        }
                        break;
                    }

                    // Vote for a role
                    case 18:case 19: {
                        $role_name = "";
                        switch($result->getCustom()){
                            case 18:
                                $role_name = "shaman";
                                break;
                            case 19:
                                $role_name = "guide";
                                break;
                        }

                        if (!is_a( $target, Citizen::class )) break;

                        if(!$target->getAlive()) break;

                        $role = $this->entity_manager->getRepository(CitizenRole::class)->findOneBy(['name' => $role_name]);
                        if(!$role) break;

                        if ($this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($citizen, $role))
                            break;

                        if (!$this->town_handler->is_vote_needed($citizen->getTown(), $role)) break;
                        
                        // Add our vote !
                        $citizenVote = (new CitizenVote())
                            ->setAutor($citizen)
                            ->setVotedCitizen($target)
                            ->setRole($role);

                        $citizen->addVote($citizenVote);

                        // Persist
                        $this->entity_manager->persist($citizenVote);
                        $this->entity_manager->persist($citizen);
                        
                        break;
                    }

                    // Sandballs, bitches!
                    case 20: {

                        if ($target === null) {
                            // Hordes-like - there is no target, there is only ZUUL
                            $list = $citizen->getZone()->getCitizens()->filter( function(Citizen $c) use ($citizen): bool {
                                return $c->getAlive() && $c !== $citizen && ($c->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit, $citizen->getId())->getLast() === null || $c->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit, $citizen->getId())->getLast()->getTimestamp() < (time() - 1800));
                            } )->getValues();
                            $sandball_target = $this->random_generator->pick( $list );

                        } else $sandball_target = $target;

                        if (!$this->entity_manager->getRepository(EventActivationMarker::class)->findOneBy(['town' => $citizen->getTown(), 'active' => true, 'event' => 'christmas']))
                            $sandball_target = null;

                        if ($sandball_target !== null) {
                            $this->picto_handler->give_picto($citizen, 'r_sandb_#00');

                            $this->inventory_handler->forceRemoveItem( $item );
                            $cache->addConsumedItem($item);

                            $cache->setTargetCitizen($sandball_target);
                            $sandball_target->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit, $citizen->getId())->increment();

                            $hurt = !$this->citizen_handler->isWounded($sandball_target) && $this->random_generator->chance( $town_conf->get(TownConf::CONF_MODIFIER_SANDBALL_NASTYNESS, 0.0) );
                            if ($hurt) $this->citizen_handler->inflictWound($sandball_target);

                            $this->entity_manager->persist( $this->log->sandballAttack( $citizen, $sandball_target, $hurt ) );
                            $this->entity_manager->persist($sandball_target);


                        } else $cache->addTag('fail');

                        break;
                    }

                    // Flare
                    case 21 :
                        $criteria = new Criteria();
                        $criteria->andWhere($criteria->expr()->eq('town', $citizen->getTown()));
                        $criteria->andWhere($criteria->expr()->neq('discoveryStatus', Zone::DiscoveryStateCurrent));
                        $zones = $this->entity_manager->getRepository(Zone::class)->matching($criteria)->getValues();
                        if(count($zones) > 0) {
                            /** @var Zone $zone */
                            $zone = $this->random_generator->pick($zones);
                            $zone->setDiscoveryStatus(Zone::DiscoveryStateCurrent);
                            $zone->setZombieStatus( max( $zone->getZombieStatus(), $this->town_handler->getBuilding($citizen->getTown(), 'item_electro_#00', true) ? Zone::ZombieStateExact : Zone::ZombieStateEstimate ) );
                            $this->entity_manager->persist($zone);
                            $this->inventory_handler->forceRemoveItem( $item );
                            $cache->addConsumedItem($item);
                            $cache->addTag($zone->getPrototype() ? 'flare_ok_ruin' : 'flare_ok');
                            $cache->setTargetZone($zone);
                        } else {
                            $cache->addTag('flare_fail');
                        }
                        break;

                    // Chance to infect in a contaminated zone
                    case 22:
                        if ($town_conf->get(TownConf::CONF_FEATURE_ALL_POISON, false)) {

                            if ($this->random_generator->chance(0.05) && !$this->citizen_handler->hasStatusEffect($citizen, 'infection')) {

                                $inflict = true;
                                if ($this->citizen_handler->hasStatusEffect($citizen, "tg_infect_wtns")) {
                                    $inflict = $this->random_generator->chance(0.5);
                                    $this->citizen_handler->removeStatus( $citizen, 'tg_infect_wtns' );
                                    $cache->addMessage(
                                                           $inflict
                                                               ? T::__('Ein Opfer der Großen Seuche zu sein hat dir diesmal nicht viel gebracht... und es sieht nicht gut aus...', "items")
                                                               : T::__('Da hast du wohl Glück gehabt... Als Opfer der Großen Seuche bist du diesmal um eine unangenehme Infektion herumgekommen.', "items"),
                                        translationDomain: 'items'
                                    );
                                } else {
                                    $cache->addMessage(T::__("Schlechte Nachrichten, du hättest das nicht herunterschlucken sollen... du hast dir eine Infektion eingefangen!", "items"), translationDomain: 'items');
                                }

                                if ($inflict && $this->citizen_handler->inflictStatus($citizen, 'infection')) {
                                    $cache->addTag('stat-up');
                                    $cache->addTag("stat-up-infection");
                                }

                            }

                        }


                    case 70:
                        if (!is_a($target, FriendshipActionTarget::class)) break;

                        if (!$this->user_handler->checkFeatureUnlock($citizen->getUser(), 'f_share', true))
                            break;

                        $citizen->getHeroicActions()->removeElement( $target->action() );
                        $citizen->getUsedHeroicActions()->add( $target->action() );

                        $upgrade_actions = [];
                        $downgrade_actions = [];

                        if ($target->action()->getName() === 'hero_generic_find' )
                            $upgrade_actions[] = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => 'hero_generic_find_lucky']);
                        if ($target->action()->getName() === 'hero_generic_find_lucky' )
                            $downgrade_actions[] = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => 'hero_generic_find']);

                        $valid = !$this->citizen_handler->hasStatusEffect( $target->citizen(), 'tg_rec_heroic' );

                        if ($valid && $target->citizen()->getProfession()->getHeroic()) {
                            if ($target->citizen()->getHeroicActions()->contains( $target->action() ))
                                $valid = false;
                            foreach ( $upgrade_actions as $a ) if ($target->citizen()->getHeroicActions()->contains( $a ))
                                $valid = false;
                        }

                        $target->citizen()->getSpecificActionCounter(
                            ActionCounter::ActionTypeReceiveHeroic
                        )->increment()->addRecord( [
                                                       'action' => $target->action()->getName(),
                                                       'from' => $citizen->getId(),
                                                       'valid' => $valid,
                                                       'seen' => false
                                                   ] );

                        if ($valid) {
                            $this->picto_handler->award_picto_to( $citizen, 'r_share_#00' );
                            $this->citizen_handler->inflictStatus( $target->citizen(), 'tg_rec_heroic' );

                            foreach ( $downgrade_actions as $a ) {
                                $target->citizen()->getHeroicActions()->removeElement( $a );
                                $target->citizen()->getUsedHeroicActions()->removeElement( $a );
                            }
                            foreach ( $upgrade_actions as $a )
                                $target->citizen()->getUsedHeroicActions()->removeElement( $a );

                            if ($target->citizen()->getProfession()->getHeroic())
                                $target->citizen()->getHeroicActions()->add( $target->action() );
                            else $target->citizen()->addSpecialAction( $target->action()->getSpecialActionPrototype() );
                        } else $cache->addMessage(T::__( 'Du bist aber nicht sicher, ob er damit wirklich etwas anfangen kann...', 'items' ), translationDomain: 'items' );

                        break;
                    default:
                        $this->proxyService->executeCustomAction( $result->getCustom(), $citizen, $item, $target, $action, $message, $remove, $cache );
                        break;
                }

                if ($ap) {
                    $this->citizen_handler->setAP( $citizen, true, 1, 1 );
                    $cache->addPoints( PointType::AP, 1 );
                }

                $prevent_terror = $this->inventory_handler->countSpecificItems([$citizen->getInventory(), $citizen->getHome()->getChest()], 'prevent_terror') > 0;

                if ($terror && !$prevent_terror)
                    $this->citizen_handler->inflictStatus( $citizen, 'terror' );
            }

            if ($result->getAtoms()) {
                $container = (new EffectsDataContainer())->fromArray([['atomList' => $result->getAtoms()]]);
                foreach ( $container->all() as $effectsDataElement )
                    AtomEffectProcessor::process( $this->container, $cache, $effectsDataElement->atomList );
            }

            return $cache->getRegisteredError() ?? self::ErrorNone;
        };

        $results = $action->getResults()->getValues();
        foreach ($action->getResults() as $result) if ($result_group = $result->getResultGroup()) {
            $r = $this->random_generator->pickResultsFromGroup( $result_group );
            foreach ($r as $sub_result) $results[] = $sub_result;
        }

        $sort_result_list($results);
        foreach ($results as $result) {
            $res = $execute_result($result);
            if($res !== self::ErrorNone) return $res;
        }

        foreach (ItemPoisonType::cases() as $pt)
            if ($pt->poisoned() && $cache->isFlagged("transgress_poison_{$pt->value}"))
                $item?->setPoison($spread_poison = $spread_poison->mix( $pt ));

        if (($kill_by_poison || $cache->isFlagged('kill_by_poison')) && $citizen->getAlive()) {
            $this->death_handler->kill( $citizen, CauseOfDeath::Poison, $r );
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
            $cache->clearMessages();
        } elseif ($infect_by_poison || ($cache->isFlagged('infect_by_poison')) && $citizen->getAlive()) {
            $this->citizen_handler->inflictStatus( $citizen, 'infection' );
        } elseif ($random_by_poison && $citizen->getAlive() && $this->random_generator->chance(0.5)) {

            switch ($this->random_generator->pick([1,2,2,2])) {
                // Add drugged status
                case 1:
                    $this->picto_handler->award_picto_to($citizen, 'r_drug_#00');
                    if (!$this->citizen_handler->hasStatusEffect($citizen, 'drugged')) {
                        $this->citizen_handler->inflictStatus($citizen, 'drugged');
                        $cache->addMessage(T::__('Aber eine Frage bleibt: Waren diese fliegenden grünen Mäuse schon immer da?','items'), translationDomain: 'items');
                    } elseif (!$this->citizen_handler->hasStatusEffect($citizen, 'addict')) {
                        $this->citizen_handler->inflictStatus($citizen, 'addict');
                        $cache->addMessage(T::__('Sofort nach dem herunterschlucken verspürst du das Verlangen nach mehr... du bist nun <b>drogenabhängig</b>!','items'), translationDomain: 'items');
                    }

                // Add drunk status
                case 2:
                    $this->picto_handler->award_picto_to($citizen, 'r_alcool_#00');
                    $this->citizen_handler->removeStatus($citizen, 'hungover');
                    $this->citizen_handler->removeStatus($citizen, 'tg_no_hangover');
                    if (!$this->citizen_handler->hasStatusEffect($citizen, 'drunk')) {
                        $this->citizen_handler->inflictStatus($citizen, 'drunk');
                        $cache->addMessage(T::__('Plötzlich fängt alles um dich herum an, sich zu drehen ...','items'), translationDomain: 'items');
                    }
            }

        }

        if($cache->hasMessages())
            $message = implode('<hr />', $cache->getMessages( $this->translator, $this->wrapObjectsForOutputAction));

        return self::ErrorNone;
    }

    public function execute_recipe( Citizen &$citizen, Recipe $recipe, ?array &$remove, ?string &$message, int $penalty = 0 ): int {
        $town = $citizen->getTown();
        $c_inv = $citizen->getInventory();
        $t_inv = $citizen->getTown()->getBank();

        switch ( $recipe->getType() ) {
            case Recipe::WorkshopType:case Recipe::WorkshopTypeShamanSpecific:case Recipe::ManualInside:
                if ($citizen->getZone()) return ErrorHelper::ErrorActionNotAvailable;
                break;
            case Recipe::ManualOutside:
                if (!$citizen->getZone()) return ErrorHelper::ErrorActionNotAvailable;
                break;
            default: break;
        }

        $remove = [];
        $workshop_types = [Recipe::WorkshopType, Recipe::WorkshopTypeShamanSpecific, Recipe::WorkshopTypeTechSpecific];

        $silent = false;
        if (in_array($recipe->getType(), $workshop_types)) {
            $have_saw  = $this->inventory_handler->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy(['name' => 'saw_tool_#00']), false, false ) > 0;
            $have_manu = $this->town_handler->getBuilding($town, 'small_factory_#00', true) !== null;

            $ap = $penalty + (3 - ($have_saw ? 1 : 0) - ($have_manu ? 1 : 0));
            $silent = true;
        } else $ap = $penalty;

        if ( in_array($recipe->getType(), $workshop_types) && (($citizen->getAp() + $citizen->getBp()) < $ap || $this->citizen_handler->isTired( $citizen )) )
            return ErrorHelper::ErrorNoAP;

        $source_inv = in_array($recipe->getType(), $workshop_types) ? [ $t_inv ] : ($citizen->getZone() ? [$c_inv] : [$c_inv, $citizen->getHome()->getChest() ]);
        $target_inv = in_array($recipe->getType(), $workshop_types) ? [ $t_inv ] : ($citizen->getZone() ? ($citizen->getZone()->getX() != 0 || $citizen->getZone()->getY() != 0 ? [$c_inv,$citizen->getZone()->getFloor()] : [$c_inv])  : [$c_inv, $citizen->getHome()->getChest()]);

        if (!in_array($recipe->getType(), $workshop_types) && $citizen->getZone() && $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_FLOOR_ASMBLY, false))
            $source_inv[] = $citizen->getZone()->getFloor();

        $items = $this->inventory_handler->fetchSpecificItems( $source_inv, $recipe->getSource() );
        if (empty($items)) return ErrorHelper::ErrorItemsMissing;

        $list = [];
        foreach ($items as $item) {
            if($recipe->getKeep()->contains($item->getPrototype())) continue;
            $r = $recipe->getSource()->findEntry( $item->getPrototype()->getName() );
            $this->inventory_handler->forceRemoveItem( $item, $r->getChance() );
            $list[] = $item->getPrototype();
        }

        $this->citizen_handler->deductAPBP( $citizen, $ap, $used_ap, $used_bp );

        if ($recipe->getType() === Recipe::WorkshopTypeTechSpecific)
            $citizen->getSpecificActionCounter(ActionCounter::ActionTypeSpecialActionTech)->increment();

        $new_item = $this->random_generator->pickItemPrototypeFromGroup( $recipe->getResult(), $this->conf->getTownConfiguration( $citizen->getTown() ), $this->conf->getCurrentEvents( $citizen->getTown() ) );
        $this->proxyService->placeItem( $citizen, $this->item_factory->createItem( $new_item ) , $target_inv, true, $silent );
        $this->gps->recordRecipeExecuted( $recipe, $citizen, $new_item );

        if (in_array($recipe->getType(), $workshop_types))
            $this->entity_manager->persist( $this->log->workshopConvert( $citizen, array_map( function(Item $e) { return array($e->getPrototype()); }, $items  ), array([$new_item]) ) );

        switch ( $recipe->getType() ) {
            case Recipe::WorkshopType:
            case Recipe::WorkshopTypeShamanSpecific:
            case Recipe::WorkshopTypeTechSpecific:
              $base = match ($recipe->getAction()) {
                  "Öffnen"      => T::__('Du hast {item_list} in der Werkstatt geöffnet und erhälst {item}.', 'game'),
                  "Zerlegen"    => T::__('Du hast {item_list} in der Werkstatt zu {item} zerlegt.', 'game'),
                  default       => match (true) {
                      $used_bp === 0                 => T::__('Du hast ein(e,n) {item} hergestellt. Der Gegenstand wurde in der Bank abgelegt.<hr />Du hast dafür <strong>{ap} Aktionspunkt(e)</strong> verbraucht.', 'game'),
                      $used_bp > 0 && $used_ap <= 0  => T::__('Du hast ein(e,n) {item} hergestellt. Der Gegenstand wurde in der Bank abgelegt.<hr />Du hast dafür <strong>{cp} Baupunkt(e)</strong> verbraucht.', 'game'),
                      default                        => T::__('Du hast ein(e,n) {item} hergestellt. Der Gegenstand wurde in der Bank abgelegt.<hr />Du hast dafür <strong>{ap} Aktionspunkt(e)</strong> und <strong>{cp} Baupunkt(e)</strong> verbraucht.', 'game'),
                  }
              };
              $this->picto_handler->give_picto($citizen, "r_refine_#00");
              break;
            case Recipe::ManualOutside:case Recipe::ManualInside:case Recipe::ManualAnywhere:default:
                $base = (!empty($recipe->getTooltipString()) ? $recipe->getTooltipString() : T::__('Du hast {item_list} zu {item} umgewandelt.', 'game'));
                break;
        }

        if($recipe->getPictoPrototype() !== null) {
            $this->picto_handler->give_picto($citizen, $recipe->getPictoPrototype());
        }

        $message = $this->translator->trans( $base, [
            '{item_list}' => $this->wrap_concat( $list ),
            '{item}' => $this->wrap( $new_item ),
            '{ap}' => $used_ap <= 0 ? "0" : $used_ap,
            '{cp}' => $used_bp <= 0 ? "0" : $used_bp,
        ], 'game' );

        return self::ErrorNone;
    }
}
