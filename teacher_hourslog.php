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