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

$cid = null;
$uid = null;
$fullname = '';
if(isset($_GET['uid'])){
    $uid = $_GET['uid'];
    if(!preg_match("/^[0-9]*$/", $uid) || empty($uid)){
        echo(get_string('invalid_uip', $p));
        exit();
    } else {
        if(isset($_GET['cid'])){
            $cid = $_GET['cid'];
            if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
                echo(get_string('invalid_cip', $p));
                exit();
            } else {
                if($lib->check_coach_course($cid)){
                    $context = context_course::instance($cid);
                    require_capability('local/hourslog:teacher', $context);
                    $fullname = $lib->check_learner_enrolment($cid, $uid);
                    if($fullname == false){
                        echo(get_string('selected_neal', $p));
                        exit();
                    } else {
                        $_SESSION['hl_records_uid'] = $uid;
                        $_SESSION['hl_records_cid'] = $cid;
                    }
                } else {
                    echo(get_string('not_eacicp', $p));
                    exit();
                }
            }
        } else {
            echo(get_string('no_cip', $p));
            exit();
        }
    }
} else {
    echo(get_string('no_uip', $p));
    exit();
}

$hlarray = $lib->get_hours_logs();
$parray = $lib->get_progress_info();
$iarray = $lib->get_info_table_data();
include('./../inc/pdf.inc.php');
\local_hourslog\event\viewed_hourslog_pdf::create(array('context' => \context_course::instance($cid), 'courseid' => $cid, 'relateduserid' => $uid))->trigger();