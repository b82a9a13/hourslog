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

$cid = $_GET['cid'];
$uid = $_GET['uid'];
$fullname = '';
if($_GET['uid']){
    if(!preg_match("/^[0-9]*$/", $uid) || empty($uid)){
        echo("Invalid user id provided.");
        exit();
    } else {
        if($_GET['cid']){
            if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
                echo("Invalid course id provided.");
                exit();
            } else {
                if($lib->check_coach_course($cid)){
                    $context = context_course::instance($cid);
                    require_capability('local/hourslog:teacher', $context);
                    $fullname = $lib->check_learner_enrolment($cid, $uid);
                    if($fullname == false){
                        echo("The user selected is not enrolled as a learner in the course selected.");
                        exit();
                    } else {
                        $_SESSION['hl_records_uid'] = $uid;
                        $_SESSION['hl_records_cid'] = $cid;
                    }
                } else {
                    echo("You are not enrolled as a coach in the course provided.");
                    exit();
                }
            }
        } else {
            echo("No course id provided.");
            exit();
        }
    }
} else {
    echo("No user id provided.");
    exit();
}

$hlarray = $lib->get_hours_logs();
$parray = $lib->get_progress_info();
$iarray = $lib->get_info_table_data();
include('./../inc/pdf.inc.php');
\local_hourslog\event\viewed_hourslog_pdf::create(array('context' => \context_course::instance($cid), 'courseid' => $cid, 'relateduserid' => $uid))->trigger();