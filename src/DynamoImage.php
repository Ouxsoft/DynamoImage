<?php
/**
 * This file is part of the Hoopless package.
 *
 * (c) Ouxsoft <contact@Ouxsoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Model\DynamoImagePath;
use GdImage;
use Exception;
use Laminas\Validator\File\Exists;

/**
 * Class DynamoImage
 * checks if the requested image exists in cache using a hash
 * if it is not cached, it checks to see if the image exists as an assets
 * if the image exists, then it generates a resized image, stores it in cache.
 * does not handle serving image.
 */
class DynamoImage
{
    const JPEG = 1;
    const JPG = 1;
    const PNG = 2;
    const GIF = 3;
    const SUPPORTED_FILETYPES = [
        self::JPEG,
        self::JPG,
        self::PNG,
        self::GIF
    ];

    /** @var GdImage|false */
    private $image;

    private $cache_dir;
    private $assets_dir;
    private $filename;
    private $file_extension;
    private $width;
    private $height;
    private $focal_point_x;
    private $focal_point_y;

    private $cache_filename;
    private $cache_filepath;

    private $image_original;
    private $height_original;
    private $width_original;
    private $ratio_original;


    /**
     * @return string
     * @throws Exception
     */
    public function getContentType() : string
    {
        switch ($this->getFileExtension()) {
            case 'gif':
                return 'image/gif';
            case 'png':
                return 'image/png';
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            default:
                throw new Exception('Unsupported file extension');
        }
    }

    /**
     * @return int
     */
    public function getFileSize() : int
    {
        return filesize($this->getCacheFilepath());
    }

    /**
     * @return GdImage
     * @throws Exception
     */
    public function getContent() : GdImage
    {
        switch ($this->getFileExtension())
        {
            case 'gif':
                return imagegif($this->image, null);
            case 'png':
                return imagepng($this->image, null, 9);
            case 'jpeg':
            case 'jpg':
                return imagejpeg($this->image, null, 100);
            default:
                throw new Exception('Unsupported file extension');
        }
    }

    /**
     * @param string $cache_dir
     */
    public function setCacheDir(string $cache_dir) : void
    {
        $this->cache_dir = $cache_dir;
    }

    /**
     * @param string $assets_dir
     */
    public function setAssetDir(string $assets_dir) : void
    {
        $this->assets_dir = $assets_dir;
    }

    /**
     * set URL thereby setting multiple params
     * @param string|null $request
     */
    public function setURL(string $request)
    {
        $parameters = DynamoImagePath::decodeParams("/{$request}", ['height','width','dimension','offset'], 'filename');

        if(
            isset($parameters['dimension'])
            && preg_match('/([0-9]+x[0-9]+)/', $parameters['dimension'])
        ) {
            list($parameters['width'], $parameters['height'] ) = explode('x', $parameters['dimension']);
        }

        if(
            isset($parameters['offset'])
            && (strpos($parameters['offset'], ',') !== false)
        ) {
            list($parameters['offset_x'], $parameters['offset_y']) = explode(',', $parameters['offset']);
        }

        // set filename
        if (array_key_exists('filename', $parameters)) {
            $filename = trim($parameters['filename'], '/');
            $this->setFilename($filename);
        }

        // set height
        if (array_key_exists('height', $parameters)) {
            $this->setHeight($parameters['height']);
        }

        // set width
        if (array_key_exists('width', $parameters)) {
            $this->setWidth($parameters['width']);
        }

        // set offset x
        if (array_key_exists('offset_x', $parameters)) {
            $this->setFocalPointX($parameters['offset_x']);
        }

        // set offset y
        if (array_key_exists('offset_y', $parameters)) {
            $this->setFocalPointY($parameters['offset_y']);
        }

        // set cache url
        $this->setCacheURL($request);
    }

    /**
     * Set cache URL using a the filepath and a hash of file name
     * @param string|null $relative_path
     * @return void
     */
    public function setCacheURL(string $relative_path): void
    {
        $this->cache_filename = hash('sha256', $relative_path);
        $this->cache_filepath = $this->cache_dir . $this->cache_filename;
    }

    /**
     * picks the file in assets to use
     * @param string $filename
     * @return void
     */
    public function setFilename(string $filename) : void
    {
        // set filename
        $this->filename = $filename;

        // set file_extension
        $this->file_extension = strtolower(substr(strrchr($filename, '.'), 1));
    }

    /**
     * Sets height
     * @param $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Sets width
     * @param $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Sets Focal Point X
     * @param mixed $focal_point_x
     */
    public function setFocalPointX($focal_point_x): void
    {
        if (! is_numeric($focal_point_x)) {
            $this->focal_point_x = 0;
        } elseif ($focal_point_x > 50) {
            $this->focal_point_x = 50;
        } elseif ($focal_point_x <= -50) {
            $this->focal_point_x = -50;
        } else {
            $this->focal_point_x = $focal_point_x;
        }
    }

    /**
     * Sets Focal Point Y
     * @param $focal_point_y
     */
    public function setFocalPointY($focal_point_y): void
    {
        if (! is_numeric($focal_point_y)) {
            $this->focal_point_y = 0;
        } elseif ($focal_point_y > 50) {
            $this->focal_point_y = 50;
        } elseif ($focal_point_y <= -50) {
            $this->focal_point_y = -50;
        } else {
            $this->focal_point_y = $focal_point_y;
        }
    }

    /**
     * @throws Exception
     */
    public function resize()
    {
        // load for resizing
        $assets_filename = $this->filename;
        $assets_filepath = $this->assets_dir . $this->filename;

        // valid files existence
        $assets_validator = new Exists($this->assets_dir);
        if (!$assets_validator->isValid($assets_filename)) {
            //throw new Exception('Original file not found');
        }

        // create image from file
        switch ($this->file_extension) {
            case 'gif':
                $this->image_original = imagecreatefromgif($assets_filepath);
                break;
            case 'png':
                $this->image_original = imagecreatefrompng($assets_filepath);
                break;
            case 'jpeg':
            case 'jpg':
                $this->image_original = imagecreatefromjpeg($assets_filepath);
                break;
            default:
                throw new Exception('Unsupported file type');
        }

        // get original width and height
        list($this->width_original, $this->height_original) = getimagesize($this->assets_dir . $this->filename);

        // determine original ratio and desired draw image size
        $this->ratio_original = $this->width_original / $this->height_original;

        if (is_numeric($this->width) || is_numeric($this->height)) {
            // if desired width and height set
            if (is_numeric($this->width) && is_numeric($this->height)) {
                if ($this->width < $this->height) {
                    $draw_image_height = $this->height;
                    $draw_image_width = $this->height * $this->ratio_original;
                } else {
                    $draw_image_height = $this->width / $this->ratio_original;
                    $draw_image_width = $this->width;
                }
                // if width is not set but height is
            } elseif (! is_numeric($this->width) && is_numeric($this->height)) {
                $this->width = $this->height * $this->ratio_original;
                $draw_image_width = $this->height * $this->ratio_original;
            // if width is set but height is not set
            } elseif (is_numeric($this->width) && ! is_numeric($this->height)) {
                $this->height = $this->width * $this->ratio_original;
                $draw_image_height = $this->height * $this->ratio_original;
            }

            // rescale
            if ($draw_image_width < $this->width) {
                $difference = $this->width - $draw_image_width;
                $draw_image_width += $difference;
                $draw_image_height += $difference;
            } elseif ($draw_image_height < $this->height) {
                $difference = $this->height - $draw_image_height;
                $draw_image_width += $difference;
                $draw_image_height += $difference;
            }

            // compute offset
            $max_y = ($draw_image_height - $this->height) / 2;
            $center_y = ($this->height/2) - ($draw_image_height/2);
            $percent_y = ($this->focal_point_y * 2) * 0.01;
            $offset_y = $center_y - ($max_y * $percent_y);

            // compute offset
            $max_x = ($draw_image_width - $this->width) / 2;
            $center_x = ($this->width/2) - ($draw_image_width/2);
            $percent_x = ($this->focal_point_x * 2) * 0.01;
            $offset_x = $center_x - ($max_x * $percent_x);
        } else {
            // no width or height specified, use original file heights
            $this->width = $this->width_original;
            $this->height = $this->height_original;
            $draw_image_height = $this->height_original;
            $draw_image_width = $this->width_original;
            $offset_x = 0;
            $offset_y = 0;
        }

        /*
         * debug
        echo "
        img $this->image,
        img $this->image_original,
        dst_x $offset_x,
        dst_y $offset_y,
        src y 0,
        src x 0,
        dst w $draw_image_width,
        dst h $draw_image_height,
        src w $this->width_original,
        src h $this->height_original";
        die();
        */

        // create image from file
        $this->image = imagecreatetruecolor($this->width, $this->height);
        switch ($this->file_extension) {
            case 'jpg':
            case 'jpeg':
            case 'gif':
                imagecopyresampled(
                    $this->image,
                    $this->image_original,
                    $offset_x,
                    $offset_y,
                    0,
                    0,
                    $draw_image_width,
                    $draw_image_height,
                    $this->width_original,
                    $this->height_original
                );
                break;
            case 'png':
                imagealphablending($this->image, false);
                imagesavealpha($this->image, true);
                imagecopyresampled(
                    $this->image,
                    $this->image_original,
                    $offset_x,
                    $offset_y,
                    0,
                    0,
                    $this->width,
                    $this->height,
                    $this->width_original,
                    $this->height_original
                );
                break;
            default:
                throw new Exception('Unsupported file type');
        }
    }

    /**
     * @return string
     */
    public function getFileExtension() : string
    {
        return $this->file_extension;
    }

    /**
     * @return bool
     */
    public function isCached() : bool
    {
        $cache_validator = new Exists($this->cache_dir);
        if ($cache_validator->isValid($this->cache_filename)) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getCacheFilepath() : string
    {
        return $this->cache_filepath;
    }

    /**
     * Saves $this->image to specific to cache file path
     * @throws Exception
     */
    public function saveCache() : void
    {
        switch ($this->getFileExtension()) {
            case 'gif':
                imagegif($this->image, $this->cache_filepath);
                break;
            case 'png':
                imagepng($this->image, $this->cache_filepath, 9);
                break;
            case 'jpeg':
            case 'jpg':
                imagejpeg($this->image, $this->cache_filepath, 100);
                break;
            default:
                throw new Exception('Unsupported file extension.');
        }
    }

    /**
     * Sets Focal Point X & Y
     * @param $focal_point_x
     * @param $focal_point_y
     */
    public function setFocalPoints($focal_point_x, $focal_point_y) : void
    {
        $this->focal_point_x = $focal_point_x;
        $this->focal_point_y = $focal_point_y;
    }

    /**
     * Sets both height and width
     * @param $height
     * @param $width
     */
    public function setDimensions($height, $width) : void
    {
        $this->height = $height;
        $this->width = $width;
    }

    /**
     * Crops image
     * @param null $height
     * @param null $width
     * @param null $focal_point_x
     * @param null $focal_point_y
     */
    public function crop($height = null, $width = null, $focal_point_x = null, $focal_point_y = null)
    {
        if ($height !== null) {
            $this->height = $height;
        }

        if ($width !== null) {
            $this->width = $width;
        }

        if ($focal_point_x !== null) {
            $this->focal_point_x = $focal_point_x;
        }

        if ($focal_point_y !== null) {
            $this->focal_point_x = $focal_point_y;
        }
    }
}
