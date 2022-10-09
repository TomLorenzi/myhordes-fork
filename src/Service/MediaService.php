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

    public function resizeImageSimple(&$data, int $width, int $height, ?string &$format = null, bool $compress = true): int {
        return $this->resizeImage($data,
            function(int &$w, int &$h, bool &$fit) use ($width,$height) { $w = $width; $h = $height; $fit = true; return true; },
            $w,$h, $format, $compress
        );
    }

    public function resizeImage( &$data, callable $determine_dimensions, ?int &$width = null, ?int &$height = null, ?string &$format = null, bool $compress = true): int
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

            if ($compress)
                switch ($im_image->getImageFormat()) {
                    case 'WEBP':
                        $im_image->setImageCompressionQuality(90);
                        $im_image->setOption('webp:method', '6');
                        break;
                    case 'JPEG':
                        $im_image->setImageCompressionQuality(90);
                        break;
                    case 'PNG':
                        $im_image->setOption('png:compression-level', 9);
                        break;
                    case 'GIF':
                        if ($resized)
                            $im_image = $im_image->deconstructImages();
                        $im_image->setOption('optimize', true);
                        break;
                    default:
                        break;
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

            foreach ($im_image as $frame) {
                if (!$frame->cropImage( $dx, $dy, $x, $y ))
                    return self::ErrorProcessingFailed;
                $frame->setImagePage($dx,$dy,0,0);
            }

            if (($e = $this->resizeImage($im_image, $determine_dimensions, $width, $height, $format, $compress)) !== self::ErrorNone)
                return $e;

            $data = !is_a($data, Imagick::class) ? $im_image->getImagesBlob() : $im_image;
        } catch (Exception $e) {
            return self::ErrorProcessingFailed;
        }

        return self::ErrorNone;
    }
}