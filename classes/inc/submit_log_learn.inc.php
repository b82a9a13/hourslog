<?php
require_once(__DIR__.'/../../../../config.php');
use local_hourslog\lib;
require_login();
$lib = new lib;
$returnText = new stdClass();
$p = 'local_hourslog';

//Validation regex
$textarea = "/^[a-zA-Z0-9 ,.!'():;\s\-#]*$/";
$textareaReplace = "/[a-zA-Z0-9 ,.!'():;\s\-#]/";

$errorarray = [];
$date = $_POST['date'];
if($date != null && !empty($date)){
    if(!preg_match("/^[0-9-]*$/", $date) || empty($date)){
        array_push($errorarray, ['date', get_string('date', $p)]);
    } else {
        $date = (new DateTime($date))->format('U');
    }
} else {
    array_push($errorarray, ['date', get_string('date', $p)]);
}
$activity = $_POST['activity'];
if(!preg_match($textarea, $activity) || empty($activity)){
    array_push($errorarray, ['activity', get_string('activity', $p).':'.preg_replace($textareaReplace, '', $activity)]);
}
$whatlink = $_POST['whatlink'];
if(!preg_match("/^[a-z A-Z0-9]*$/", $whatlink) || empty($whatlink)){
    array_push($errorarray, ['whatlink', get_string('what_link_short', $p).':'.preg_replace("/[a-z A-Z]/", '', $whatlink)]);
}
$impact = $_POST['impact'];
if(!preg_match($textarea, $impact) || empty($impact)){
    array_push($errorarray, ['impact', get_string('what_learn_short', $p).':'.preg_replace($textareaReplace, '', $impact)]);
}
$duration = $_POST['duration'];
if(!preg_match("/^[0-9.]*$/", $duration) || empty($duration)){
    array_push($errorarray, ['duration', get_string('duration', $p).':'.preg_replace("/[0-9.]/", '', $duration)]);
}

if($errorarray != []){
    $returnText->error = $errorarray;
} else {
    $returnText->return = ($lib->create_hours_log_learn([
        $date,
        $activity,
        $whatlink,
        $impact,
        $duration
    ])) ? true : false;
}
//Output return text
echo(json_encode($returnText));