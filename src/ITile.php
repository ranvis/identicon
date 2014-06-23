<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\Identicon;

interface ITile
{
    /**
     * @return int minimum size of the tile
     */
    public function getMinimumSize();

    /**
     * allocate tile of the specified size
     * @param int $size tile size
     */
    public function allocate($size);

    /**
     * free memory alloated for drawing tile
     */
    public function free();

    /**
     * allocate color for tile
     * @param int $r red component value
     * @param int $g green component value
     * @param int $b blue component value
     * @return mixed color
     */
    public function getColor($r, $g, $b);

    /**
     * draw tile
     * @param int $type type
     * @param int $color color
     */
    public function draw($type, $color);
}
