<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\Identicon;

abstract class TileBase implements ITile
{
    protected $size;
    protected $bgColor;
    protected $bgColorValue;
    protected $image;

    const PATTERN_ANGLE_MASK = 3;
    const PATTERN_FLIP_H = 4;
    const PATTERN_FLIP_V = 8;

    protected $patterns;

    public function __construct(array $bgColor = null)
    {
        if ($bgColor === null) {
            $bgColor = array(255, 255, 255);
        }
        $this->bgColor = $bgColor;
    }

    public function allocate($size)
    {
        if ($this->size === $size) {
            return;
        }
        $this->size = $size;
        $this->image = $image = imagecreatetruecolor($this->size, $this->size);
        if (function_exists('imageantialias')) { // some installations don't have one
            imageantialias($image, true);
        }
        $bgColor = $this->bgColor;
        $this->bgColorValue = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    }

    public function free()
    {
        $this->size = $this->image = $this->bgColorValue = null;
    }

    public function getColor($r, $g, $b)
    {
        if (($r & $g & $b & 0xc0) == 0xc0) {
            $flags = ($r + $g + $b) & 0x7;
            if ($flags == 0) {
                $flags = 0x7;
            }
            if ($flags & 1) {
                $r ^= 0xff;
            }
            if ($flags & 3) {
                $g ^= 0xff;
            }
            if ($flags & 5) {
                $b ^= 0xff;
            }
        }
        return imagecolorallocate($this->image, $r, $g, $b);
    }

    protected function getPattern($type)
    {
        $type %= count($this->patterns);
        $pattern = $this->patterns[$type];
        $rotation = 0;
        if (is_int($pattern)) {
            $rotation = $pattern;
            while (is_int($this->patterns[--$type])) {
            }
            $pattern = $this->patterns[$type];
        }
        return array($pattern, $rotation);
    }

    protected function applyRotation($image, $rotation)
    {
        if ($rotation & self::PATTERN_FLIP_H) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        if ($rotation & self::PATTERN_FLIP_V) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }
        if ($rotation & self::PATTERN_ANGLE_MASK) {
            $image = imagerotate($image, ($rotation & self::PATTERN_ANGLE_MASK) * 90, $this->bgColorValue);
        }
        return $image;
    }
}
