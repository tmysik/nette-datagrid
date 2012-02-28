<?php

namespace DataGrid;

/**
 * Global actions are rendered in the header and has no key (typically "Add" action).
 */
class GlobalAction extends Action {

    public function __construct($title, $destination, \Nette\Utils\Html $icon = NULL, $useAjax = FALSE, $key = self::WITHOUT_KEY) {
        parent::__construct($title, $destination, $icon, $useAjax, $key);
    }

}

?>
