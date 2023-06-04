<?php


namespace App\Structures\ActionHandler;


use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;

class Evaluation
{
    private array $missing_items = [];
    private array $messages = [];
    private array $trans = [];

    public function __construct(
        public readonly Citizen $citizen,
        public readonly ?Item $item,
        public readonly TownConf $conf,
        public readonly MyHordesConf $sysConf
    ) { }

    public function addMissingItem(ItemPrototype $prototype): void {
        $this->missing_items[] = $prototype;
    }

    public function addMessage(string $message): void {
        $this->messages[] = $message;
    }

    public function addTranslationKey(string $key, string $value): void {
        $this->trans[$key] = $value;
    }

    public function getMissingItems(): array {
        return $this->missing_items;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function getTranslationKeys(): array {
        return $this->trans;
    }
}