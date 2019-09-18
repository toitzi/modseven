<?php
/**
 * Interface for config readers
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
     * @param string $group Configuration group
     *
     * @return boolean|array Returns FALSE if group does not exist or an array if it does
     */
    public function load(string $group);

}
