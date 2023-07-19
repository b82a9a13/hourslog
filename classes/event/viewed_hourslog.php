<?php
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

namespace local_hourslog\event;
use core\event\base;
defined('MOODLE_INTERNAL') || die();

class viewed_hourslog extends base {
    protected function init(){
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }
    public static function get_name(){
        return "Hours log page viewed";
    }
    public function get_description(){
        return "The user with id '".$this->userid."' viewed hours logs for the user with id '".$this->relateduserid."' and for the course with id '".$this->courseid."'";
    }
    public function get_url(){
        return new \moodle_url('/local/hourslog/teacher_hourslog.php?cid='.$this->courseid.'&uid='.$this->relateduserid.'&e='.$this->other);
    }
    public function get_id(){
        return $this->objectid;
    }
}