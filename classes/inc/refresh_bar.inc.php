<?php
require_once(__DIR__.'/../../../../config.php');
use local_hourslog\lib;
require_login();
$lib = new lib;
$returnText = new stdClass();

$returnText->return = $lib->get_progress_info();

echo(json_encode($returnText));