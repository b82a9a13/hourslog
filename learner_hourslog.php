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
$cid = $_GET['cid'];
$fullname = '';
if($_GET['cid']){
    if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
        $errorText = 'Invalid course id provided.';
    } else {
        $context = context_course::instance($cid);
        require_capability('local/activityrecord:student', $context);
        $PAGE->set_context($context);
        $PAGE->set_course($lib->get_course_record($cid));
        $PAGE->set_url(new moodle_url("/local/activityrecord/learner_records.php?cid=$cid"));
        $PAGE->set_title('Activity Records');
        $PAGE->set_heading('Activity Records');
        $PAGE->set_pagelayout('incourse');
        $fullname = $lib->get_current_user_fullname();
        if(!$lib->check_setup_exists_learner($cid)){
            $errorText = 'Your coach needs to create a setup for you.';
        } else {
            $_SESSION['hl_lrecords_cid'] = $cid;
        }
    }
} else {
    $errorTxt = 'No course id provided.';
}

echo $OUTPUT->header();

if($errorTxt != ''){
    echo("<h1 class='text-error'>$errorTxt</h1>");
} else {
    $template = (Object)[
        'fullname' => $fullname,
        'coursename' => $lib->get_course_fullname($cid),
        'modules_array' => array_values($lib->get_plan_json_modules_learn()),
        'logs_array' => $lib->get_hours_logs_learn(),
        'info_array' => array_values([$lib->get_info_table_data_learn()]),
        'cid' => $cid,
        'page_url' => '../../my/index.php',
        'learn_ext' => '_learn'
    ];
    echo $OUTPUT->render_from_template('local_hourslog/hours_log', $template);
    \local_hourslog\event\viewed_hourslog_learn::create(array('context' => \context_course::instance($cid), 'courseid' => $cid))->trigger();
}

echo $OUTPUT->footer();