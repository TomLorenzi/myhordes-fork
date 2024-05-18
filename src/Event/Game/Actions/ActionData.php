<?php

namespace App\Event\Game\Actions;

use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Structures\ActionHandler\Execution;
use App\Structures\FriendshipActionTarget;

class ActionData
{

    public readonly int $type;
    public readonly Citizen $citizen;
    public readonly Item|null $item;
    public readonly Citizen|Item|ItemPrototype|FriendshipActionTarget|null $target;
    public readonly ItemAction $action;

    public ?string $message;
    public array $remove;

    public Execution $cache;

	/**
     * @param int $type
     * @param Citizen $citizen
     * @param Item|null $item
     * @param Citizen|Item|ItemPrototype|FriendshipActionTarget|null $target
     * @param ItemAction $action
     * @param string|null $message
     * @param array|null $remove
     * @param Execution $cache
     * @return ActionData
     * @noinspection PhpDocSignatureInspection
     */
	public function setup( int $type, Citizen $citizen, ?Item $item, Citizen|Item|ItemPrototype|FriendshipActionTarget|null $target, ItemAction $action, ?string $message, ?array $remove, Execution $cache ): void {
        $this->type = $type;
        $this->citizen = $citizen;
        $this->item = $item;
        $this->target = $target;
        $this->action = $action;
        $this->message = $message;
        $this->remove = $remove ?? [];
        $this->cache = $cache;
	}

}