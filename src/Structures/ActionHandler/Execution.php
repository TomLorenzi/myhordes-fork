<?php


namespace App\Structures\ActionHandler;


use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\RuinZone;
use App\Entity\Zone;
use App\Enum\ActionHandler\CountType;
use App\Enum\ActionHandler\PointType;
use App\Service\Actions\Game\WrapObjectsForOutputAction;
use App\Structures\FriendshipActionTarget;
use Symfony\Contracts\Translation\TranslatorInterface;

class Execution extends Base
{

    private array $points = [];
    private array $counter = [];

    private array $spawned_items = [];
    private array $consumed_items = [];
    private array $morphed_items = [];
    private array $target_morphed_items = [];
    private array $used_tool_items = [];

    private ?ItemAction $action = null;
    private ?Citizen $target_citizen = null;
    private ?Zone $target_zone = null;
    private array $discovered_plans = [];
    private ?RuinZone $target_ruin_zone = null;

    private bool $escort_mode = false;

    public function addPoints(PointType $type, int $value): void {
        $this->points[$type->value] = $this->getPoints($type) + $value;
    }

    public function getPoints(PointType $type): int {
        return $this->points[$type->value] ?? 0;
    }

    public function addToCounter(CountType $type, int $value): void {
        $this->counter[$type->value] = $this->getCounter($type) + $value;
    }

    public function getCounter(CountType $type): int {
        return $this->counter[$type->value] ?? 0;
    }

    public function addSpawnedItem(Item|ItemPrototype $item): void {
        $this->spawned_items[] = is_a($item, Item::class) ? $item->getPrototype() : $item;
    }

    public function addConsumedItem(Item|ItemPrototype $item): void {
        $this->consumed_items[] = is_a($item, Item::class) ? $item->getPrototype() : $item;
    }

    public function addToolItem(Item|ItemPrototype $item): void {
        $this->used_tool_items[] = is_a($item, Item::class) ? $item->getPrototype() : $item;
    }

    public function setItemMorph(ItemPrototype $from, ItemPrototype $to, bool $forTargetItem = false): void {
        if (!$forTargetItem) {
            $this->morphed_items[] = [$from, $to];
            // $this->addConsumedItem( $from );
            // $this->addSpawnedItem( $to );
        } else $this->target_morphed_items = [$from, $to];
    }

    public function setTargetCitizen( Citizen|FriendshipActionTarget $citizen): void {
        $this->target_citizen = is_a($citizen, Citizen::class) ? $citizen : $citizen->citizen();
    }

    public function setTargetZone( Zone $zone ): void {
        $this->target_zone = $zone;
    }

    public function setTargetRuinZone( RuinZone $zone ): void {
        $this->target_ruin_zone = $zone;
    }

    public function getTargetRuinZone( ): ?RuinZone {
        return $this->target_ruin_zone;
    }

    public function setAction(ItemAction $action): void
    {
        $this->action = $action;
    }

    public function getAction(): ?ItemAction {
        return $this->action;
    }

    public function addDiscoveredBlueprint( BuildingPrototype $building ): void {
        $this->discovered_plans[] = $building;
    }

    public function setEscortMode(bool $escort): void {
        $this->escort_mode = $escort;
    }

    public function getEscortMode(): bool {
        return $this->escort_mode;
    }

    public function calculateTags(): array {
        $tags = parent::calculateTags();
        foreach (PointType::cases() as $type)
            if ($this->getPoints($type) > 0) $tags[] = "{$type->letterCode()}-up";
            elseif ($this->getPoints($type) < 0) $tags[] = "{$type->letterCode()}-down";

        if ($this->getCounter(CountType::Kills) > 0)
            $tags[] = 'kills';

        if (count($this->spawned_items) >= 1)
            $tags[] = 'spawned';

        if (!empty($this->morphed_items))
            $tags[] = 'morphed';

        $tags[] = $this->citizen->getZone() ? 'outside' : 'inside';

        $tags[] = empty($this->discovered_plans) ? 'bp_fail' : 'bp_ok';
        if (!empty( array_filter( $this->discovered_plans, fn(BuildingPrototype $b) => !!$b->getParent() ) ))
            $tags[] = 'bp_parent';

        return $tags;
    }

    /**
     * @param BuildingPrototype $prototype
     * @return BuildingPrototype[]
     */
    private function listBuildingParents(BuildingPrototype $prototype): array {
        $cache = [];
        while ($prototype = $prototype->getParent())
            $cache[] = $prototype;
        return array_reverse($cache);
    }

    protected function getOwnKeys(TranslatorInterface $trans, WrapObjectsForOutputAction $wrapper): array {
        $dynamic = [];
        foreach (PointType::cases() as $type) {
            $dynamic["{{$type->letterCode()}}"] = $this->getPoints( $type );
            $dynamic["{minus_{$type->letterCode()}}"] = -$this->getPoints( $type );
        }
        foreach (CountType::cases() as $type)
            $dynamic["{{$type->variable()}}"] = $this->getCounter( $type );

        foreach ($this->consumed_items as $key => $data)
            $dynamic["{items_consume_$key}"] = $wrapper($data);

        $zone = $this->target_zone ?? $this->citizen?->getZone() ?? null;

        return [
            ...$dynamic,
            '{user}'          => $wrapper($this->citizen),
            '{citizen}'       => $wrapper($this->target_citizen),
            '{target}'        => $wrapper($this->originalTargetPrototype),
            '{item_initial}'  => $wrapper($this->originalPrototype),
            '{item_from}'     => $wrapper($this->morphed_items[0][0] ?? $this->consumed_items[0] ?? null),
            '{item_to}'       => $wrapper($this->morphed_items[0][1] ?? $this->spawned_items[0] ?? null),
            '{item_tool}'     => $wrapper($this->used_tool_items),
            '{items_spawn}'   => $wrapper($this->spawned_items, accumulate: true),
            '{items_consume}' => $wrapper($this->consumed_items, accumulate: true),
            '{target_from}'   => $wrapper($this->target_morphed_items[0][0] ?? null),
            '{target_to}'     => $wrapper($this->target_morphed_items[0][1] ?? null),
            '{zone}'          => $wrapper( $zone ? "{$zone->getX()} / {$zone->getY()}" : null ),
            '{zone_ruin}'     => $wrapper($zone),
            '{bp_spawn}'      => $wrapper($this->discovered_plans),
            '{bp_parent}'     => implode(', ',
                array_map(fn(array $a) => $wrapper($a, concatUsing: ' > '), array_filter(
                        array_map( fn(BuildingPrototype $b) => $this->listBuildingParents($b), $this->discovered_plans ),
                        fn(array $a) => !empty($a)
                    )
                )
            ),
            ...parent::getOwnKeys($trans, $wrapper),
        ];
    }

}