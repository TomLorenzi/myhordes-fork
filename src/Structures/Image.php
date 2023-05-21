<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace App\Structures;


use App\Entity\Item;
use Imagick;

/**
 * @property-read float $aspect Smaller dimension (width or height) divided by the other. Always between 0 and 1.
 * @property-read float $ratio Width divided by height
 * @property-read bool $animated
 */
class Image
{
    public function __construct(
        public readonly Imagick $image,
        public ?string $format = null,
        public int $height = 0,
        public int $width = 0,
        public int $frames = 0,
    ) {}

    public function __get(string $name)
    {
        return match ($name) {
            'aspect' => ($this->width > 0 && $this->height > 0) ? min($this->width, $this->height) / max($this->width, $this->height) : 0,
            'ratio' => ($this->width > 0 && $this->height > 0) ? $this->width / $this->height : 0,
            'animated' => $this->frames > 1
        };
    }

}