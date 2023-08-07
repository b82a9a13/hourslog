<?php
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

require_once(__DIR__.'/../../config.php');
use local_hourslog\lib;
require_login();
$lib = new lib;
$p = 'local_hourslog';

$errorTxt = '';
$e = $_GET['e'];
$uid = $_GET['uid'];
$cid = $_GET['cid'];
$fullname = '';
if($_GET['e']){
    if(($e != 'a' && $e != 'c') || empty($e)){
        $errorTxt = 'Invalid e character provided.';
    } else {
        if($_GET['uid']){
            if(!preg_match("/^[0-9]*$/", $uid) || empty($uid)){
                $errorText = 'Invalid user id provided.';
            } else {
                if($_GET['cid']){
                    if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
                        $errorText = 'Invalid course id provided.';
                    } else {
                        if($lib->check_coach_course($cid)){
                            $context = context_course::instance($cid);
                            require_capability('local/hourslog:teacher', $context);
                            $PAGE->set_context($context);
                            $PAGE->set_course($lib->get_course_record($cid));
                            $PAGE->set_url(new moodle_url("/local/hourslog/teacher_hourslog.php?cid=$cid&uid=$uid"));
                            $PAGE->set_title('Off The Job Hours');
                            $PAGE->set_heading('Off The Job Hours');
                            $PAGE->set_pagelayout('incourse');
                            $fullname = $lib->check_learner_enrolment($cid, $uid);
                            if($fullname == false){
                                $errorText = 'The user selected is not enrolled as a learner in the course selected.';
                            } else {
                                $_SESSION['hl_records_uid'] = $uid;
                                $_SESSION['hl_records_cid'] = $cid;
                            }
                        } else  {
                            $errorText = 'You are not enrolled as a coach in the course provided.';
                        }
                    }
                } else {
                    $errorText = 'No course id provided.';
                }
            }
        } else {
            $errorText = 'No user id provided.';
        }
    }
}

echo $OUTPUT->header();
if($errorText != ''){
    echo("<h1 class='text-error'>$errorText</h1>");
} else {
    if($e == 'a'){
        $e = '';
    } elseif($e == 'c'){
        $e = '?id='.$cid;
    }
    $template = (Object)[
        'btm' => get_string('btm', $p),
        'title' => get_string('otj_hl', $p),
        'create_anr' => get_string('create_anr', $p),
        'date' => get_string('date', $p),
        'activity' => get_string('activity', $p),
        'what_link_title' => get_string('what_link_title', $p),
        'what_learn_title' => get_string('what_learn_title', $p),
        'duration_title' => get_string('duration_title', $p),
        'pick_am' => get_string('pick_am', $p),
        'submit' => get_string('submit', $p),
        'otj_hli' => get_string('otj_hli', $p),
        'progress' => get_string('progress', $p),
        'expected' => get_string('expected', $p),
        'incomplete' => get_string('incomplete', $p),
        'info_table' => get_string('info_table', $p),
        'total_notj_title' => get_string('total_notj_title', $p),
        'total_nohl_title' => get_string('total_nohl_title', $p),
        'otj_hpw' => get_string('otj_hpw', $p),
        'months_op' => get_string('months_op', $p),
        'weeks_op' => get_string('weeks_op', $p),
        'annual_lw' => get_string('annual_lw', $p),
        'otj_ht' => get_string('otj_ht', $p),
        'update_r' => get_string('update_r', $p),
        'reset' => get_string('reset', $p),
        'print_t' => get_string('print_t', $p),
        'id' => get_string('id', $p),
        'initials' => get_string('initials', $p),
        'fullname' => $fullname,
        'coursename' => $lib->get_course_fullname($cid),
        'btm_ext' => $e,
        'modules_array' => array_values($lib->get_plan_json_modules()),
        'logs_array' => $lib->get_hours_logs(),
        'info_array' => array_values([$lib->get_info_table_data()]),
        'cid' => $cid,
        'uid' => $uid,
        'page_url' => './teacher.php'
    ];
    echo $OUTPUT->render_from_template('local_hourslog/hours_log', $template);
    \local_hourslog\event\viewed_hourslog::create(array('context' => \context_course::instance($cid), 'courseid' => $cid, 'relateduserid' => $uid, 'other' => $_GET['e']))->trigger();
}
echo $OUTPUT->footer();