<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Service\Media;

use App\Structures\Image;
use Imagick;

class ImageService
{

    public static function createImageFromData(string $data): ?Image {
        if (!extension_loaded('imagick')) return null;

        $image = new Imagick();
        try {
            if (!$image->readImageBlob($data)) return null;

            // Coalesce all images if the format requires this
            $image = $image->coalesceImages();

            // Properly reset all internal pointers
            $image->resetImagePage('0x0');
            $image->setFirstIterator();

            // RGB is not widely supported in GIF images; when the image claims it is RGB this is most likely an
            // error, so we disregard the RGB definition and overwrite it as sRGB
            if ($image->getImageFormat() === 'GIF' && $image->getImageColorspace() === Imagick::COLORSPACE_RGB)
                foreach ($image as $frame)
                    $frame->setImageColorspace(Imagick::COLORSPACE_SRGB);

            return new Image(
                $image,
                format: $image->getImageFormat(),
                height: $image->getImageHeight(),
                width: $image->getImageWidth(),
                frames: $image->getNumberImages()
            );

        } catch (\Throwable) {
            return null;
        }
    }

    public static function convertImageData(string $data, string $format, ?float $quality = 0.9): ?string {

        if (!extension_loaded('imagick')) return null;

        try {
            return ($image = self::createImageFromData( $data )) ? self::save( $image, $format, $quality ) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getCompressionOptions(Image $image, string $preset = 'avif', bool $lossless = false): array {
        $converter_formats = [];
        if (!$lossless && in_array($image->format, ['WEBP','GIF','PNG','JPEG'])) $converter_formats[] = null;
        if ($lossless && in_array($image->format, ['WEBP','GIF','PNG'])) $converter_formats[] = null;
        if ($image->format !== 'AVIF' && !$image->animated && $preset === 'avif' ) $converter_formats[] = 'AVIF';
        if ($image->format !== 'WEBP' ) $converter_formats[] = 'WEBP';

        return $converter_formats;
    }

    public static function cloneImage(Image $image): ?Image {
        try {
            return new Image(
                clone $image->image,
                format: $image->format,
                height: $image->height,
                width: $image->width,
                frames: $image->frames
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public static function crop(Image $image, int $x, int $y, int $width, int $height): bool {
        try {
            foreach ($image->image as $frame) {
                if (!$frame->cropImage( $width, $height, $x, $y ))
                    return false;
                $frame->setImagePage( $width, $height,0,0 );
            }
            $image->image->resetImagePage('0x0');
            $image->image->setFirstIterator();

            $image->width = $width;
            $image->height = $height;

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function resize(Image $image, int $width, int $height, int $filter = imagick::FILTER_SINC, float $blur = 1, bool $bestFit = false): bool {
        try {
            foreach ($image->image as $frame)
                if (!$frame->resizeImage($width, $height, $filter, $blur, $bestFit))
                    return false;

            $image->image->resetImagePage('0x0');
            $image->image->setFirstIterator();

            $image->width = $image->image->getImageWidth();
            $image->height = $image->image->getImageHeight();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function save(Image $image, ?string $format = null, ?float $quality = 0.9, bool $live = false): ?string {
        try {
            $clone = $live ? clone $image->image : $image->image;
            if ($format !== null) $clone->setFormat( $format );

            if ($quality !== null)
                switch ($format ?? $image->format) {
                    case 'WEBP':
                        $clone->setImageCompressionQuality($quality * 100);
                        $clone->setOption('webp:method', '6');
                        break;
                    case 'PNG':
                        $clone->setOption('png:compression-filter', 5);
                        $clone->setOption('png:compression-level', 9);
                        $clone->setOption('png:compression-strategy', 1);
                        break;
                    case 'AVIF':
                        $clone->setOption('heic:speed', 0);
                        $clone->setImageDelay(10);
                        $clone->setOption('heic:chroma', "444");
                        break;
                    case 'GIF':
                        //$clone = $clone->deconstructImages();
                        $clone->setOption('optimize', true);
                        break;
                    default:
                        $clone->setImageCompressionQuality($quality * 100);
                        break;
                }

            if ($live) $image->format = $format ?? $image->format;

            return $clone->getImagesBlob();

        } catch (\Throwable) {
            return null;
        }
    }

}