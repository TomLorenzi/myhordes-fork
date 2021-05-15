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

            if ($im_image->getImageFormat() === 'GIF') {
                $im_image->coalesceImages();
                $im_image->resetImagePage('0x0');
                $im_image->setFirstIterator();
            }

            $width = $w = $im_image->getImageWidth();
            $height = $h = $im_image->getImageHeight();
            $fit = true;

            if (!$determine_dimensions($width, $height, $fit)) return self::ErrorDimensionMismatch;

            if ($width !== $w || $height !== $h)
                foreach ($im_image as $frame)
                    if (!$frame->resizeImage($width, $height, imagick::FILTER_SINC, 1, $fit))
                        return self:: ErrorProcessingFailed;

            if ($im_image->getImageFormat() === 'GIF')
                $im_image->setFirstIterator();

            $width = $im_image->getImageWidth();
            $height = $im_image->getImageHeight();

            if ($compress)
                switch ($im_image->getImageFormat()) {
                    case 'JPEG':
                        $im_image->setImageCompressionQuality(90);
                        break;
                    case 'PNG':
                        $im_image->setOption('png:compression-level', 9);
                        break;
                    case 'GIF':
                        $im_image->setOption('optimize', true);
                        break;
                    default:
                        break;
                }

            $data = !is_a($data, Imagick::class) ? $im_image->getImagesBlob() : $im_image;
            $format = strtolower($im_image->getImageFormat());
        } catch (Exception $e) {
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