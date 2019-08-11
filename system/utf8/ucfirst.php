<?php
/**
 * UTF8::ucfirst
 *
 * @package    KO7
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */

use \KO7\UTF8;

function _ucfirst($str)
{
    if (UTF8::is_ascii($str)) {
        return ucfirst($str);
    }

    preg_match('/^(.?)(.*)$/us', $str, $matches);
    return UTF8::strtoupper($matches[1]) . $matches[2];
}
