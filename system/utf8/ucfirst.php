<?php
/**
 * UTF8::ucfirst
 *
 * @package    Modseven
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) 2016-2019  Koseven Team
 * @copyright  (c) since 2019 Modseven Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */

use Modseven\UTF8;

function _ucfirst($str)
{
    if (UTF8::is_ascii($str)) {
        return ucfirst($str);
    }

    preg_match('/^(.?)(.*)$/us', $str, $matches);
    return UTF8::strtoupper($matches[1]) . $matches[2];
}
