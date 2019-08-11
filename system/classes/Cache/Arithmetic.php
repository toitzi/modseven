<?php
/**
 * KO7 Cache Arithmetic Interface, for basic cache integer based
 * arithmetic, addition and subtraction
 *
 * @package    KO7/Cache
 * @category   Base
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 * @since      3.2.0
 */

namespace KO7\Cache;

interface Arithmetic
{

    /**
     * Increments a given value by the step value supplied.
     * Useful for shared counters and other persistent integer based
     * tracking.
     *
     * @param string    id of cache entry to increment
     * @param int       step value to increment by
     * @return  integer|bool
     */
    public function increment(string $id, int $step = 1);

    /**
     * Decrements a given value by the step value supplied.
     * Useful for shared counters and other persistent integer based
     * tracking.
     *
     * @param string    id of cache entry to decrement
     * @param int       step value to decrement by
     * @return  integer|bool
     */
    public function decrement(string $id, int $step = 1);

}