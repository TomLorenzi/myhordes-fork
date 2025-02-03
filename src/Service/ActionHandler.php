<?php


namespace App\Service;


use App\Entity\ActionCounter;
use App\Entity\BuildingPrototype;
use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\EscortActionGroup;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeActionPrototype;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\Recipe;
use App\Entity\Requirement;
use App\Entity\Result;
use App\Entity\RuinZone;
use App\Entity\ZonePrototype;
use App\Enum\ActionHandler\ActionValidity;
use App\Enum\ActionHandler\PointType;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\ItemPoisonType;
use App\Service\Actions\Game\AtomProcessors\Effect\AtomEffectProcessor;
use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use App\Service\Actions\Game\DecodeConditionalMessageAction;
use App\Service\Actions\Game\WrapObjectsForOutputAction;
use App\Structures\ActionHandler\Evaluation;
use App\Structures\ActionHandler\Execution;
use App\Structures\EscortItemActionSet;
use App\Structures\FriendshipActionTarget;
use App\Structures\TownConf;
use App\Translation\T;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly DeathHandler $death_handler,
        private readonly InventoryHandler $inventory_handler,
        private readonly RandomGenerator $random_generator,
        private readonly ItemFactory $item_factory,
        private readonly TranslatorInterface $translator,
        private readonly TownHandler $town_handler,
        private readonly PictoHandler $picto_handler,
        private readonly Packages $assets,
        private readonly LogTemplateHandler $log,
        private readonly ConfMaster $conf,
        private readonly GameProfilerService $gps,
        private readonly ContainerInterface $container,
        private readonly EventProxyService $proxyService,
        private readonly WrapObjectsForOutputAction $wrapObjectsForOutputAction,
        private readonly DecodeConditionalMessageAction $messageDecoder,
    ) {}

    protected function evaluate( Citizen $citizen, ?Item $item, $target, ItemAction $action, ?string &$message, ?Evaluation &$cache = null, ?Citizen $contextCitizen = null ): ActionValidity {

        if ($item && !$item->getPrototype()->getActions()->contains( $action )) return ActionValidity::None;
        if ($target && (!$action->getTarget() || !$this->targetDefinitionApplies($target, $action->getTarget(), reference: $citizen)))
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
                    if (!AtomRequirementProcessor::process( $this->container, $cache, $requirementsDataElement->atomList, $contextCitizen ))
                        $current_state = $current_state->merge($this_state);
            }

            if (!$current_state->thatOrAbove($last_state) && $thisMessage = $meta_requirement->getFailureText())
                $cache->addMessage($thisMessage, translationDomain: 'items');
        }

        $messages = $cache->getMessages( $this->translator, $this->wrapObjectsForOutputAction, $this->messageDecoder, $citizen->fullPropertySet());
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

    public function getHeroicDonatedFromCitizen(HeroicActionPrototype $heroic, Citizen $citizen, bool $used = false ): ?Citizen {
        $giftedActions = array_column( array_filter(
            $citizen->getSpecificActionCounter( ActionCounter::ActionTypeReceiveHeroic )->getAdditionalData() ?? [],
            fn($entry) => ($entry['valid'] && ($entry['used'] ?? false) === $used)
        ), null, 'action');

        $cid = Arr::get( $giftedActions, "{$heroic->getName()}.origin", Arr::get( $giftedActions, "{$heroic->getName()}.from", -1 ) );
        if ($cid <= 0) return null;
        $donated_by_citizen = $this->entity_manager->getRepository(Citizen::class)->find( $cid );
        if ($donated_by_citizen?->getTown() !== $citizen->getTown()) return null;

        return $donated_by_citizen;
    }

    /**
     * @param Citizen $citizen
     * @param HeroicActionPrototype[] $available
     * @param HeroicActionPrototype[] $crossed
     * @param HeroicActionPrototype[] $used
     */
    public function getAvailableIHeroicActions(Citizen $citizen, ?array &$available, ?array &$crossed, ?array &$used ) {
        $available = $crossed = $used = [];

        if (!$citizen->getProfession()->getHeroic()) return;
        $is_at_00 = $citizen->getZone() && $citizen->getZone()->isTownZone();

        foreach ($citizen->getHeroicActions() as $heroic) {
            if ($is_at_00 && !$heroic->getAction()->getAllowedAtGate()) continue;
            $mode = $this->evaluate( $citizen, null, null, $heroic->getAction(), $tx, contextCitizen: $this->getHeroicDonatedFromCitizen( $heroic, $citizen ) );
            if ($mode->thatOrAbove( ActionValidity::Allow )) $available[] = $heroic;
            else if ($mode->thatOrAbove(ActionValidity::Crossed)) $crossed[] = $heroic;
        }

        foreach ($citizen->getUsedHeroicActions() as $used_heroic) {
            if ($citizen->getHeroicActions()->contains($used_heroic) || ($is_at_00 && !$used_heroic->getAction()->getAllowedAtGate())) continue;
            $mode = $this->evaluate( $citizen, null, null, $used_heroic->getAction(), $tx, contextCitizen: $this->getHeroicDonatedFromCitizen( $used_heroic, $citizen ) );
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
     * @param Citizen|FriendshipActionTarget|Item|ItemPrototype $target
     * @param ItemTargetDefinition $definition
     * @return bool
     */
    public function targetDefinitionApplies(
        Citizen|ItemPrototype|FriendshipActionTarget|Item $target,
        ItemTargetDefinition $definition,
        bool $forSelection = false,
        ?Citizen $reference = null
    ): bool {
        switch ($definition->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:case ItemTargetDefinition::ItemSelectionTypePoison: case ItemTargetDefinition::ItemTypeChestSelectionType:
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
                if ( $target->getZone()->getDistance() > ($reference?->property(CitizenProperties::HeroRescueRange) ?? CitizenProperties::HeroRescueRange->default()) ) return false;
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
     * @param bool $escort_mode
     * @return int
     */
    public function execute( Citizen &$citizen, ?Item &$item, &$target, ItemAction $action, ?string &$message, ?array &$remove, bool $force = false, bool $escort_mode = false, ?Citizen $contextCitizen = null ): int {

        $remove = [];

        $kill_by_poison   = $item && ($item->getPoison() === ItemPoisonType::Deadly) && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $infect_by_poison = $item && ($item->getPoison() === ItemPoisonType::Infectious) && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $random_by_poison = $item && ($item->getPoison() === ItemPoisonType::Strange) && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $spread_poison = ItemPoisonType::None;

        /** @var ?Evaluation $evaluation */
        $evaluation = null;

        if (!$force) {
            $mode = $this->evaluate( $citizen, $item, $target, $action, $tx, $evaluation, $contextCitizen );
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

        $cache = new Execution($this->entity_manager, $citizen, $item, $target, $this->conf->getTownConfiguration( $citizen->getTown() ), $this->conf->getGlobalConf());
        $default_message = $escort_mode ? $action->getEscortMessage() : $action->getMessage();
        $cache->setEscortMode($escort_mode);
        $cache->setAction($action);

        if ($default_message) $cache->addMessage($default_message, translationDomain: 'items');
        foreach ($evaluation?->getProcessedItems('item_tool') ?? [] as $tool) $cache->addToolItem( $tool );

        $cache->addTranslationKey('tamer_dog', LogTemplateHandler::generateDogName($citizen->getId(), $this->translator));

        if ($citizen->activeExplorerStats())
            $cache->setTargetRuinZone($ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByExplorerStats($citizen->activeExplorerStats()));

        $all_atoms = [];

        $processResultList = function(array $results) use (&$all_atoms, &$processResultList): void {

            /** @var Result $result */
            foreach ($results as $result) {

                if ($result->getAtoms()) $all_atoms = [...$all_atoms, ...$result->getAtoms()];
                if ($result_group = $result->getResultGroup())
                    $processResultList($this->random_generator->pickResultsFromGroup( $result_group ));
            }

        };

        $processResultList($action->getResults()->toArray());

        if (!empty($all_atoms)) {
            $container = (new EffectsDataContainer())->fromArray([['atomList' => $all_atoms]]);
            foreach ( $container->all() as $effectsDataElement ) {
                AtomEffectProcessor::process($this->container, $cache, $effectsDataElement->atomList, $contextCitizen);
                if ($cache->getRegisteredError()) return $cache->getRegisteredError();
            }
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
                    break;

                // Add drunk status
                case 2:
                    $this->picto_handler->award_picto_to($citizen, 'r_alcool_#00');
                    $this->citizen_handler->removeStatus($citizen, 'hungover');
                    $this->citizen_handler->removeStatus($citizen, 'tg_no_hangover');
                    if (!$this->citizen_handler->hasStatusEffect($citizen, 'drunk')) {
                        $this->citizen_handler->inflictStatus($citizen, 'drunk');
                        $cache->addMessage(T::__('Plötzlich fängt alles um dich herum an, sich zu drehen ...','items'), translationDomain: 'items');
                    }
                    break;
            }

        }

        if($cache->hasMessages())
            $message = implode('<hr />', $cache->getMessages( $this->translator, $this->wrapObjectsForOutputAction, $this->messageDecoder, $citizen->fullPropertySet()));

        return self::ErrorNone;
    }

    public function execute_recipe(Citizen $citizen, Recipe $recipe, ?array &$remove, ?string &$message, int $penalty = 0 ): int {
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

        $break_item = null;
        $break_chance = 0;

        $silent = false;
        if (in_array($recipe->getType(), $workshop_types)) {
            $have_saw  = $this->inventory_handler->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy(['name' => 'saw_tool_#00']), false, false ) > 0;
            $have_manu = $this->town_handler->getBuilding($town, 'small_factory_#00', true) !== null;

            $breakable_saw = $have_saw ? null : ($this->inventory_handler->fetchSpecificItems( $c_inv, ['saw_tool_temp_#00'] )[0] ?? null);
            if ($breakable_saw) {
                $break_chance = 0.15;
                $break_item = $breakable_saw;
            }

            $ap = $penalty + (3 - (($have_saw || $breakable_saw) ? 1 : 0) - ($have_manu ? 1 : 0));
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

        $this->citizen_handler->deductPointsWithFallback( $citizen, PointType::AP, PointType::CP, $ap, $used_ap, $used_bp);

        if ($recipe->getType() === Recipe::WorkshopTypeTechSpecific)
            $citizen->getSpecificActionCounter(ActionCounter::ActionTypeSpecialActionTech)->increment();

        $new_items = [];
        if ($recipe->isMultiOut())
            foreach ($recipe->getResult()->getEntries() as $result)
                for ($i = 0; $i < $result->getChance(); $i++)
                    $new_items[] = $result->getPrototype();
        else
            $new_items[] = $this->random_generator->pickItemPrototypeFromGroup( $recipe->getResult(), $this->conf->getTownConfiguration( $citizen->getTown() ), $this->conf->getCurrentEvents( $citizen->getTown() ) );

        foreach ($new_items as $new_item)
            $this->proxyService->placeItem( $citizen, $this->item_factory->createItem( $new_item ) , $target_inv, true, $silent );
        $this->gps->recordRecipeExecuted( $recipe, $citizen, $new_items );

        if (in_array($recipe->getType(), $workshop_types))
            $this->entity_manager->persist( $this->log->workshopConvert( $citizen, array_map( fn(Item $e)  => [$e->getPrototype()], $items  ), array_map( fn(ItemPrototype $e)  => [$e], $new_items ) ) );

        switch ( $recipe->getType() ) {
            case Recipe::WorkshopType:
            case Recipe::WorkshopTypeShamanSpecific:
            case Recipe::WorkshopTypeTechSpecific:
              $base = match ($recipe->getAction()) {
                  "Öffnen"      => T::__('Du hast {item_list} in der Werkstatt geöffnet und erhälst {item}.', 'game'),
                  "Zerlegen"    => T::__('Du hast {item_list} in der Werkstatt zu {item} zerlegt.', 'game'),
                  default       => match (true) {
                      count($new_items) === 1 && $used_bp === 0                 => T::__('Du hast ein(e,n) {item} hergestellt. Der Gegenstand wurde in der Bank abgelegt.<hr />Du hast dafür <strong>{ap} Aktionspunkt(e)</strong> verbraucht.', 'game'),
                      count($new_items) === 1 && $used_bp > 0 && $used_ap <= 0  => T::__('Du hast ein(e,n) {item} hergestellt. Der Gegenstand wurde in der Bank abgelegt.<hr />Du hast dafür <strong>{cp} Baupunkt(e)</strong> verbraucht.', 'game'),
                      count($new_items) === 1                                   => T::__('Du hast ein(e,n) {item} hergestellt. Der Gegenstand wurde in der Bank abgelegt.<hr />Du hast dafür <strong>{ap} Aktionspunkt(e)</strong> und <strong>{cp} Baupunkt(e)</strong> verbraucht.', 'game'),
                      $used_bp === 0                 => T::__('Du hast {item} hergestellt. Die Gegenstände wurden in der Bank abgelegt.<hr />Du hast dafür <strong>{ap} Aktionspunkt(e)</strong> verbraucht.', 'game'),
                      $used_bp > 0 && $used_ap <= 0  => T::__('Du hast {item} hergestellt. Die Gegenstände wurden in der Bank abgelegt.<hr />Du hast dafür <strong>{cp} Baupunkt(e)</strong> verbraucht.', 'game'),
                      default                        => T::__('Du hast {item} hergestellt. Die Gegenstände wurden in der Bank abgelegt.<hr />Du hast dafür <strong>{ap} Aktionspunkt(e)</strong> und <strong>{cp} Baupunkt(e)</strong> verbraucht.', 'game'),
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
            '{item}' => $this->wrap_concat( $new_items ),
            '{ap}' => $used_ap <= 0 ? "0" : $used_ap,
            '{cp}' => $used_bp <= 0 ? "0" : $used_bp,
        ], 'game' );

        $break = ($break_item && $this->random_generator->chance( $break_chance ));
        if ($break) {
            $message .= '<hr/>' . $this->translator->trans( 'Ups, dein(e) {item} scheint dabei kaputt gegangen zu sein...', [
                    '{item}' => $this->wrap( $break_item->getPrototype() ),
                ], 'game' );
            $this->entity_manager->persist( $break_item->setBroken(true) );
        }

        return self::ErrorNone;
    }
}
