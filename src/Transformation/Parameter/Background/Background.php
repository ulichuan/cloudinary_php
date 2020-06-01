<?php
/**
 * This file is part of the Cloudinary PHP package.
 *
 * (c) Cloudinary
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cloudinary\Transformation;

use Cloudinary\ClassUtils;
use Cloudinary\Transformation\Argument\ColorValue;
use Cloudinary\Transformation\Parameter\BaseParameter;
use Cloudinary\Transformation\Parameter\Value\ColorValueTrait;

/**
 * Class Background
 *
 * @api
 */
class Background extends BaseParameter
{
    use ColorValueTrait;

    /**
     * Background constructor.
     *
     * @param $color
     */
    public function __construct($color)
    {
        parent::__construct(ClassUtils::verifyInstance($color, ColorValue::class));
    }

    /**
     * Sets the background color.
     *
     * @param string $color The color. Can be RGB, HEX, named color, etc.
     *
     * @return Background
     *
     */
    public static function color($color)
    {
        return new self($color);
    }

    /**
     * Applies blurred background (Relevant only for videos).
     *
     * @param int $intensity  The intensity of the blur.
     * @param int $brightness The brightness of the background.
     *
     * @return BlurredBackground
     *
     */
    public static function blurred($intensity = null, $brightness = null)
    {
        return new BlurredBackground($intensity, $brightness);
    }

    /**
     * Applies blurred background (Relevant only for videos).
     *
     * @param string $autoBackground The type of the background color. See AutoBackground class.
     *
     * @return AutoBackground
     *
     * @see AutoBackground
     */
    public static function auto($autoBackground = null)
    {
        return ClassUtils::verifyInstance($autoBackground, AutoBackground::class);
    }
}
