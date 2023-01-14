<?php

namespace App\Service;

use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Town;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use Imagick;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaService {

    const ErrorNone = 0;
    const ErrorBackendMissing = 1;
    const ErrorInputBroken = 2;
    const ErrorInputUnsupported = 3;
    const ErrorDimensionMismatch = 4;
    const ErrorProcessingFailed = 5;


    public function __construct()
    {

    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    protected function cloneImageFormat( Imagick $im_image, ?string $format = null ): ?Imagick {
        $clone = clone $im_image;
        if ($format !== null)
            try {
                if (!$clone->setFormat( $format ))
                    throw new Exception();
            } catch (\Throwable) {
                return null;
            }

        return $clone;
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    protected function getSmallerImage( ?Imagick $a, ?Imagick $b ): Imagick {
        try {
            $blob_a = $a?->getImageBlob();
        } catch (\Throwable) {
            $blob_a = null;
        }

        try {
            $blob_b = $b?->getImageBlob();
        } catch (\Throwable) {
            $blob_b = null;
        }

        if ($blob_a === null) return $b;
        elseif ($blob_b === null) return $a;

        return strlen( $blob_a ) < strlen( $blob_b ) ? $a : $b;
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    protected function compressImage(Imagick $im_image, bool $allow_format_change = true, bool $gif_deconstruct = true): ?Imagick {
        try {
            $format = $im_image->getImageFormat();
        } catch (\Throwable) {
            return null;
        }

        if ($format !== 'WEBP' && $allow_format_change)
            $im_image_webp = $this->compressImage( $this->cloneImageFormat( $im_image, 'WEBP' ), false, $gif_deconstruct );
        else $im_image_webp = null;

        try {
            switch ($format) {
                case 'WEBP':
                    $im_image->setImageCompressionQuality(90);
                    $im_image->setOption('webp:method', '6');
                    return $im_image;
                case 'JPEG':
                    $im_image->setImageCompressionQuality(90);
                    return $this->getSmallerImage( $im_image, $im_image_webp );
                case 'PNG':
                    $im_image->setOption('png:compression-level', 9);
                    return $this->getSmallerImage( $im_image, $im_image_webp );
                case 'GIF':
                    if ($gif_deconstruct)
                        $im_image = $im_image->deconstructImages();
                    $im_image->setOption('optimize', true);
                    return $this->getSmallerImage( $im_image, $im_image_webp );
                default:
                    return $im_image;
            }
        } catch (\Throwable) {
            return null;
        }
    }

    public function resizeImageSimple(&$data, int $width, int $height, ?string &$format = null, bool $compress = true): int {
        return $this->resizeImage($data,
            function(int &$w, int &$h, bool &$fit) use ($width,$height) { $w = $width; $h = $height; $fit = true; return true; },
            $w,$h, $format, $compress, false
        );
    }

    public function updateImageFormat( &$data, &$format, $force = false ): bool {
        if (!extension_loaded('imagick')) return false;

        try {
            if (!is_a($data, Imagick::class)) {
                $im_image = new Imagick();
                if (!$im_image->readImageBlob($data))
                    return false;
            } else $im_image = $data;

            $format = strtolower($im_image->getImageFormat());

            if ($format === 'webp') return false;

            $webp_image = $this->compressImage( $this->cloneImageFormat( $im_image, 'WEBP' ), false, true );
            $smaller_image = $force ? $webp_image : $this->getSmallerImage( $im_image, $webp_image );

            if ($smaller_image === $im_image) return false;

            $data = !is_a($data, Imagick::class) ? $smaller_image->getImagesBlob() : $smaller_image;
            $format = strtolower($smaller_image->getImageFormat());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function resizeImage( &$data, callable $determine_dimensions, ?int &$width = null, ?int &$height = null, ?string &$format = null, bool $compress = true, bool $change_format = true): int
    {
        if (!extension_loaded('imagick')) return self::ErrorBackendMissing;

        try {
            if (!is_a($data, Imagick::class)) {
                $im_image = new Imagick();
                if (!$im_image->readImageBlob($data))
                    return self::ErrorInputBroken;
            } else $im_image = $data;

            if (!in_array($im_image->getImageFormat(), ['GIF', 'JPEG', 'BMP', 'PNG', 'WEBP']))
                return self::ErrorInputUnsupported;

            if ($change_format && $im_image->getImageFormat() === 'GIF' && $im_image->getNumberImages() > 1)
                $im_image->setFormat("WEBP");

            $im_image->resetImagePage('0x0');
            $im_image->setFirstIterator();

            // RGB is not widely supported in GIF images; when the image may claim it is RGB this is most likely an
            // error, so we disregard the RGB definition and overwrite it as sRGB
            if ($im_image->getImageFormat() === 'GIF' && $im_image->getImageColorspace() === Imagick::COLORSPACE_RGB)
                foreach ($im_image as $frame)
                    $frame->setImageColorspace(Imagick::COLORSPACE_SRGB);

            $width = $w = $im_image->getImageWidth();
            $height = $h = $im_image->getImageHeight();
            $fit = true;

            if (!$determine_dimensions($width, $height, $fit, $im_image->getNumberImages())) return self::ErrorDimensionMismatch;

            $resized = false;
            if ($width !== $w || $height !== $h) {

                if ($im_image->getNumberImages() > 256)
                    return self::ErrorProcessingFailed;

                $im_image = $im_image->coalesceImages();
                $resized = true;

                foreach ($im_image as $frame) {
                    if (!$frame->resizeImage($width, $height, imagick::FILTER_SINC, 1, $fit))
                        return self::ErrorProcessingFailed;
                }
            }

            $im_image->setFirstIterator();

            $width = $im_image->getImageWidth();
            $height = $im_image->getImageHeight();

            if ($compress) {
                $im_image = $this->compressImage($im_image, $change_format, $resized);
                if ($im_image === null) return self::ErrorProcessingFailed;
            }

            $data = !is_a($data, Imagick::class) ? $im_image->getImagesBlob() : $im_image;
            $format = strtolower($im_image->getImageFormat());
        } catch (Exception $e) {
            //throw $e;
            return self::ErrorProcessingFailed;
        }

        return self::ErrorNone;
    }

    public function cropImage( &$data, $dx, $dy, $x, $y, callable $determine_dimensions, ?int &$width = null, ?int &$height = null, ?string &$format = null, bool $compress = true): int {

        if (!extension_loaded('imagick')) return self::ErrorBackendMissing;

        try {
            if (!is_a($data, Imagick::class)) {
                $im_image = new Imagick();
                if (!$im_image->readImageBlob($data))
                    return self::ErrorInputBroken;
            } else $im_image = $data;

            $im_image->setFirstIterator();
            $im_image = $im_image->coalesceImages();

            foreach ($im_image as $frame) {
                if (!$frame->cropImage( $dx, $dy, $x, $y ))
                    return self::ErrorProcessingFailed;
                $frame->setImagePage($dx,$dy,0,0);
            }
            $im_image->resetImagePage('0x0');
            $im_image->setFirstIterator();

            if (($e = $this->resizeImage($im_image, $determine_dimensions, $width, $height, $format, $compress, false)) !== self::ErrorNone)
                return $e;

            $data = !is_a($data, Imagick::class) ? $im_image->getImagesBlob() : $im_image;
        } catch (Exception $e) {
            return self::ErrorProcessingFailed;
        }

        return self::ErrorNone;
    }
}