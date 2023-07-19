<?php
require_once(__DIR__.'/../../../../config.php');
use local_hourslog\lib;
require_login();
$lib = new lib;
$returnText = new stdClass();

$result = $lib->get_hours_logs();
$returnText->return = $result;

//Output return text
echo(json_encode($returnText));