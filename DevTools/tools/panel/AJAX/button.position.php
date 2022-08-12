<?php

use Arsavinel\Arshwell\Session;

Session::setPanel('button.position.top',	$_POST['tp']);
Session::setPanel('button.position.bottom',	'unset');
Session::setPanel('button.position.left',	$_POST['lft']);
Session::setPanel('button.position.right',	'unset');
