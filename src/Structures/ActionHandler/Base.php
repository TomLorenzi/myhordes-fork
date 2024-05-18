<?php

namespace App\Structures\ActionHandler;

use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Service\Actions\Game\WrapObjectsForOutputAction;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Base
{
    public readonly ItemPrototype|null $originalPrototype;
    public readonly Inventory|null $originalInventory;
    private array $messages = [];
    private array $trans = [];
    private array $metaTrans = [];

    private array $trans_wrapped = [];

    private array $flags = [];

    private array $tags = [];

    public function __construct(
        public readonly EntityManagerInterface $em,
        public readonly Citizen $citizen,
        public readonly ?Item $item,
        public readonly TownConf $conf,
        public readonly MyHordesConf $sysConf
    ) {
        $this->originalPrototype = $item?->getPrototype();
        $this->originalInventory = $item?->getInventory();
    }

    public function addMessage(string $message, array $variables = [], string $translationDomain = null): void {
        $this->messages[] = [$message, $variables, $translationDomain];
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

    public function getMessages(TranslatorInterface $trans, WrapObjectsForOutputAction $wrapObjectsForOutputAction, array $keys = []): array {
        $composite_keys = [
            ...$this->getOwnKeys($trans, $wrapObjectsForOutputAction),
            ...$keys
        ];

        $messages = [];
        $tags = $this->calculateTags();

        foreach (array_map(
                     fn(array $m) => $trans->trans( $m[0], [...$m[1], ...$composite_keys], $m[2] ?? 'game' ),
                     $this->messages
                 ) as $contentMessage) {
            do {
                $contentMessage = preg_replace_callback( '/<t-(.*?)>(.*?)<\/t-\1>/' , function(array $m) use ($tags): string {
                    [, $tag, $text] = $m;
                    return in_array( $tag, $tags ) ? $text : '';
                }, $contentMessage, -1, $c);
                $contentMessage = preg_replace_callback( '/<nt-(.*?)>(.*?)<\/nt-\1>/' , function(array $m) use ($tags): string {
                    [, $tag, $text] = $m;
                    return !in_array( $tag, $tags ) ? $text : '';
                }, $contentMessage, -1, $d);
            } while ($c > 0 || $d > 0);
            $messages[] = $contentMessage;
        }

        return array_filter($messages);
    }
}