<?php


namespace App\Structures\ActionHandler;


use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function addMessage(string $message, array $variables = [], string $translationDomain = null): void {
        $this->messages[] = [$message, $variables, $translationDomain];
    }

    public function addTranslationKey(string $key, string $value): void {
        $this->trans[$key] = $value;
    }

    public function getMissingItems(): array {
        return $this->missing_items;
    }

    public function getMessages(TranslatorInterface $trans, array $keys = []): array {
        return array_map( function(array $m) use ($trans, $keys) {
            return $m[1] === null ? $m[0] : $trans->trans( $m[0], array_merge($m[1], $this->getTranslationKeys(), $keys), $m[2] );
        }, $this->messages );
    }

    public function getTranslationKeys(): array {
        return $this->trans;
    }
}