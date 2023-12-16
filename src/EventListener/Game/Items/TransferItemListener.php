<?php


namespace App\EventListener\Game\Items;

use App\Controller\Town\TownController;
use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Enum\Game\TransferItemType;
use App\Event\Game\Items\TransferItemEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\BankAntiAbuseService;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: TransferItemEvent::class, method: 'onValidateItemTransfer', priority: 100)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onTriggerBankLockUpdate', priority: 90)]
#[AsEventListener(event: TransferItemEvent::class, method: 'onTransferItem', priority: 0)]
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
        if ($event->error_code !== InventoryHandler::ErrorNone) return;

        // Get transfer options
        $opt_enforce_placement = in_array( TransferItemOption::EnforcePlacement, $event->options );
        $opt_allow_extra_bag   = in_array( TransferItemOption::AllowExtraBag, $event->options );
        $opt_allow_multi_heavy = in_array( TransferItemOption::AllowMultiHeavy, $event->options );

        // Block Transfer if citizen is hiding
        if ($event->actor->getZone() && $event->modality !== TransferItemModality::Impound && !$opt_enforce_placement && $event->actor->hasAnyStatus('tg_hide', 'tg_tomb')) {
            $event->error_code = InventoryHandler::ErrorTransferBlocked;
            return;
        }

        // Check if the source is valid
        if ($event->item->getInventory() && ( !$event->from || $event->from->getId() !== $event->item->getInventory()->getId() ) ) {
            $event->error_code = InventoryHandler::ErrorInvalidTransfer;
            return;
        }

        if (!$this->transferType($event->item, $event->actor, $event->to, $event->from, $type_to, $type_from )) {
            $event->error_code = $event->item->getEssential() ? InventoryHandler::ErrorEssentialItemBlocked : InventoryHandler::ErrorInvalidTransfer;
            return;
        }

        // Store from/to types
        $event->type_from = $type_from;
        $event->type_to = $type_to;

        // Check inventory size
        if (!$opt_enforce_placement && ($event->to && ($max_size = $this->getService(InventoryHandler::class)->getSize($event->to)) > 0 && count($event->to->getItems()) >= $max_size ) ) {
            $event->error_code = InventoryHandler::ErrorInventoryFull;
            return;
        }

        // Check exp_b items already in inventory
        if (!$opt_allow_extra_bag && !$opt_enforce_placement){
            $bag_item_groups = [
                ['bagxl_#00', 'bag_#00', 'cart_#00'],
                ['pocket_belt_#00']
            ];

            if ( $type_to->isRucksack() )
                foreach ($bag_item_groups as $bag_item_group)
                    if (in_array($event->item->getPrototype()->getName(), $bag_item_group) && $event->to->hasAnyItem( ...$bag_item_group ) ) {
                        $event->error_code = InventoryHandler::ErrorExpandBlocked;
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
            $event->error_code = InventoryHandler::ErrorHeavyLimitHit;
            return;
        }

        // Check Soul limit
        $soul_names = ['soul_blue_#00', 'soul_blue_#01', 'soul_red_#00', 'soul_yellow_#00'];
        if( $type_to->isRucksack() && $event->to->getCitizen() && in_array($event->item->getPrototype()->getName(), $soul_names) && !$event->to->getCitizen()->hasRole("shaman") && $event->to->getCitizen()->getProfession()->getName() !== "shaman"){
            foreach($soul_names as $soul_name) {
                if ($this->getService(InventoryHandler::class)->countSpecificItems($event->to, $soul_name) > 0) {
                    $event->error_code = InventoryHandler::ErrorTooManySouls;
                    return;
                }
            }
        }

        // Prevent undroppable items
        if ($type_from === TransferItemType::Escort && ($event->item->getEssential() || $event->item->getPrototype()->hasProperty('esc_fixed'))) {
            $event->error_code = InventoryHandler::ErrorEscortDropForbidden;
            return;
        }

        // Check bank abuse
        if ($type_from === TransferItemType::Bank) {
            if ($event->actor->getBanished()) {
                $event->error_code = InventoryHandler::ErrorBankBlocked;
                return;
            }

            // At this point, the actor has his hands on a bank item, so we invoke the bank lock system
            $event->invokeBankLock = true;

            //Bank Anti abuse system
            if (!$this->getService(BankAntiAbuseService::class)->allowedToTake($event->actor))
            {
                $event->error_code = InventoryHandler::ErrorBankLimitHit;
                return;
            }

            //if ($modality === TransferItemModality::BankTheft && $this->rand->chance(0.6667))
            //    return InventoryHandler::ErrorBankTheftFailed;
        }

        // Can't deposit items in the home of a dead citizen
        if ( $type_to === TransferItemType::Steal && !$event->to->getHome()->getCitizen()->getAlive()) {
            $event->error_code = InventoryHandler::ErrorInvalidTransfer;
            return;
        }

        if ($type_from === TransferItemType::Steal || $type_to === TransferItemType::Steal) {

            if ($type_to === TransferItemType::Steal && $event->actor->getTown()->getChaos() ) {
                $event->error_code = TownController::ErrorTownChaos;
                return;
            }

            // Check victim's house protection if they are alive
            $victim = $type_from === TransferItemType::Steal ? $event->from->getHome()->getCitizen() : $event->to->getHome()->getCitizen();
            if ($victim->getAlive()) {
                $ch = $this->getService(CitizenHandler::class);

                // Check house protection
                if ($ch->houseIsProtected( $victim )) {
                    $event->error_code = InventoryHandler::ErrorStealBlocked;
                    return;
                }

                // Check for un-stealable item
                if ($event->item->getPrototype()->getName() === 'trapma_#00' && $type_from === TransferItemType::Steal) {
                    $event->error_code = InventoryHandler::ErrorUnstealableItem;
                    return;
                }
            }
        }

        if ($type_from === TransferItemType::Spawn && $type_to === TransferItemType::Tamer && $event->modality !== TransferItemModality::None && (!$event->actor->getZone() || !$event->actor->getZone()->isTownZone()) ) {
            $event->error_code = InventoryHandler::ErrorInvalidTransfer;
            return;
        }

        if ($type_from === TransferItemType::Rucksack && $type_to === TransferItemType::Tamer && $event->modality !== TransferItemModality::Tamer && $event->modality !== TransferItemModality::Impound) {
            $event->error_code = InventoryHandler::ErrorInvalidTransfer;
            return;
        }

        if ($type_from === TransferItemType::Impound && $type_to === TransferItemType::Tamer && $event->modality !== TransferItemModality::Impound) {
            $event->error_code = InventoryHandler::ErrorInvalidTransfer;
            return;
        }

        $event->error_code = InventoryHandler::ErrorNone;
    }

    public function onTriggerBankLockUpdate( TransferItemEvent $event ): void {
        if ($event->invokeBankLock) {
            $this->getService(BankAntiAbuseService::class)->increaseBankCount($event->actor);
            $event->markModified();
        }
    }

    public function onTransferItem( TransferItemEvent $event ): void {
        if ($event->error_code === InventoryHandler::ErrorNone) {
            if ($event->to)
                $this->getService(InventoryHandler::class)->forceMoveItem( $event->to, $event->item );
            else $this->getService(InventoryHandler::class)->forceRemoveItem( $event->item );
            $event->markModified();
        }
    }
}