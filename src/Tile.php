<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\Identicon;

class Tile extends TileBase
{
    public function __construct(array $bgColor = null)
    {
        parent::__construct($bgColor);
        $this->patterns = [
            null, // empty
            [0, 0, 1, 0, .5, 1], 1, 2, 3, // regular triangle
            [0, 0, 1, 0, 0, 1], 1, 2, 3, // isosceles right triangle
            [0, 0, 1, 0.5, 0.5, 1], 1, 2, 3, // isosceles triangle
            [0, 0, 1, 1, 1, 0, 0, 1], 1, // bowknot
            [.5, 0, .5, 1, 1, .5, 0, .5], 1, // rotated bowknot
            [0, 0, .5, 0, 1, 1, .5, 1], 1, 4, 5, // parallelogram
            [.5, 0, 1, .5, .5, 1, 0, .5], // rotated square
            [0, 0, 1, 0, 1, 1], // fill
        ];
    }

    public function getMinimumSize()
    {
        return 3;
    }

    public function draw($type, $color)
    {
        $size = $this->size;
        $image = $this->image;
        assert('$image');
        list($poly, $rotation) = $this->getPattern($type);
        imagefilledrectangle($image, 0, 0, $size, $size, $this->bgColorValue);
        if ($poly) {
            foreach ($poly as &$pt) {
                $pt *= $size - 1;
            }
            unset($pt);
            imagefilledpolygon($image, $poly, count($poly) / 2, $color);
            $image = $this->applyRotation($image, $rotation);
        }
        return $image;
    }
}
