<?php
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

namespace local_hourslog\event;
use core\event\base;
defined('MOODLE_INTERNAL') || die();

class created_hourslog_learn extends base {
    protected function init(){
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
    public static function get_name(){
        return "Hours log record created";
    }
    public function get_description(){
        return "The user with id '".$this->userid."' created a hours log record for their course with id '".$this->courseid."'";
    }
    public function get_url(){
        return new \moodle_url('/local/hourslog/learner_hourslog.php?cid='.$this->courseid);
    }
    public function get_id(){
        return $this->objectid;
    }
}