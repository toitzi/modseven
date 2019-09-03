<?php
/**
 * Model base class. All models should extend this class.
 *
 * @package    KO7
 * @category   Models
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7;

abstract class Model
{
    /**
     * Create a new model instance.
     *
     *     $model = Model::factory($name);
     *
     * @param string $name model name
     * @return  Model
     */
    public static function factory(string $name): Model
    {
        // Add the model prefix
        $class = 'Model_' . $name;

        return new $class;
    }

}
