<?php

/**
 * Interface for config readers
 *
 * @package    KO7
 * @category   Configuration
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\Config;

interface Reader extends Source
{

    /**
     * Tries to load the specified configuration group
     *
     * Returns FALSE if group does not exist or an array if it does
     *
     * @param string $group Configuration group
     * @return boolean|array
     */
    public function load(string $group);

}
