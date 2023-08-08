<?php
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

require_once(__DIR__.'/../../../../config.php');
use local_hourslog\lib;
require_login();
$lib = new lib;
$p = 'local_hourslog';

$errorTxt = '';
$cid = $_GET['cid'];
$fullname = '';
if($_GET['cid']){
    if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
        $errorTxt = get_string('invalid_cip', $p);
    } else {
        $context = context_course::instance($cid);
        require_capability('local/activityrecord:student', $context);
        $fullname = $lib->get_current_user_fullname();
        if(!$lib->check_setup_exists_learner($cid)){
            $errorTxt = get_string('coach_ncs', $p);
        } else {
            $_SESSION['hl_lrecords_cid'] = $cid;
        }
    }
} else {
    $errorTxt = get_string('no_cip', $p);
}
if($errorTxt != ''){
    echo $errorTxt;
    exit();
}
$hlarray = $lib->get_hours_logs_learn();
$parray = $lib->get_progress_info_learn();
$iarray = $lib->get_info_table_data_learn();
include('./../inc/pdf.inc.php');
\local_hourslog\event\viewed_hourslog_pdf_learn::create(array('context' => \context_course::instance($cid), 'courseid' => $cid))->trigger();