<?php
/**
 * UTF8::trim
 *
 * @package    KO7
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */

use \KO7\UTF8;

function _trim($str, $charlist = NULL)
{
    if ($charlist === NULL) {
        return trim($str);
    }

    return UTF8::ltrim(UTF8::rtrim($str, $charlist), $charlist);
}
