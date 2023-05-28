<?php

namespace MyHordes\Fixtures\DTO\Items;

use MyHordes\Fixtures\DTO\Element;

/**
 * @property string label
 * @method self label(string $v)
 * @property string icon
 * @method self icon(string $v)
 * @property string description
 * @method self description(string $v)
 * @property string category
 * @method self category(string $v)
 * @property int deco
 * @method self deco(int $v)
 * @property bool heavy
 * @method self heavy(bool $v)
 * @property int watchpoint
 * @method self watchpoint(int $v)
 * @property bool fragile
 * @method self fragile(bool $v)
 * @property string deco_text
 * @method self deco_text(string $v)
 * @property int sort
 * @method self sort(int $v)
 * @property bool hideInForeignChest
 * @method self hideInForeignChest(bool $v)
 * @property bool unstackable
 * @method self unstackable(bool $v)
 *
 * @method ItemPrototypeDataContainer commit()
 * @method ItemPrototypeDataContainer discard()
 */
class ItemPrototypeDataElement extends Element {}