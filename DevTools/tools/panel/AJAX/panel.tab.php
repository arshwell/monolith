<?php

use Arsavinel\Arshwell\Session;

$tabs   = explode('-', $_POST['tb']);
$tab    = '';
do {
    $name = array_shift($tabs);
    Session::setPanel('box.tab'.$tab, $name);

    $tab .= ('.'. $name);
} while ($tabs);
