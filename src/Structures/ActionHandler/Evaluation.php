<?php


namespace App\Structures\ActionHandler;


use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Service\Actions\Game\WrapObjectsForOutputAction;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Evaluation extends Base
{
    private array $missing_items = [];
    private array $processed_items = [];

    public function addMissingItem(ItemPrototype $prototype): void {
        $this->missing_items[] = $prototype;
    }

    public function addProcessedItem(string $key, ItemPrototype $prototype): void {
        if (!isset( $this->processed_items[$key] )) $this->processed_items[$key] = [];
        $this->processed_items[$key][] = $prototype;
    }

    protected function getOwnKeys(TranslatorInterface $trans, WrapObjectsForOutputAction $wrapper): array {
        return [
            '{items_required}' => $wrapper($this->getMissingItems(), accumulate: true),
            ...parent::getOwnKeys( $trans, $wrapper )
        ];
    }

    /**
     * @return ItemPrototype[]
     */
    public function getMissingItems(): array {
        return $this->missing_items;
    }

    /**
     * @return ItemPrototype[]
     */
    public function getProcessedItems(string $key): array {
        return $this->processed_items[$key] ?? [];
    }
}