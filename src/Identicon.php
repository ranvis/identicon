<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\Identicon;

class Identicon
{
    private $maxSize;
    private $tileSize;
    private $tiles;
    private $colors;
    private $white;
    private $tile;
    private $image;

    /**
     * @param int $maxSize maximum size of the icon to draw
     * @param TileInterface $tile tile to use
     * @param int $tiles complexity of the icon
     * @param int $colors maximum usable colors
     * @param bool $highQuality prefer quality over memory and speed
     */
    public function __construct($maxSize, TileInterface $tile, $tiles = 6, $colors = 2, $highQuality = true)
    {
        $this->maxSize = $maxSize;
        if ($highQuality && !function_exists('imageantialias')) {
            $maxSize *= 2; // increase rendering tile size
        }
        $this->tiles = $tiles;
        $this->colors = $colors;
        $res = $tiles * $tile->getMinimumSize();
        $this->tileSize = max(1, $highQuality ? (ceil($maxSize / $tiles / $res) * $res) : ceil($maxSize / $tiles));
        $this->tile = $tile;
    }

    /**
     * get number of hex characters required to draw icon
     * @return int required hash length (bits / 4)
     */
    public function getMinimumHashLength()
    {
        $xEnd = ($this->tiles + 1) >> 1;
        return $this->colors * 6 + 3 + ($xEnd + 1) * $xEnd / 2;
    }

    /**
     * draw icon to internal buffer
     * @param string $hash
     * @return $this
     */
    public function draw($hash)
    {
        $index = 0;
        $tiles = $this->tiles;
        $tileSize = $this->tileSize;
        $tile = $this->tile;
        $tile->allocate($tileSize);
        $br = $tileSize * ($tiles - 1);
        $offsets = array(
            // x, y, innerMoveX, innerMoveY, outerMoveX, outerMoveY
            // corners and sides
            array(0, 0, 0, 1, 1, 0),
            array($br, 0, -1, 0, 0, 1),
            array($br, $br, 0, -1, -1, 0),
            array(0, $br, 1, 0, 0, -1),
            // opposite sides
            array($br, 0, 0, 1, -1, 0),
            array($br, $br, -1, 0, 0, -1),
            array(0, $br, 0, -1, 1, 0),
            array(0, 0, 1, 0, 0, 1),
        );
        if (!$this->image) {
            $this->image = $image = imagecreatetruecolor($tileSize * $tiles, $tileSize * $tiles);
            $this->white = imagecolorallocate($image, 255, 255, 255);
        }
        $image = $this->image;
        $white = $this->white;
        $xEnd = ($tiles + 1) >> 1;
        $xMid = ($xEnd + 1) >> 1;
        $center = ($tiles & 1) ? ($tiles >> 1) : -1;
        $numColors = $this->colors;
        assert('$this->getMinimumHashLength() <= strlen($hash)');
        $colors = array();
        for ($i = 0; $i < $numColors; $i++) {
            $r = hexdec(substr($hash, $index, 2)); $index += 2;
            $g = hexdec(substr($hash, $index, 2)); $index += 2;
            $b = hexdec(substr($hash, $index, 2)); $index += 2;
            $colors[] = $tile->getColor($r, $g, $b);
        }
        $baseColor = hexdec($hash[$index++]);
        $colorPattern = hexdec($hash[$index++]);
        $type = hexdec($hash[$index++]);
        for ($x = 0; $x < $xEnd; $x++) {
            $xOffsets = $offsets;
            for ($y = 0; $y <= $x; $y++) {
                $color = $baseColor;
                if ($colorPattern & 1) {
                    $color++;
                }
                if ($colorPattern & 2) {
                    $color += $x;
                }
                if ($colorPattern & 4) {
                    $color += $y;
                }
                if ($colorPattern & 8) {
                    $color += $x <= $xMid;
                }
                $type += hexdec($hash[$index++]);
                $tileImage = $tile->draw($type, $colors[$color % $numColors]);
                for ($i = 0; $i < 8; $i++) {
                    if ($i == 4 && ($y == $x || $x == 0)) {
                        break;
                    }
                    $offset = $xOffsets[$i];
                    if ($i < 4 || $x != $center) {
                        if ($i == 4) {
                            imageflip($tileImage, IMG_FLIP_HORIZONTAL);
                        }
                        imagecopy($image, $tileImage, $offset[0], $offset[1], 0, 0, $tileSize, $tileSize);
                        if ($x == $center && $y == $center) {
                            break;
                        }
                        if ($i != 7) {
                            $tileImage = imagerotate($tileImage, 270, $white);
                        }
                    }
                    $xOffsets[$i][0] += $offset[2] * $tileSize;
                    $xOffsets[$i][1] += $offset[3] * $tileSize;
                }
            }
            for ($i = 0; $i < 8; $i++) {
                $offsets[$i][0] += $offsets[$i][4] * $tileSize;
                $offsets[$i][1] += $offsets[$i][5] * $tileSize;
            }
        }
        return $this;
    }

    /**
     * explicitly free memory used to render icon
     */
    public function free()
    {
        $this->tile->free();
        $this->image = null;
    }

    /**
     * get icon image
     * @param int $size image size
     * @return resource GD image
     */
    public function getImage($size = null)
    {
        assert('$this->image');
        if ($size === null) {
            $size = $this->maxSize;
        }
        $icon = imagecreatetruecolor($size, $size);
        $srcSize = $this->tileSize * $this->tiles;
        imagecopyresampled($icon, $this->image, 0, 0, 0, 0, $size, $size, $srcSize, $srcSize);
        return $icon;
    }

    /**
     * print image to stdout with Content-Type header
     * @param int $size image size
     * @param int $compression PNG compression level
     * @param int $filters PNG filter flags to use
     * @return bool true on success
     */
    public function output($size = null, $compression = -1, $filters = -1)
    {
        header('Content-Type: image/png');
        return imagepng($this->getImage($size), null, $compression, $filters);
    }

    /**
     * save PNG image to file
     * @param string $filePath file path to save
     * @param int $size image size
     * @param int $compression PNG compression level
     * @param int $filters PNG filter flags to use
     * @return bool true on success
     */
    public function save($filePath, $size = null, $compression = -1, $filters = -1)
    {
        return imagepng($this->getImage($size), $filePath, $compression, $filters);
    }
}
