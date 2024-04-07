<?php

namespace App\Service;

use App\Entity\AdminReport;
use App\Entity\Announcement;
use App\Entity\BlackboardEdit;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CommunityEvent;
use App\Entity\GlobalPrivateMessage;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\RuinZone;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Zone;
use App\Enum\EventStages\BuildingEffectStage;
use App\Enum\EventStages\BuildingValueQuery;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Enum\ScavengingActionType;
use App\Event\Common\Messages\Announcement\NewAnnouncementEvent;
use App\Event\Common\Messages\Announcement\NewEventAnnouncementEvent;
use App\Event\Common\Messages\Forum\ForumMessageNewPostEvent;
use App\Event\Common\Messages\Forum\ForumMessageNewThreadEvent;
use App\Event\Common\Messages\GlobalPrivateMessage\GPMessageNewPostEvent;
use App\Event\Common\Messages\GlobalPrivateMessage\GPMessageNewThreadEvent;
use App\Event\Common\Social\ContentReportEvents\BlackboardEditContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\CitizenRankingProxyContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\GlobalPrivateMessageContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\PostContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\PrivateMessageContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\UserContentReportEvent;
use App\Event\Common\Social\FriendEvent;
use App\Event\Common\User\PictoPersistedEvent;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\Event\Game\Citizen\CitizenQueryDigChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDeathChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDefenseEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchInfoEvent;
use App\Event\Game\Citizen\CitizenWorkshopOptionsData;
use App\Event\Game\Citizen\CitizenWorkshopOptionsEvent;
use App\Event\Game\GameInteractionEvent;
use App\Event\Game\Items\TransferItemEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingCatapultItemTransformEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingDestroyedDuringAttackPostEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingDestructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownParameterEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownRoleEnabledEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePreAttackEvent;
use App\Event\Game\Town\Basic\Core\AfterJoinTownEvent;
use App\Event\Game\Town\Basic\Core\BeforeJoinTownData;
use App\Event\Game\Town\Basic\Core\BeforeJoinTownEvent;
use App\Event\Game\Town\Basic\Core\JoinTownEvent;
use App\Structures\FriendshipActionTarget;
use App\Structures\HTMLParserInsight;
use Doctrine\Common\Util\ClassUtils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventProxyService
{
    public function __construct(
        private EventDispatcherInterface $ed,
        private EventFactory $ef
    ) { }

    /**
     * @param Building $building
     * @param string|Citizen|null $method
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingConstruction( Building $building, string|Citizen $method = null ): void {
        $this->ed->dispatch( $this->ef->gameEvent( BuildingConstructionEvent::class, $building->getTown() )->setup( $building, $method ) );
    }

    /**
     * @param Building $building
     * @param string $method
     * @param bool $post
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingDestruction( Building $building, string $method = 'attack', bool $post = false ): void {
        $this->ed->dispatch( $this->ef->gameEvent( $post ? BuildingDestroyedDuringAttackPostEvent::class : BuildingDestructionEvent::class, $building->getTown() )->setup( $building, $method ) );
    }

    /**
     * @param Building $building
     * @param bool $isPreAttack
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingUpgrade( Building $building, bool $isPreAttack ): void {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->ed->dispatch($this->ef->gameEvent($isPreAttack ? BuildingUpgradePreAttackEvent::class : BuildingUpgradePostAttackEvent::class, $building->getTown() )->setup($building ) );
    }

    /**
     * @param Building $building
     * @param ?Building $upgraded
     * @param BuildingEffectStage $stage
     * @return Building[]
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingEffect( Building $building, ?Building $upgraded, BuildingEffectStage $stage ): array {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->ed->dispatch($event = $this->ef->gameEvent($stage->eventClass(), $building->getTown() )->setup($building,$upgraded) );
        return $event->destroyed_buildings;
    }

    /**
     * @param Town $town
     * @param Item $item
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingQueryNightwatchDefenseBonus( Town $town, Item $item ): int {
        $this->ed->dispatch( $event = $this->ef->gameEvent( BuildingQueryNightwatchDefenseBonusEvent::class, $town )->setup( $item ) );
        return $event->defense;
    }

    /**
     * @param Citizen $citizen
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function citizenPostDeath( Citizen $citizen ): void {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CitizenPostDeathEvent::class, $citizen->getTown() )->setup( $citizen ) );
    }

    /**
     * @param Citizen $citizen
     * @param Zone|RuinZone|null $zone,
     * @param ScavengingActionType $type,
     * @param bool $night
     * @return float
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function citizenQueryDigChance( Citizen $citizen, Zone|RuinZone|null $zone, ScavengingActionType $type, bool $night ): float {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CitizenQueryDigChancesEvent::class, $citizen->getTown() )->setup( $citizen, $type, $zone, at_night: $night ) );
        return $event->chance;
    }

    public function citizenWorkshopOptions( Citizen $citizen ): CitizenWorkshopOptionsData {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CitizenWorkshopOptionsEvent::class, $citizen->getTown() )->setup( $citizen ) );
        return $event->data;
    }

	public function citizenQueryNightwatchDefense(Citizen $citizen, LoggerInterface $log = null): int {
		$this->ed->dispatch($event = $this->ef->gameEvent(CitizenQueryNightwatchDefenseEvent::class, $citizen->getTown())->setup($citizen, log: $log));
		return $event->nightwatchDefense;
	}

	public function citizenQueryNightwatchDeathChance(Citizen $citizen, LoggerInterface $log = null): array {
		$this->ed->dispatch($event = $this->ef->gameEvent(CitizenQueryNightwatchDeathChancesEvent::class, $citizen->getTown())->setup($citizen, log: $log));
		return [
			'death' => $event->deathChance,
			'terror' => $event->terrorChance,
			'wound' => $event->woundChance,
			'hint' => $event->hintSentence
		];
	}

	public function citizenQueryNightwatchInfo(Citizen $citizen): array {
		$this->ed->dispatch($event = $this->ef->gameEvent(CitizenQueryNightwatchInfoEvent::class, $citizen->getTown())->setup($citizen));
		return $event->nightwatchInfo;
	}

    public function queryTownParameter( Town $town, BuildingValueQuery $query, mixed $arg = null ): float|int {
        $this->ed->dispatch( $event = $this->ef->gameEvent( BuildingQueryTownParameterEvent::class, $town )->setup( $query, $arg ) );
        return $event->value;
    }

    public function queryTownRoleEnabled( Town $town, CitizenRole $role ): bool {
        $this->ed->dispatch( $event = $this->ef->gameEvent( BuildingQueryTownRoleEnabledEvent::class, $town )->setup( $role ) );
        return $event->enabled;
    }

    public function queryCatapultItemTransformation( Town $town, ItemPrototype $in ): ItemPrototype {
        $this->ed->dispatch( $event = $this->ef->gameEvent( BuildingCatapultItemTransformEvent::class, $town )->setup( $in ) );
        return $event->out ?? $in;
    }


    public function executeCustomAction( int $type, Citizen $citizen, ?Item $item, Citizen|Item|ItemPrototype|FriendshipActionTarget|null $target, ItemAction $action, ?string &$message, ?array &$remove, array &$execute_info_cache ): void {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CustomActionProcessorEvent::class, $citizen->getTown() )->setup( $type, $citizen, $item, $target, $action, $message, $remove, $execute_info_cache ) );
        $message = $event->message;
        $remove = $event->remove;
        $execute_info_cache = $event->execute_info_cache;
    }

    public function forumNewPostEvent( Post $post, HTMLParserInsight $insight, bool $new_thread = false ): void {
        $this->ed->dispatch( ($new_thread ? new ForumMessageNewThreadEvent() : new ForumMessageNewPostEvent())->setup( $post, $insight ) );
    }

    public function newAnnounceEvent( Announcement $announcement ): void {
        $this->ed->dispatch( (new NewAnnouncementEvent())->setup( $announcement ) );
    }

    public function newCommunityEventEvent( CommunityEvent $event ): void {
        $this->ed->dispatch( (new NewEventAnnouncementEvent())->setup( $event ) );
    }

    public function globalPrivateMessageNewPostEvent( GlobalPrivateMessage $post, HTMLParserInsight $insight, bool $new_thread = false ): void {
        $this->ed->dispatch( ($new_thread ? new GPMessageNewThreadEvent() : new GPMessageNewPostEvent())->setup( $post, $insight ) );
    }

    public function friendListUpdatedEvent( User $actor, User $subject, bool $added ): void {
        $this->ed->dispatch( (new FriendEvent())->setup( $added, $actor, $subject ) );
    }

    public function beforeTownJoinEvent( Town $town, User $subject, bool $auto ): BeforeJoinTownData {
        $this->ed->dispatch( $event = $this->ef->gameEvent( BeforeJoinTownEvent::class, $town )->setup( $subject, $auto ) );
        return $event->data;
    }

    public function townJoinEvent( Town $town, User $subject, bool $auto ): Citizen {
        $this->ed->dispatch( $event = $this->ef->gameEvent( JoinTownEvent::class, $town )->setup( $subject, $auto ) );
        return $event->citizen;
    }

    public function afterTownJoinEvent( Town $town, BeforeJoinTownData $data ): void {
        $this->ed->dispatch( $event = $this->ef->gameEvent( AfterJoinTownEvent::class, $town )->setup( $data ) );
    }

    public function pictosPersisted( User $user, ?Season $season = null, ?bool $old = null, ?bool $imported = null ): void {
        $this->ed->dispatch( (new PictoPersistedEvent())->setup( $user, $season, $old, $imported ) );
    }

    /**
     * @param Citizen $actor
     * @param Item $item
     * @param Inventory|null $from
     * @param Inventory|null $to
     * @param TransferItemModality $modality
     * @param TransferItemOption[] $options
     * @return int
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function transferItem( Citizen $actor, Item $item, ?Inventory $from = null, ?Inventory $to = null, TransferItemModality $modality = TransferItemModality::None, array $options = [] ): int {
        $this->ed->dispatch( $event = $this->ef->gameInteractionEvent( TransferItemEvent::class, $actor )->setup( $item, $actor, $from, $to, $modality, $options ) );
        if (!$event->isPropagationStopped()) $this->ed->dispatch( $event, GameInteractionEvent::class );
        return $event->getErrorCode() ?? InventoryHandler::ErrorNone;
    }

    /**
     * @param Citizen $actor
     * @param Item $item
     * @param Inventory[] $inventories
     * @param bool $force
     * @param bool $silent
     * @return Inventory|null
     */
    public function placeItem( Citizen $actor, Item $item, array $inventories, bool $force = false, bool $silent = false ): ?Inventory {
        foreach ($inventories as $inventory)
            if ($inventory && $this->transferItem( $actor, $item, to: $inventory, options: $silent ? [TransferItemOption::Silent] : [] ) === InventoryHandler::ErrorNone)
                return $inventory;
        if ($force) foreach (array_reverse($inventories) as $inventory)
            if ($inventory && $this->transferItem( $actor, $item, to: $inventory, options: $silent ? [TransferItemOption::EnforcePlacement, TransferItemOption::Silent] : [TransferItemOption::EnforcePlacement] ) === InventoryHandler::ErrorNone)
                return $inventory;
        return null;
    }

    public function contentReport(
        User $reporter,
        AdminReport $report,
        Post|GlobalPrivateMessage|PrivateMessage|BlackboardEdit|CitizenRankingProxy|User $subject,
        int $count = 1
    ): void {
        $this->ed->dispatch( match (ClassUtils::getRealClass(get_class($subject))) {
            Post::class => (new PostContentReportEvent())->setup( $reporter, $report, $subject, $count ),
            GlobalPrivateMessage::class => (new GlobalPrivateMessageContentReportEvent())->setup( $reporter, $report, $subject, $count ),
            PrivateMessage::class => (new PrivateMessageContentReportEvent())->setup( $reporter, $report, $subject, $count ),
            BlackboardEdit::class => (new BlackboardEditContentReportEvent())->setup( $reporter, $report, $subject, $count ),
            CitizenRankingProxy::class => (new CitizenRankingProxyContentReportEvent())->setup( $reporter, $report, $subject, $count ),
            User::class => (new UserContentReportEvent())->setup( $reporter, $report, $subject, $count ),
        });
    }
}