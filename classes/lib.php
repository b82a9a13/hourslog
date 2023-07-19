<?php
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */
namespace local_hourslog;
use stdClass;

class lib{
        
    //Get the category id for apprenticeships
    public function get_category_id(){
        global $DB;
        return $DB->get_record_sql('SELECT id FROM {course_categories} WHERE name = ?',['Apprenticeships'])->id;
    }

    //Get current userid
    public function get_userid(){
        global $USER;
        return $USER->id;
    }

    //Get full course name from course id
    public function get_course_fullname($id){
        global $DB;
        return $DB->get_record_sql('SELECT fullname FROM {course} WHERE id = ?',[$id])->fullname;
    }

    public function get_current_user_fullname(){
        global $DB;
        $record = $DB->get_record_sql('SELECT firstname, lastname FROM {user} WHERE id = ?',[$this->get_userid()]);
        return $record->firstname.' '.$record->lastname;
    }

    //Get user full name from a specific id
    public function get_user_fullname($id){
        global $DB;
        $record = $DB->get_record_sql('SELECT firstname, lastname FROM {user} WHERE id = ?',[$id]);
        return $record->firstname.' '.$record->lastname;
    }

    //Check if the current user is enrolled as a coach in a apprenticeship course
    public function check_coach(){
        global $DB;
        $records = $DB->get_records_sql('SELECT {enrol}.courseid as courseid, {course}.fullname as fullname FROM {user_enrolments}
            INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
            INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
            INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
            INNER JOIN {course} ON {course}.id = {enrol}.courseid
            WHERE {role_assignments}.roleid IN (3,4) AND {user_enrolments}.userid = ? AND {course}.category = ? AND {user_enrolments}.status = 0 AND {role_assignments}.userid = {user_enrolments}.userid',
        [$this->get_userid(), $this->get_category_id()]);
        $array = [];
        foreach($records as $record){
            array_push($array, [$record->courseid, $record->fullname]);
        }
        return $array;
    }
    
    //Check if the current user is enrolled in the course provided as a coach
    public function check_coach_course($id){
        global $DB;
        $record = $DB->get_record_sql('SELECT {enrol}.courseid as courseid FROM {user_enrolments}
            INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
            INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
            INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
            INNER JOIN {course} ON {course}.id = {enrol}.courseid
            WHERE {role_assignments}.roleid IN (3,4) AND {user_enrolments}.userid = ? AND {course}.category = ? AND {user_enrolments}.status = 0 AND {role_assignments}.userid = {user_enrolments}.userid AND {course}.id = ?',
        [$this->get_userid(), $this->get_category_id(), $id]);
        if($record->courseid != null){
            return true;
        } else {
            return false;
        }
    }
    
    //Get the record for a specific course
    public function get_course_record($id){
        global $DB;
        return $DB->get_record_sql('SELECT * FROM {course} WHERE id = ?',[$id]);
    }

    //Get the progress for a specific userid and courseid
    public function get_progress_uid_cid($uid, $cid){
        global $DB;
        $record = $DB->get_record_sql('SELECT otjhours, totalmonths, startdate FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
        $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
            INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
            WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
        [$uid, $cid]);
        $expected = floatval(
            number_format((($record->otjhours / $record->totalmonths) / 4) *
            (round((date('U') - $record->startdate) / 604800) / $record->otjhours) * 100, 0, '.',' ')
        );
        $expected = ($expected < 0) ? 0 : $expected;
        $percent = 0;
        if(count($records) > 0){
            $duration = 0;
            foreach($records as $rec){
                $duration += $rec->duration;
            }
            $percent = floatval(number_format(($duration / $record->otjhours) * 100, 0, '.',' '));
            $percent = ($percent < 0) ? 0 : $percent;
            $percent = ($percent > 100) ? 100 : $percent;
        }
        return [$percent, $expected];
    }

    //Get learners for a specific course
    public function get_enrolled_learners($id){
        global $DB;
        if($this->check_coach_course($id)){
            $records = $DB->get_records_sql('SELECT {user}.firstname as firstname, {user}.lastname as lastname, {user}.id as id FROM {user_enrolments} 
                INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
                INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
                INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
                INNER JOIN {course} ON {course}.id = {enrol}.courseid 
                INNER JOIN {user} ON {user}.id = {user_enrolments}.userid
                WHERE {enrol}.courseid = ? AND {user_enrolments}.status = 0 AND {role_assignments}.roleid = 5 AND {course}.category = ? AND {user_enrolments}.userid = {role_assignments}.userid',
            [$id, $this->get_category_id()]);
            if(count($records) > 0){
                $array = [];
                foreach($records as $record){
                    //Need to add data for if a setup is created or not
                    $tmpRecord = $DB->get_record_sql('SELECT coachsign FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$record->id, $id]);
                    if($tmpRecord != null){
                        if($tmpRecord->coachsign != '' && $tmpRecord->coachsign != null){
                            array_push($array, [$record->firstname.' '.$record->lastname, $id, $record->id, true, true, $this->get_progress_uid_cid($record->id, $id)]);
                        } else {
                            array_push($array, [$record->firstname.' '.$record->lastname, $id, $record->id, true, false]);
                        }
                    } else {
                        array_push($array, [$record->firstname.' '.$record->lastname, $id, $record->id, false, false]);
                    }
                }
                asort($array);
                return $array;
            } else {
                return ['invalid'];
            }
        } else {
            return 'invalid';
        }
    }

    //Check if a userid is enrolled in a course as a learner
    public function check_learner_enrolment($cid, $uid){
        global $DB;
        $record = $DB->get_record_sql('SELECT {user}.firstname as firstname, {user}.lastname as lastname FROM {user_enrolments} 
            INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
            INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
            INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
            INNER JOIN {course} ON {course}.id = {enrol}.courseid 
            INNER JOIN {user} ON {user}.id = {user_enrolments}.userid
            WHERE {enrol}.courseid = ? AND {user_enrolments}.status = 0 AND {role_assignments}.roleid = 5 AND {course}.category = ? AND {user_enrolments}.userid = {role_assignments}.userid AND {user_enrolments}.userid = ?',
        [$cid, $this->get_category_id(), $uid]);
        if($record->firstname != null){
            return $record->firstname.' '.$record->lastname;
        } else {
            return false;
        }
    }

    //Get plan modules for a specific user and course 
    public function get_plan_json_modules(){
        global $DB;
        global $CFG;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return [];
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        $file = $DB->get_record_sql('SELECT planfilename FROM {trainingplan_setup} WHERE userid = ? AND courseid = ?',[$uid, $cid])->planfilename;
        $json = json_decode(file_get_contents($CFG->dirroot.'/local/trainingplan/templates/json/'.$file));
        $array = [];
        foreach($json->modules as $arr){
            array_push($array, [str_replace(',','',$arr->name)]);
        }
        return $array;
    }

    //get hours log id for a specific userid and courseid
    public function get_hours_log_id($uid, $cid){
        global $DB;
        return $DB->get_record_sql('SELECT id FROM {hourslog_hours} WHERE userid = ? AND courseid = ?',[$uid, $cid])->id;
    }

    //Create a hours log by a coach
    public function create_hours_log($data){
        global $DB;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return false;
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            $record = new stdClass();
            $record->userid = $uid;
            $record->courseid = $cid;
            if(!$DB->insert_record('hourslog_hours', $record, false)){
                return false;
            }
        }
        $record = new stdClass();
        $record->hoursid = $this->get_hours_log_id($uid, $cid);
        $record->date = $data[0];
        $record->activity = $data[1];
        $record->whatlink = $data[2];
        $record->impact = $data[3];
        $record->duration = $data[4];
        $record->creatorid = $this->get_userid();
        if($DB->insert_record('hourslog_hours_info', $record, false)){
            \local_hourslog\event\created_hourslog::create(array('context' => \context_course::instance($cid), 'courseid' => $cid, 'relateduserid' => $uid))->trigger();
            return true;
        } else {
            return false;
        }
    }

    public function get_initials_uid($uid){
        global $DB;
        $record = $DB->get_record_sql('SELECT firstname, lastname FROM {user} WHERE id = ?',[$uid]);
        return substr($record->firstname, 0, 1).''.substr($record->lastname, 0, 1);
    }

    //Get hours log for a specific user and course
    public function get_hours_logs(){
        global $DB;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return false;
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        $array = [];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        }
        $hlid = $this->get_hours_log_id($uid, $cid);
        $records = $DB->get_records_sql('SELECT * FROM {hourslog_hours_info} WHERE hoursid = ?',[$hlid]);
        if(count($records) > 0){
            $int = 1;
            foreach($records as $record){
                array_push($array, [
                    $int,
                    $record->id, 
                    date('d/m/Y',$record->date), 
                    $record->activity, 
                    $record->whatlink, 
                    $record->impact, 
                    $record->duration, 
                    $record->creatorid, 
                    $this->get_initials_uid($record->creatorid)
                ]);
                $int++;
            }
        }
        return $array;
    }

    //Get hours log data by id
    public function get_hours_log_id_data($id){
        global $DB;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return false;
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        }
        $hlid = $this->get_hours_log_id($uid, $cid);
        if($DB->record_exists('hourslog_hours_info', [$DB->sql_compare_text('id') => $id, $DB->sql_compare_text('hoursid') => $hlid])){
            $record = $DB->get_record_sql('SELECT id, date, activity, whatlink, impact, duration FROM {hourslog_hours_info} WHERE id = ? and hoursid = ?',[$id, $hlid]);
            return [
                $record->id,
                date('Y-m-d',$record->date),
                $record->activity,
                $record->whatlink,
                $record->impact,
                $record->duration
            ];
        } else {
            return false;
        }
    }

    //Update a hours log record
    public function update_hours_log($data){
        global $DB;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid']) || !isset($_SESSION['hl_records_lid'])){
            return false;
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        $lid = $_SESSION['hl_records_lid'];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        }
        $hlid = $this->get_hours_log_id($uid, $cid);
        if($DB->record_exists('hourslog_hours_info', [$DB->sql_compare_text('id') => $lid, $DB->sql_compare_text('hoursid') => $hlid])){
            $record = new stdClass();
            $record->id = $lid;
            $record->date = $data[0];
            $record->activity = $data[1];
            $record->whatlink = $data[2];
            $record->impact = $data[3];
            $record->duration = $data[4];
            $record->creatorid = $this->get_userid();
            if($DB->update_record('hourslog_hours_info', $record)){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //Get data for hours info table
    public function get_info_table_data(){
        global $DB;
        $array = [];
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return $array;
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return $array;
        } else {
            $record = $DB->get_record_sql('SELECT otjhours, hoursperweek, totalmonths, annuallw FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
                INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
                WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
            [$uid, $cid]);
            $totalHL = $record->otjhours;
            foreach($records as $rec){
                $totalHL -= $rec->duration;
            }
            $totalHL = ($totalHL < 0) ? 0 : $totalHL;
            $array = [
                $record->otjhours,
                $totalHL,
                $record->hoursperweek,
                $record->totalmonths,
                round($record->totalmonths*4.34),
                $record->annuallw
            ];
            return $array;
        }
    }

    //Get total number of OTJ hours left
    public function get_total_otjh_left(){
        global $DB;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return 'Error';
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return 'Error';
        } else {
            $record = $DB->get_record_sql('SELECT otjhours FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
                INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
                WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
            [$uid, $cid]);
            $totalHL = $record->otjhours;
            foreach($records as $rec){
                $totalHL -= $rec->duration;
            }
            $totalHL = ($totalHL < 0) ? 0 : $totalHL;
            return $totalHL;
        }
    }

    //Get progress, expected and incomplete for a specific userid and course id
    public function get_progress_info(){
        global $DB;
        if(!isset($_SESSION['hl_records_uid']) || !isset($_SESSION['hl_records_cid'])){
            return false;
        }
        $uid = $_SESSION['hl_records_uid'];
        $cid = $_SESSION['hl_records_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        } else {
            $record = $DB->get_record_sql('SELECT otjhours, totalmonths, startdate FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
                INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
                WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
            [$uid, $cid]);
            $expected = floatval(
                number_format((($record->otjhours / $record->totalmonths) / 4) *
                (round((date('U') - $record->startdate) / 604800) / $record->otjhours) * 100, 0, '.',' ')
            );
            $expected = ($expected < 0) ? 0 : $expected;
            $percent = 0;
            if(count($records) > 0){
                $duration = 0;
                foreach($records as $rec){
                    $duration += $rec->duration;
                }
                $percent = floatval(number_format(($duration / $record->otjhours) * 100, 0, '.',' '));
                $percent = ($percent < 0) ? 0 : $percent;
                $percent = ($percent > 100) ? 100 : $percent;
            }
            return [$percent, $expected];
        }
    }

    //Check if a setup exists for a specific userid and course id for a learner
    public function check_setup_exists_learner($cid){
        global $DB;
        if($DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $this->get_userid(), $DB->sql_compare_text('courseid') => $cid])){
            return true;
        } else {
            return false;
        }
    }

    //Get plan modules for a specific user and course : learner 
    public function get_plan_json_modules_learn(){
        global $DB;
        global $CFG;
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return [];
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        $file = $DB->get_record_sql('SELECT planfilename FROM {trainingplan_setup} WHERE userid = ? AND courseid = ?',[$uid, $cid])->planfilename;
        $json = json_decode(file_get_contents($CFG->dirroot.'/local/trainingplan/templates/json/'.$file));
        $array = [];
        foreach($json->modules as $arr){
            array_push($array, [str_replace(',','',$arr->name)]);
        }
        return $array;
    }

    //Get data for hours info table : learner
    public function get_info_table_data_learn(){
        global $DB;
        $array = [];
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return $array;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return $array;
        } else {
            $record = $DB->get_record_sql('SELECT otjhours, hoursperweek, totalmonths, annuallw FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
                INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
                WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
            [$uid, $cid]);
            $totalHL = $record->otjhours;
            foreach($records as $rec){
                $totalHL -= $rec->duration;
            }
            $totalHL = ($totalHL < 0) ? 0 : $totalHL;
            $array = [
                $record->otjhours,
                $totalHL,
                $record->hoursperweek,
                $record->totalmonths,
                round($record->totalmonths*4.34),
                $record->annuallw
            ];
            return $array;
        }
    }

    //Get hours log for a specific user and course : learner
    public function get_hours_logs_learn(){
        global $DB;
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        $array = [];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        }
        $hlid = $this->get_hours_log_id($uid, $cid);
        $records = $DB->get_records_sql('SELECT * FROM {hourslog_hours_info} WHERE hoursid = ?',[$hlid]);
        if(count($records) > 0){
            $int = 1;
            foreach($records as $record){
                array_push($array, [
                    $int,
                    $record->id, 
                    date('d/m/Y',$record->date), 
                    $record->activity, 
                    $record->whatlink, 
                    $record->impact, 
                    $record->duration, 
                    $record->creatorid, 
                    $this->get_initials_uid($record->creatorid)
                ]);
                $int++;
            }
        }
        return $array;
    }

    //Create a hours log by a coach
    public function create_hours_log_learn($data){
        global $DB;
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            $record = new stdClass();
            $record->userid = $uid;
            $record->courseid = $cid;
            if(!$DB->insert_record('hourslog_hours', $record, false)){
                return false;
            }
        }
        $record = new stdClass();
        $record->hoursid = $this->get_hours_log_id($uid, $cid);
        $record->date = $data[0];
        $record->activity = $data[1];
        $record->whatlink = $data[2];
        $record->impact = $data[3];
        $record->duration = $data[4];
        $record->creatorid = $this->get_userid();
        if($DB->insert_record('hourslog_hours_info', $record, false)){
            return true;
        } else {
            return false;
        }
    }

    //Get hours log data by id : learner
    public function get_hours_log_id_data_learn($id){
        global $DB;
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        }
        $hlid = $this->get_hours_log_id($uid, $cid);
        if($DB->record_exists('hourslog_hours_info', [$DB->sql_compare_text('id') => $id, $DB->sql_compare_text('hoursid') => $hlid])){
            $record = $DB->get_record_sql('SELECT id, date, activity, whatlink, impact, duration FROM {hourslog_hours_info} WHERE id = ? and hoursid = ?',[$id, $hlid]);
            return [
                $record->id,
                date('Y-m-d',$record->date),
                $record->activity,
                $record->whatlink,
                $record->impact,
                $record->duration
            ];
        } else {
            return false;
        }
    }

    //Update a hours log record
    public function update_hours_log_learn($data){
        global $DB;
        if(!isset($_SESSION['hl_lrecords_cid']) || !isset($_SESSION['hl_lrecords_lid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        $lid = $_SESSION['hl_lrecords_lid'];
        if(!$DB->record_exists('hourslog_hours', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        }
        $hlid = $this->get_hours_log_id($uid, $cid);
        if($DB->record_exists('hourslog_hours_info', [$DB->sql_compare_text('id') => $lid, $DB->sql_compare_text('hoursid') => $hlid])){
            $record = new stdClass();
            $record->id = $lid;
            $record->date = $data[0];
            $record->activity = $data[1];
            $record->whatlink = $data[2];
            $record->impact = $data[3];
            $record->duration = $data[4];
            $record->creatorid = $this->get_userid();
            if($DB->update_record('hourslog_hours_info', $record)){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //Get progress, expected and incomplete for a specific userid and course id
    public function get_progress_info_learn(){
        global $DB;
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        } else {
            $record = $DB->get_record_sql('SELECT otjhours, totalmonths, startdate FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
                INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
                WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
            [$uid, $cid]);
            $expected = floatval(
                number_format((($record->otjhours / $record->totalmonths) / 4) *
                (round((date('U') - $record->startdate) / 604800) / $record->otjhours) * 100, 0, '.',' ')
            );
            $expected = ($expected < 0) ? 0 : $expected;
            $percent = 0;
            if(count($records) > 0){
                $duration = 0;
                foreach($records as $rec){
                    $duration += $rec->duration;
                }
                $percent = floatval(number_format(($duration / $record->otjhours) * 100, 0, '.',' '));
                $percent = ($percent < 0) ? 0 : $percent;
                $percent = ($percent > 100) ? 100 : $percent;
            }
            return [$percent, $expected];
        }
    }

    //Get total number of OTJ hours left
    public function get_total_otjh_left_learn(){
        global $DB;
        if(!isset($_SESSION['hl_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['hl_lrecords_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return 'Error';
        } else {
            $record = $DB->get_record_sql('SELECT otjhours FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            $records = $DB->get_records_sql('SELECT {hourslog_hours_info}.duration as duration FROM {hourslog_hours} 
                INNER JOIN {hourslog_hours_info} ON {hourslog_hours_info}.hoursid = {hourslog_hours}.id
                WHERE {hourslog_hours}.userid = ? AND {hourslog_hours}.courseid = ?',
            [$uid, $cid]);
            $totalHL = $record->otjhours;
            foreach($records as $rec){
                $totalHL -= $rec->duration;
            }
            $totalHL = ($totalHL < 0) ? 0 : $totalHL;
            return $totalHL;
        }
    }
}