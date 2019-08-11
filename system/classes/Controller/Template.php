<?php
/**
 * Abstract controller class for automatic templating.
 *
 * @package    KO7
 * @category   Controller
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\Controller;

use \KO7\Controller;
use \KO7\View;

abstract class Template extends Controller
{

    /**
     * @var  mixed  page template
     */
    public $template = 'template';

    /**
     * @var  boolean  auto render template
     **/
    public $auto_render = true;

    /**
     * Loads the template [View] object.
     */
    public function before(): void
    {
        parent::before();

        if ($this->auto_render === TRUE) {
            // Load the template
            $this->template = View::factory($this->template);
        }
    }

    /**
     * Assigns the template [View] as the request response.
     */
    public function after(): void
    {
        if ($this->auto_render === TRUE) {
            $this->response->body($this->template->render());
        }

        parent::after();
    }

}
