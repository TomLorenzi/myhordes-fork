<?php

namespace App\Structures\ActionHandler;

use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Service\Actions\Game\DecodeConditionalMessageAction;
use App\Service\Actions\Game\WrapObjectsForOutputAction;
use App\Structures\FriendshipActionTarget;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Base
{
    public readonly ItemPrototype|null $originalPrototype;
    public readonly Inventory|null $originalInventory;
    public readonly ItemPrototype|null $originalTargetPrototype;
    public readonly Inventory|null $originalTargetInventory;
    private array $messages = [];
    private array $trans = [];
    private array $metaTrans = [];

    private array $trans_wrapped = [];

    private array $flags = [];

    private array $tags = [];

    private ?int $error = null;

    public function __construct(
        public readonly EntityManagerInterface $em,
        public readonly Citizen $citizen,
        public readonly Item|null $item,
        public readonly Item|ItemPrototype|Citizen|FriendshipActionTarget|null $target,
        public readonly TownConf $conf,
        public readonly MyHordesConf $sysConf
    ) {
        $this->originalPrototype = $item?->getPrototype();
        $this->originalInventory = $item?->getInventory();

        if (is_a($target, Item::class)) {
            $this->originalTargetPrototype = $target->getPrototype();
            $this->originalTargetInventory = $target->getInventory();
        } elseif (is_a($target, ItemPrototype::class)) {
            $this->originalTargetPrototype = $target;
            $this->originalTargetInventory = null;
        } else {
            $this->originalTargetPrototype = null;
            $this->originalTargetInventory = null;
        }

    }

    public function addMessage(string $message, array $variables = [], string $translationDomain = null, int $order = 0): void {
        $this->messages[] = [$message, $variables, $translationDomain,$order,count($this->messages)];
    }

    public function addTranslationKey(string $key, string $value, bool $wrap = false): void {
        if ($wrap) $this->trans_wrapped[$key] = $value;
        else $this->trans[$key] = $value;
    }

    public function addMetaTranslationKey(string $key, string $value, string $domain): void {
        $this->metaTrans[$key] = [$value, $domain];
    }

    public function hasMessages(): bool {
        return !empty($this->messages);
    }

    public function clearMessages(): void {
        $this->messages = [];
    }

    public function addFlag(string $flag): void {
        $this->flags[] = $flag;
    }

    public function isFlagged(string $flag): bool {
        return in_array( $flag, $this->flags );
    }

    public function addTag(string $tag): void {
        $this->tags[] = $tag;
    }

    public function registerError(int $error): void {
        $this->error = $error;
    }

    public function getRegisteredError(): ?int {
        return $this->error;
    }

    public function calculateTags(): array {
        return $this->tags;
    }

    protected function getOwnKeys(TranslatorInterface $trans, WrapObjectsForOutputAction $wrapper): array {
        return [
            ...$this->trans,
            ...array_map( fn(string $s) => $wrapper($s), $this->trans_wrapped ),
            ...array_map( fn(array $obj) => $trans->trans( $obj[0], [], $obj[1] ), $this->metaTrans ),
            '{hr}'            => "<hr />",
            '{item}'          => $wrapper($this->item),
            '{km_from_town}'  => $this->citizen?->getZone()?->getDistance() ?? 0,
        ];
    }

    public function getMessages(TranslatorInterface $trans, WrapObjectsForOutputAction $wrapObjectsForOutputAction, DecodeConditionalMessageAction $decoder, array $keys = []): array {
        $composite_keys = [
            ...$this->getOwnKeys($trans, $wrapObjectsForOutputAction),
            ...$keys
        ];

        $messages = [];
        $tags = $this->calculateTags();

        $ordered = $this->messages;
        usort($ordered, fn($m1, $m2) => $m1[3] <=> $m2[3] ?: $m1[4] <=> $m2[4]);

        foreach (array_map(
                     fn(array $m) => $trans->trans( $m[0], [...$m[1], ...$composite_keys], $m[2] ?? 'game' ),
                     $ordered
                 ) as $contentMessage) {
            $messages[] = ($decoder)($contentMessage, $tags, $this->citizen->getProperties());
        }

        return array_filter($messages);
    }
}