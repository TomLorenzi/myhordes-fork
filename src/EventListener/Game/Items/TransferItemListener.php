<?php


namespace App\EventListener\Game\Items;

use App\Controller\Town\TownController;
use App\Entity\ActionCounter;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\EventActivationMarker;
use App\Entity\HomeIntrusion;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\PrivateMessage;
use App\Enum\ActionHandler\PointType;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Enum\Game\TransferItemType;
use App\Event\Game\Items\ForceTransferItemEvent;
use App\Event\Game\Items\TransferItemEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\BankAntiAbuseService;
use App\Service\CitizenHandler;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\DoctrineCacheService;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: TransferItemEvent::class, method: 'onValidateItemTransfer', priority: 100)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onProtectGarland', priority: 95)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onTriggerBankLockUpdate', priority: 90)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPreHandleSoulPickup', priority: 10)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onTransferItem', priority: 0)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onAdjustCitizenTheftDiscoveryChance', priority: -10)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPostCreateBeyondLogEntries', priority: -10)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPostHandleBankInteraction', priority: -11)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPostHandleCitizenTheft', priority: -12)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPostHandleChestDrop', priority: -20)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPostHandleSoulPickup', priority: -90)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPostHandleHiddenPickup', priority: -91)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onPersistItem', priority: -100)]

#[AsEventListener(event: ForceTransferItemEvent::class, method: 'onProtectGarlandForced', priority: 95)]
final class TransferItemListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            InventoryHandler::class,
            CitizenHandler::class,
            PictoHandler::class,
            RandomGenerator::class,
            LogTemplateHandler::class,
            BankAntiAbuseService::class,
            TranslatorInterface::class,
            DoctrineCacheService::class,
            DeathHandler::class,
            CrowService::class,
            Packages::class
        ];
    }

    protected function singularTransferType( ?Citizen $citizen, ?Inventory $inventory ): TransferItemType {
        if (!$citizen || !$inventory) return TransferItemType::Unknown;

        $citizen_is_at_home = $citizen->getZone() === null;

        // Check if the inventory belongs to a town, and if the town is the same town as that of the citizen
        if ($inventory->getTown() && $inventory->getTown() === $citizen->getTown())
            return $citizen_is_at_home ? TransferItemType::Bank : TransferItemType::Tamer;

        // Check if the inventory belongs to a house, and if the house is owned by the citizen
        if ($inventory->getHome() === $citizen->getHome())
            return $citizen_is_at_home ? TransferItemType::Home : TransferItemType::Tamer;

        // Check if the inventory belongs to a house, and if the house is owned by a different citizen of the same town
        if ($inventory->getHome()?->getCitizen()?->getTown() === $citizen->getTown())
            return $citizen_is_at_home ? TransferItemType::Steal : TransferItemType::Unknown;

        // Check if the inventory belongs directly to the citizen
        if ($inventory->getCitizen() === $citizen)
            return TransferItemType::Rucksack;

        // Check if the inventory belongs directly to the citizen
        if ($inventory->getCitizen()?->getEscortSettings()?->getLeader() === $citizen &&
            $inventory->getCitizen()?->getEscortSettings()?->getAllowInventoryAccess())
            return TransferItemType::Escort;

        // Check if the inventory belongs to the citizens current zone
        if ($inventory->getZone() && !$citizen_is_at_home &&
            $inventory->getZone() === $citizen->getZone() && !$citizen->activeExplorerStats())
            return TransferItemType::Local;

        // Check if the inventory belongs to the citizens current ruin zone
        if ($inventory->getRuinZone() && !$citizen_is_at_home &&
            $inventory->getRuinZone()->getZone() === $citizen->getZone() &&
            ($ex = $citizen->activeExplorerStats()) && /*!$ex->getInRoom() &&*/
            $ex->getX() === $inventory->getRuinZone()->getX() && $ex->getY() === $inventory->getRuinZone()->getY()  )
            return TransferItemType::Local;

        return TransferItemType::Unknown;
    }

    protected function transferType( Item $item, Citizen $citizen, ?Inventory $target, ?Inventory $source, ?TransferItemType &$target_type, ?TransferItemType &$source_type): bool {
        $source_type = !$source ? TransferItemType::Spawn   : $this->singularTransferType( $citizen, $source );
        $target_type = !$target ? TransferItemType::Consume : $this->singularTransferType( $citizen, $target );
        return $this->validateTransferTypes($item, $target_type, $source_type);
    }

    protected function validateTransferTypes( Item $item, TransferItemType $target, TransferItemType $source ): bool {
        // Essential items can not be transferred; only allow spawn and consume
        if ($item->getEssential() && !$source->isValidEssentialSource() && !$target->isValidEssentialTarget())
            return false;

        return $source->checkTarget( $target );
    }

    public function onValidateItemTransfer( TransferItemEvent $event ): void {
        // If a previous event invocation has already set an error code, cancel execution
        if ($event->hasError()) return;

        // Get transfer options
        $opt_enforce_placement = in_array( TransferItemOption::EnforcePlacement, $event->options );
        $opt_allow_extra_bag   = in_array( TransferItemOption::AllowExtraBag, $event->options );
        $opt_allow_multi_heavy = in_array( TransferItemOption::AllowMultiHeavy, $event->options );

        // Can't steal from the bank if it's not night time
        if ($event->modality === TransferItemModality::BankTheft && !$event->townConfig->isNightMode()) {
            $event->pushError(ErrorHelper::ErrorActionNotAvailable);
            return;
        }

        // Block Transfer if citizen is hiding
        if ($event->actor->getZone() && $event->modality !== TransferItemModality::Impound && !$opt_enforce_placement && $event->actor->hasAnyStatus('tg_hide', 'tg_tomb')) {
            $event->pushError(InventoryHandler::ErrorTransferBlocked);
            return;
        }

        // Check if the source is valid
        if ($event->item->getInventory() && ( !$event->from || $event->from->getId() !== $event->item->getInventory()->getId() ) ) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        // Get transfer types
        if (!$this->transferType($event->item, $event->actor, $event->to, $event->from, $type_to, $type_from )) {
            $event->pushError($event->item->getEssential() ? InventoryHandler::ErrorEssentialItemBlocked : InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        // Store from/to types
        $event->type_from = $type_from;
        $event->type_to = $type_to;

        // Validate modality
        if ($event->modality === TransferItemModality::BankTheft && ($type_from !== TransferItemType::Bank || $type_to !== TransferItemType::Rucksack)) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }
        if ($event->modality === TransferItemModality::HideItem && ($type_from !== TransferItemType::Rucksack || $type_to !== TransferItemType::Local)) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        // Check inventory size
        if (!$opt_enforce_placement && ($event->to && ($max_size = $this->getService(InventoryHandler::class)->getSize($event->to)) > 0 && count($event->to->getItems()) >= $max_size ) ) {
            $event->pushError(InventoryHandler::ErrorInventoryFull);
            return;
        }

        // Cannot steal from a citizen you've previously sent items to
        if ($type_from === TransferItemType::Steal && $event->actor->getSpecificActionCounterValue(ActionCounter::ActionTypeSendPMItem, $event->from->getHome()?->getCitizen()?->getId() ?? -1) > 0) {
            $event->pushError(InventoryHandler::ErrorTransferStealPMBlock);
            return;
        }

        // Check exp_b items
        if (!$opt_enforce_placement){
            $bag_item_groups = [
                ['bagxl_#00', 'bag_#00', 'cart_#00'],
                ['pocket_belt_#00']
            ];

            // Cannot carry multiple bag extensions of the same type
            if ( $type_to->isRucksack() && !$opt_allow_extra_bag )
                foreach ($bag_item_groups as $bag_item_group)
                    if (in_array($event->item->getPrototype()->getName(), $bag_item_group) && $event->to->hasAnyItem( ...$bag_item_group ) ) {
                        $event->pushError(InventoryHandler::ErrorExpandBlocked);
                        return;
                    }

            // Cannot deposit a bag extension
            if ( $type_to === TransferItemType::Steal )
                foreach ($bag_item_groups as $bag_item_group)
                    if (in_array($event->item->getPrototype()->getName(), $bag_item_group)) {
                        $event->pushError(InventoryHandler::ErrorTransferStealDropInvalid);
                        return;
                    }
        }

        // Check Heavy item limit
        if (
            !$opt_allow_multi_heavy &&
            $event->item->getPrototype()->getHeavy() &&
            $type_to->isRucksack() &&
            $this->getService(InventoryHandler::class)->countHeavyItems($event->to)
        ) {
            $event->pushError(InventoryHandler::ErrorHeavyLimitHit);
            return;
        }

        // Check Soul limit
        $soul_names = ['soul_blue_#00', 'soul_blue_#01', 'soul_red_#00', 'soul_yellow_#00'];
        if( $type_to->isRucksack() && $event->to->getCitizen() && in_array($event->item->getPrototype()->getName(), $soul_names) && !$event->to->getCitizen()->hasRole("shaman") && $event->to->getCitizen()->getProfession()->getName() !== "shaman"){
            foreach($soul_names as $soul_name) {
                if ($this->getService(InventoryHandler::class)->countSpecificItems($event->to, $soul_name) > 0) {
                    $event->pushError(InventoryHandler::ErrorTooManySouls);
                    return;
                }
            }
        }

        // Prevent undroppable items
        if ($type_from === TransferItemType::Escort && ($event->item->getEssential() || $event->item->getPrototype()->hasProperty('esc_fixed'))) {
            $event->pushError(InventoryHandler::ErrorEscortDropForbidden);
            return;
        }

        // Check bank abuse
        if ($type_from === TransferItemType::Bank) {
            if ($event->actor->getBanished()) {
                $event->pushError(InventoryHandler::ErrorBankBlocked);
                return;
            }

            // At this point, the actor has his hands on a bank item, so we invoke the bank lock system
            $event->invokeBankLock = true;

            //Bank Anti abuse system
            if (!$this->getService(BankAntiAbuseService::class)->allowedToTake($event->actor))
            {
                $event->pushError(InventoryHandler::ErrorBankLimitHit);
                return;
            }

            //if ($modality === TransferItemModality::BankTheft && $this->rand->chance(0.6667))
            //    return InventoryHandler::ErrorBankTheftFailed;
        }

        // Can't deposit items in the home of a dead citizen
        if ( $type_to === TransferItemType::Steal && !$event->to->getHome()->getCitizen()->getAlive()) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        if ($type_from === TransferItemType::Steal || $type_to === TransferItemType::Steal) {

            if ($type_to === TransferItemType::Steal && $event->actor->getTown()->getChaos() ) {
                $event->pushError(TownController::ErrorTownChaos);
                return;
            }

            // Check victim's house protection if they are alive
            $victim = $type_from === TransferItemType::Steal ? $event->from->getHome()->getCitizen() : $event->to->getHome()->getCitizen();
            if ($victim->getAlive()) {
                $ch = $this->getService(CitizenHandler::class);

                // Check house protection
                if ($ch->houseIsProtected( $victim, thief: $event->actor )) {
                    $event->pushError(InventoryHandler::ErrorStealBlocked);
                    return;
                }

                // Check for un-stealable item
                if ($event->item->getPrototype()->hasProperty('nosteal') && $type_from === TransferItemType::Steal) {
                    $event->pushError(InventoryHandler::ErrorUnstealableItem);
                    return;
                }
            }
        }

        if ($type_from === TransferItemType::Spawn && $type_to === TransferItemType::Tamer && $event->modality !== TransferItemModality::None && (!$event->actor->getZone() || !$event->actor->getZone()->isTownZone()) ) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        if ($type_from === TransferItemType::Rucksack && $type_to === TransferItemType::Tamer && $event->modality !== TransferItemModality::Tamer && $event->modality !== TransferItemModality::Impound) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        if ($type_from === TransferItemType::Tamer && $type_to === TransferItemType::Rucksack && $event->modality !== TransferItemModality::Tamer) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            return;
        }

        if ($type_from === TransferItemType::Impound && $type_to === TransferItemType::Tamer && $event->modality !== TransferItemModality::Impound) {
            $event->pushError(InventoryHandler::ErrorInvalidTransfer);
            //return;
        }
    }

    public function onTriggerBankLockUpdate( TransferItemEvent $event ): void {
        if ($event->invokeBankLock) {
            $this->getService(BankAntiAbuseService::class)->increaseBankCount($event->actor);
            $event->markModified()->shouldPersist();
        }
    }

    public function onPreHandleSoulPickup( TransferItemEvent $event ): void {
        if ($event->type_to->isRucksack() && $event->item->getPrototype()->getName() == 'soul_red_#00' && $event->to->getCitizen()?->getZone()) {
            $target_citizen = $event->to->getCitizen();

            // We pick a read soul in the World Beyond
            if ( $target_citizen && !$this->getService(CitizenHandler::class)->hasStatusEffect($target_citizen, "tg_shaman_immune") ) {

                // Produce logs
                if (!in_array(TransferItemOption::Silent, $event->options))
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->beyondItemLog( $target_citizen, $event->item->getPrototype(), false, $event->item->getBroken(), false ) );

                // He is not immune, he dies.
                $this->getService(DeathHandler::class)->kill( $target_citizen, CauseOfDeath::Haunted );
                $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->citizenDeath( $target_citizen ) );

                // The red soul vanishes too
                $this->getService(InventoryHandler::class)->forceRemoveItem($event->item);

                // Prematurely end the event chain
                $event->hasSideEffects = true;
                $event->markModified()->shouldPersist();
                $event->stopPropagation();

            } elseif ( !$target_citizen->hasRole('shaman') && $target_citizen->getProfession()->getName() !== 'shaman' && $this->getService(CitizenHandler::class)->hasStatusEffect($target_citizen, "tg_shaman_immune"))
                $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du nimmst diese wandernde Seele und betest, dass der Schamane weiß, wie man diesen Trank zubereitet! Und du überlebst! Was für ein Glück, du hätten keine müde Mark auf den Scharlatan gewettet.', [], "game"));
        }
    }

    public function onTransferItem( TransferItemEvent $event ): void {
        if (!$event->hasError()) {
            if ($event->to) {
                $this->getService(InventoryHandler::class)->forceMoveItem($event->to, $event->item);
                $event->item->setHidden($event->modality === TransferItemModality::HideItem && $event->type_to === TransferItemType::Local);
            }
            else $this->getService(InventoryHandler::class)->forceRemoveItem( $event->item );
            $event->markModified()->shouldPersist();
        } else $event->stopPropagation();
    }

    public function onAdjustCitizenTheftDiscoveryChance( TransferItemEvent $event ): void {
        if ($event->hasError()) return;

        if ($event->type_from === TransferItemType::Steal || $event->type_to === TransferItemType::Steal) {

            if ($event->citizen->hasAnyStatus('tamer_watch_1', 'tamer_watch_2')) {

                $ch = $this->getService(CitizenHandler::class);

                $factor = $event->citizen->hasStatus('tamer_watch_2') ? 0.75 : 0.5;
                $ch->removeStatus($event->citizen, 'tamer_watch_1');
                $ch->removeStatus($event->citizen, 'tamer_watch_2');

                $event->discovery_change = $event->discovery_change * $factor;
            }

        }
    }

    public function onPostCreateBeyondLogEntries( TransferItemEvent $event ): void {
        // Item log for picking up or dropping items in the world beyond
        if (
            ($event->type_from === TransferItemType::Local || $event->type_to === TransferItemType::Local) &&
            $target_citizen = $event->type_from === TransferItemType::Local ? $event->to?->getCitizen() : $event->from?->getCitizen()
        ) {
            $hide = $event->modality === TransferItemModality::HideItem;

            // We're not trying to hide an item and the item isn't already hidden
            if (!$hide && !$event->item->getHidden()) {
                if (!in_array(TransferItemOption::Silent, $event->options))
                    $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->beyondItemLog($target_citizen, $event->item->getPrototype(), $event->type_to === TransferItemType::Local, $event->item->getBroken(), false));
                $event->markModified()->shouldPersist();
            }
            // We're trying to hide an item
            elseif ($hide && $event->type_to === TransferItemType::Local) {
                $others = false;
                if (!$event->town->getChaos() && $target_citizen->getZone()) foreach ($target_citizen->getZone()->getCitizens() as $c) if (!$c->getBanished()) $others = true;
                if ($others) {
                    $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->beyondItemLog($target_citizen, $event->item->getPrototype(), true, $event->item->getBroken(), true));
                    $event->markModified()->shouldPersist();
                }
            }
        }
    }

    public function onPostHandleBankInteraction( TransferItemEvent $event ): void {
        if ($event->type_from === TransferItemType::Bank || $event->type_to === TransferItemType::Bank) {

            if ($event->modality === TransferItemModality::BankTheft) {
                if ($this->getService(RandomGenerator::class)->chance(0.6)) {
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->bankItemStealLog( $event->actor, $event->item->getPrototype(), false, $event->item->getBroken() ) );
                    $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Dein Diebstahlversuch ist gescheitert! Du bist entdeckt worden!', [], "game"), 'error');
                } else {
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->bankItemStealLog( $event->actor, $event->item->getPrototype(), true, $event->item->getBroken() ) );
                    $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du hast soeben {item} aus der Bank gestohlen. Dein Name wird nicht im Register erscheinen...', ['item' => $this->getService(LogTemplateHandler::class)->wrap($this->getService(LogTemplateHandler::class)->iconize($event->item), 'tool')], "game"));
                }
            } else {
                if (!in_array(TransferItemOption::Silent, $event->options))
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->bankItemLog( $event->actor, $event->item->getPrototype(), $event->type_to === TransferItemType::Bank, $event->item->getBroken() ) );
                if ($event->type_from === TransferItemType::Bank)
                    $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du hast soeben folgenden Gegenstand aus der Bank genommen: {item}. <strong>Sei nicht zu gierig</strong> oder deine Mitbürger könnten dich für einen <strong>Egoisten</strong> halten...', ['item' => $this->getService(LogTemplateHandler::class)->wrap($this->getService(LogTemplateHandler::class)->iconize($event->item), 'tool')], "game"));
            }

            $event->markModified()->shouldPersist();
        }
    }

    public function onPostHandleCitizenTheft( TransferItemEvent $event ): void {
        if ($event->type_from === TransferItemType::Steal || $event->type_to === TransferItemType::Steal) {

            $victim_home = $event->type_from === TransferItemType::Steal ? $event->from->getHome() : $event->to->getHome();
            if (!$victim_home) return;

            $this->getService(CitizenHandler::class)->inflictStatus($event->actor, 'tg_steal');
            $event->hasSideEffects = true;

            // Give picto steal
            $pictoName = $victim_home->getCitizen()->getAlive() ? "r_theft_#00" : "r_plundr_#00";

            $isSanta = false;
            $isLeprechaun = false;
            $hasExplodingDoormat = false;
            $hasRabidDog = false;

            if ($this->getService(InventoryHandler::class)->countSpecificItems($event->actor->getInventory(), 'christmas_suit_full_#00') > 0) {
                if (
                    $victim_home->getCitizen()->getAlive() &&
                    $this->getService(EntityManagerInterface::class)->getRepository(EventActivationMarker::class)->findOneBy(['town' => $event->town, 'active' => true, 'event' => 'christmas'])
                ) $pictoName = "r_santac_#00";
                $isSanta = true;
            } elseif ($this->getService(InventoryHandler::class)->countSpecificItems($event->actor->getInventory(), 'leprechaun_suit_#00') > 0){
                if(
                    $victim_home->getCitizen()->getAlive() &&
                    $this->getService(EntityManagerInterface::class)->getRepository(EventActivationMarker::class)->findOneBy(['town' => $event->town, 'active' => true, 'event' => 'stpatrick'])
                ) $pictoName = "r_lepre_#00";
                $isLeprechaun = true;
            }

            if ($this->getService(InventoryHandler::class)->countSpecificItems($victim_home->getChest(), "trapma_#00") > 0)
                $hasExplodingDoormat = true;
            elseif ($victim_home->hasTag('rabid_dog'))
                $hasRabidDog = true;

            $this->getService(PictoHandler::class)->give_picto($event->actor, $pictoName);

            $alarm = ($this->getService(EntityManagerInterface::class)->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype(
                    $victim_home,
                    $this->getService(DoctrineCacheService::class)->getEntityByIdentifier(CitizenHomeUpgradePrototype::class, 'alarm' ) ) && $victim_home->getCitizen()->getAlive());

            if ($event->type_from === TransferItemType::Steal) {
                if (($hasExplodingDoormat || $hasRabidDog) && $victim_home->getCitizen()->getAlive()) {

                    if ($this->getService(CitizenHandler::class)->isWounded($event->actor)) {
                        $this->getService(DeathHandler::class)->kill($event->actor, match (true) {
                            $hasExplodingDoormat => CauseOfDeath::ExplosiveDoormat,
                            $hasRabidDog => CauseOfDeath::RabidDog,
                            default => CauseOfDeath::Unknown
                        });
                        $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->citizenDeath( $event->actor ) );
                        $event->hasSideEffects = true;
                    }
                    else {
                        $this->getService(CitizenHandler::class)->inflictWound( $event->actor );
                        $event->hasSideEffects = true;
                    }

                    if ($hasExplodingDoormat) {
                        $dm = $this->getService(InventoryHandler::class)->fetchSpecificItems($victim_home->getChest(), [new ItemRequest('trapma_#00')]);
                        if (!empty($dm)) $this->getService(InventoryHandler::class)->forceRemoveItem(array_pop($dm));
                    }

                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townSteal( $victim_home->getCitizen(), $event->actor, $event->item->getPrototype(), true, false, $event->item->getBroken() ) );
                    if ($event->actor->getAlive()) {
                        $base = match (true) {
                            $hasExplodingDoormat => $this->getService(TranslatorInterface::class)->trans('Huch! Scheint, als würde dein Mitbürger nicht wollen, dass jemand seine Sachen durchstöbert. Unter deinen Füßen ist etwas explodiert und hat dich gegen die Wand geschleudert. Du wurdest verletzt!', ['victim' => $victim_home->getCitizen()->getName()], 'game'),
                            $hasRabidDog => $this->getService(TranslatorInterface::class)->trans('Huch! Scheint, als würde dein Mitbürger nicht wollen, dass jemand seine Sachen durchstöbert. Sein Hund hat dich angefallen und dir einige tiefe Bisswunden zugefügt!', ['victim' => $victim_home->getCitizen()->getName()], 'game'),
                            default => null
                        };

                        $event->pushMessage(($base ? "$base<hr/>" : '') .
                                         $this->getService(TranslatorInterface::class)->trans('Der Diebstahl, den du gerade begangen hast, wurde bemerkt! Die Bürger werden gewarnt, dass du den(die,das) {item} bei {victim} gestohlen hast.', ['victim' => $victim_home->getCitizen()->getName(), '{item}' => "<strong><img alt='' src='{$this->getService(Packages::class)->getUrl( "build/images/item/item_{$event->item->getPrototype()->getIcon()}.gif" )}'> {$this->getService(TranslatorInterface::class)->trans($event->item->getPrototype()->getLabel(),[],'items')}</strong>"], 'game')
                        );
                    } else {
                        $event->pushMessage( match (true) {
                            $hasExplodingDoormat => $this->getService(TranslatorInterface::class)->trans('Tja, das hast du davon bei einem paranoiden Pyromanen einbrechen zu wollen. Deine Einzelteile besprenkeln nun seine vier Wände. Das ist lange nicht so spaßig, wie es klingt: Irgendjemand wird hier putzen müssen.', ['victim' => $victim_home->getCitizen()->getName()], 'game'),
                            $hasRabidDog => $this->getService(TranslatorInterface::class)->trans('Tja, das hast du davon bei einem Hundebesitzer einbrechen zu wollen. Dein Blut ist bis an die Decke gespritzt. Das ist lange nicht so spaßig, wie es klingt: Irgendjemand wird hier putzen müssen.', ['victim' => $victim_home->getCitizen()->getName()], 'game'),
                            default => ''
                        });
                    }

                } elseif ($isSanta || $isLeprechaun) {
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townSteal( $victim_home->getCitizen(), null, $event->item->getPrototype(), true, $isSanta, $event->item->getBroken(), $isLeprechaun ) );
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townSteal( $victim_home->getCitizen(), $event->actor, $event->item->getPrototype(), true, false, $event->item->getBroken(), false )->setAdminOnly(true) );
                    $event->pushMessage( $this->getService(TranslatorInterface::class)->trans($isSanta ? 'Dank deines Kostüms konntest du {item} von {victim} stehlen, <strong>ohne erkannt zu werden</strong>.<hr/>Ho ho ho.' : 'Dank deines Kostüms konntest du {item} von {victim} stehlen, <strong>ohne erkannt zu werden</strong>.<hr/>Was für ein guter Morgen!', [
                        '{victim}' => $victim_home->getCitizen()->getName(),
                        '{item}' => $this->getService(LogTemplateHandler::class)->wrap($this->getService(LogTemplateHandler::class)->iconize($event->item))], 'game') );
                } elseif ($alarm) {
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townSteal( $victim_home->getCitizen(), $event->actor, $event->item->getPrototype(), true, false, $event->item->getBroken() ) );
                    $event->pushMessage( $this->getService(TranslatorInterface::class)->trans('Der Diebstahl, den du gerade begangen hast, wurde bemerkt! Die Bürger werden gewarnt, dass du den(die,das) {item} bei {victim} gestohlen hast.', ['victim' => $victim_home->getCitizen()->getName(), '{item}' => "<strong><img alt='' src='{$this->getService(Packages::class)->getUrl( "build/images/item/item_{$event->item->getPrototype()->getIcon()}.gif" )}'> {$this->getService(TranslatorInterface::class)->trans($event->item->getPrototype()->getLabel(),[],'items')}</strong>"], 'game'));
                    //$this->getService(CitizenHandler::class)->inflictStatus( $event->actor, 'terror' );
                    //$event->pushMessage($this->getService(TranslatorInterface::class)->trans('{victim}s Alarmanlage hat die halbe Stadt aufgeweckt und dich zu Tode erschreckt!', ['{victim}' => $victim_home->getCitizen()->getName()], 'game') );
                } elseif ($this->getService(RandomGenerator::class)->chance($event->discovery_change) || !$victim_home->getCitizen()->getAlive()) {
                    if ($victim_home->getCitizen()->getAlive()){
                        $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townSteal( $victim_home->getCitizen(), $event->actor, $event->item->getPrototype(), true, false, $event->item->getBroken() ) );
                        $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Der Diebstahl, den du gerade begangen hast, wurde bemerkt! Die Bürger werden gewarnt, dass du den(die,das) {item} bei {victim} gestohlen hast.', ['victim' => $victim_home->getCitizen()->getName(), '{item}' => "<strong><img alt='' src='{$this->getService(Packages::class)->getUrl( "build/images/item/item_{$event->item->getPrototype()->getIcon()}.gif" )}'> {$this->getService(TranslatorInterface::class)->trans($event->item->getPrototype()->getLabel(),[],'items')}</strong>"], 'game'));
                    } else {
                        $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townLoot( $victim_home->getCitizen(), $event->actor, $event->item->getPrototype(), true, false, $event->item->getBroken() ) );
                        $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du hast dir folgenden Gegenstand unter den Nagel gerissen: {item}. Dein kleiner Hausbesuch bei † {victim} ist allerdings aufgeflogen...<hr /><strong>Dieser Gegenstand wurde in deiner Truhe abgelegt.</strong>', ['{item}' => $this->getService(LogTemplateHandler::class)->wrap($this->getService(LogTemplateHandler::class)->iconize($event->item)), '{victim}' => $victim_home->getCitizen()->getName()], 'game') );
                    }
                } else {
                    $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Es ist dir gelungen, {item} von {victim} zu stehlen <strong>ohne entdeckt zu werden</strong>. Nicht schlecht!', [
                        '{victim}' => $victim_home->getCitizen(),
                        '{item}' => $this->getService(LogTemplateHandler::class)->wrap($this->getService(LogTemplateHandler::class)->iconize($event->item))
                    ], 'game') );
                }

                $this->getService(CrowService::class)->postAsPM( $victim_home->getCitizen(), '', '', PrivateMessage::TEMPLATE_CROW_THEFT, $event->item->getPrototype()->getId() );
            } else {
                $messages = [ $this->getService(TranslatorInterface::class)->trans('Du hast den(die,das) {item} bei {victim} abgelegt...', ['{item}' => "<strong><img alt='' src='{$this->getService(Packages::class)->getUrl( "build/images/item/item_{$event->item->getPrototype()->getIcon()}.gif" )}'> {$this->getService(TranslatorInterface::class)->trans($event->item->getPrototype()->getLabel(),[],'items')}</strong>",  '{victim}' => "<strong>{$victim_home->getCitizen()->getName()}</strong>"], 'game')];
                if ( !$isSanta && !$isLeprechaun && ($this->getService(RandomGenerator::class)->chance(0.1) || $alarm) ) {
                    $messages[] = $this->getService(TranslatorInterface::class)->trans('Du bist bei deiner Aktion aufgeflogen! <strong>Deine Mitbürger wissen jetz Bescheid!</strong>', [], 'game');
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->townSteal( $victim_home->getCitizen(), $event->actor, $event->item->getPrototype(), false, false, $event->item->getBroken() ) );
                }

                $event->pushMessage(implode('<hr/>', $messages));
            }

            $intrusion = $this->getService(EntityManagerInterface::class)->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $event->actor, 'victim' => $victim_home->getCitizen()]);
            if ($intrusion) {
                $this->getService(EntityManagerInterface::class)->remove($intrusion);
                $event->hasSideEffects = true;
            }

            $event->markModified()->shouldPersist();
        }
    }

    public function onPostHandleChestDrop( TransferItemEvent $event ): void {
        if ($event->type_from === TransferItemType::Rucksack && $event->type_to === TransferItemType::Home) {

            $hiddenStash = $event->from->getCitizen()->property(CitizenProperties::ChestHiddenStashLimit) ?? 0;
            if ($hiddenStash && $event->to->getItems()->filter(fn(Item $i) => $i->getHidden())->count() < $hiddenStash) {
                $event->item->setHidden(true);
                $event->markModified()->shouldPersist();
            }

        }
    }

    public function onPostHandleSoulPickup( TransferItemEvent $event ): void {
        if ($event->type_to->isRucksack() && $event->to->getCitizen() && $event->item->getPrototype()->getName() == 'soul_blue_#00' && $event->item->getFirstPick()) {
            // Set first pick to false
            $event->item->setFirstPick(false);

            // In the "Job" version of the shaman, the one that pick a blue soul for the 1st time gets the "r_collec" picto
            if ($event->townConfig->is(TownConf::CONF_FEATURE_SHAMAN_MODE, ['job', 'both'], "normal"))
                $this->getService(PictoHandler::class)->give_picto($event->to->getCitizen(), "r_collec2_#00");

            // Persist item
            $this->getService(EntityManagerInterface::class)->persist($event->item);
            $event->markModified()->shouldPersist();
        }
    }

    public function onPostHandleHiddenPickup( TransferItemEvent $event ): void {
        if ($event->type_to->isRucksack() && $event->to->getCitizen() && $event->item->getHidden()) {
            // Set hidden to false
            $event->item->setHidden(false);
            $event->markModified()->shouldPersist();
        }
    }

    public function onPersistItem( TransferItemEvent $event ): void {
        // Persist or remove the item
        if ($event->item->getInventory())
            $this->getService(EntityManagerInterface::class)->persist($event->item);
        else $this->getService(EntityManagerInterface::class)->remove($event->item);
    }

    public function onProtectGarland( TransferItemEvent $event ): void {
        // If a previous event invocation has already set an error code, cancel execution
        if ($event->hasError()) return;

        // Get transfer options
        $opt_enforce_placement = in_array( TransferItemOption::EnforcePlacement, $event->options );


        if ( !$opt_enforce_placement && $event->item->getPrototype()->getName() === 'xmas_gift_#01' ) {

            // Can't take an installed garland from your own home
            if ( $event->type_from === TransferItemType::Home ) {
                $event->stopPropagation();
                $event->pushError( ErrorHelper::ErrorActionNotAvailable );
                $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du musst die Girlande zuerst <strong>abnehmen</strong>, bevor du sie <strong>mitnehmen</strong> kannst.', [], 'game'), 'error');
            }
            // When stealing a garland, convert it back to an uninstalled garland
            elseif ( $event->type_from === TransferItemType::Steal || $event->type_to->isRucksack() ) {
                $base_garland = $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('xmas_gift_#00');
                if ($base_garland) $event->item->setPrototype( $base_garland );
            }

        }
    }

    public function onProtectGarlandForced( ForceTransferItemEvent $event ): void {
        // Force garland transformation when moving to anything that is not a home inventory
        if ( $event->item->getPrototype()->getName() === 'xmas_gift_#01' && !$event->to?->getHome() ) {
            $base_garland = $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('xmas_gift_#00');
            if ($base_garland) $event->item->setPrototype( $base_garland );
        }
    }
}