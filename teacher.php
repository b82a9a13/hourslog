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

$type = '';
$enrolments = [];
$id = null;
$errorTxt = '';
if(isset($_GET['id'])){
    $id = $_GET['id'];
    if(!preg_match("/^[0-9]*$/", $id) || empty($id)){
        $errorTxt = get_string('invalid_cip', $p);
    } else {
        if($lib->check_coach_course($id)){
            $context = context_course::instance($id);
            require_capability('local/hourslog:teacher', $context);
            $PAGE->set_context($context);
            $PAGE->set_course($lib->get_course_record($id));
            $type = 'one';
        } else {
            $errorTxt = get_string('not_eacicp', $p);
        }
    }
} else {
    $enrolments = $lib->check_coach();
    if(count($enrolments) > 0){
        $context = context_course::instance($enrolments[0][0]);
        require_capability('local/hourslog:teacher', $context);
        $PAGE->set_context($context);
        $type = 'all';
    } else {
        $errorTxt = get_string('no_ca', $p);
    }
}

$title = get_string('hours_log', $p);
$PAGE->set_url(new moodle_url('/local/hourslog/teacher.php'));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

if($errorTxt != ''){
    echo("<h1 class='text-error'>$errorTxt</h1>");
} else {
    if($type == 'all'){
        $_SESSION['hl_menu_type'] = 'all';
        $template = (Object)[
            'enrolments' => array_values($enrolments),
            'title' => get_string('hours_logs', $p)
        ];
        echo $OUTPUT->render_from_template('local_hourslog/teacher_all_courses', $template);
        echo("<script src='./amd/min/teacher_course.min.js' defer></script>");
        \local_hourslog\event\viewed_menu::create(array('context' => \context_system::instance()))->trigger();
    } elseif($type == 'one'){
        $_SESSION['hl_menu_type'] = 'one';
        $template = (Object)[
            'coursename' => $lib->get_course_fullname($id),
            'title' => get_string('hours_logs', $p)
        ];
        echo $OUTPUT->render_from_template('local_hourslog/teacher_one_course', $template);
        echo("<script src='./amd/min/teacher_course.min.js'></script>");
        echo("<script defer>course_clicked($id)</script>");
        \local_hourslog\event\viewed_menu::create(array('context' => \context_course::instance($id)))->trigger();
    }
}

echo $OUTPUT->footer();