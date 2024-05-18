<?php

namespace App\Service\Actions\Game;

use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Clue\StreamFilter\fun;

readonly class WrapObjectsForOutputAction
{
    public function __construct(
        private TranslatorInterface  $translator,
        private Packages $assets,
    ) { }

    private function normalize(
        Item|ItemPrototype|
        Building|BuildingPrototype|
        User|Citizen|
        Zone|ZonePrototype|
        string|null $object
    ): ItemPrototype|BuildingPrototype|ZonePrototype|Citizen|string|null {
        return match (true) {
            is_a($object, Item::class) || is_a($object, Building::class) || is_a($object, Zone::class) => $object->getPrototype(),
            is_a($object, User::class) => $object->getActiveCitizen(),
            default => $object
        };
    }

    private function renderObject(ItemPrototype|BuildingPrototype|ZonePrototype|Citizen|string $o): string {
        [$text, $icon] = match(true) {
            is_a($o, ItemPrototype::class) => [
                $this->translator->trans($o->getLabel(), [], 'items'),
                "build/images/item/item_{$o->getIcon()}.gif"
            ],
            is_a($o, BuildingPrototype::class) => [
                $this->translator->trans($o->getLabel(), [], 'buildings'),
                "build/images/building/{$o->getIcon()}.gif"
            ],
            is_a($o, ZonePrototype::class) => [
                $this->translator->trans($o->getLabel(), [], 'game'),
                null
            ],
            is_a($o, Citizen::class) => [
                $o->getName(),
                "build/images/professions/{$o->getProfession()->getIcon()}.gif"
            ],
            default => [
                $o,
                null
            ]
        };

        return $icon
            ? "<img alt=\"\" src=\"{$this->assets->getUrl( $icon )}\" />$text"
            : $text;
    }

    public function __invoke(
        Item|ItemPrototype|
        Building|BuildingPrototype|
        User|Citizen|
        Zone|ZonePrototype|
        array|string|null $object,
        int $count = 1,
        string $concatUsing = ', ',
        bool $accumulate = false
    ): string
    {
        if ($object === null) return '';
        if (!is_array($object)) $object = array_fill(0, $count, $object);
        if (empty($object)) return '';

        if ($accumulate) {
            $segments = [];
            foreach ( $object as $entry ) {
                $key = match (true) {
                    is_object($entry) => $entry->getId(),
                    is_string($entry) => $entry,
                    default => null,
                };
                if ($key === null) continue;

                if (!isset($segments[$key])) $segments[$key] = [1,$entry];
                else $segments[$key][0]++;
            }
        } else $segments = array_map( fn($o) => [1,$o], $object );

        return implode( $concatUsing, array_filter( array_map( function ($entry) {
            [$this_count, $this_object] = $entry;
            $this_object = $this->normalize( $this_object );
            if ($this_object === null || $this_count <= 0) return null;
            return $this_count > 1
                ? ("<span>{$this_count} × {$this->renderObject( $this_object )}</span>")
                : "<span>{$this->renderObject( $this_object )}</span>";

        }, $segments ), fn($o) => $o !== null ));
    }
}