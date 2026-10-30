<?php

namespace App\Classes;

use Larajax\LarajaxController;
use Larajax\Ui\Traits\ViewMaker;

/**
 * Base controller for pages that render Amber widgets.
 *
 * Extending LarajaxController wires up automatic AJAX handler routing, so a
 * widget built inside an action (for example a Form) can dispatch its handlers
 * (onSave, onDelete, ...) back to methods on this controller. The ViewMaker
 * trait adds the view helpers the widgets rely on.
 */
abstract class ControllerBase extends LarajaxController
{
    use ViewMaker;
}
