<?php
/**
 * A HTTP Response specific interface that adds the methods required
 * by HTTP responses. Over and above [KO7_HTTP_Interaction], this
 * interface provides status.
 *
 * @package    KO7
 * @category   HTTP
 *
 * @since      3.1.0
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\HTTP;

interface Response extends Message
{

    /**
     * Sets or gets the HTTP status from this response.
     *
     * @param integer $code Status to set to this response
     * @return  mixed
     */
    public function status(int $code = NULL);

}
