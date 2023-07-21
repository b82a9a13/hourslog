<?php
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

namespace local_hourslog\event;
use core\event\base;
defined('MOODLE_INTERNAL') || die();

class viewed_hourslog_pdf_learn extends base {
    protected function init(){
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
    public static function get_name(){
        return "Hours log pdf viewed";
    }
    public function get_description(){
        return "The user with id '".$this->userid."' viewed hours logs pdf for their course with id '".$this->courseid."'";
    }
    public function get_url(){
        return new \moodle_url('/local/hourslog/classes/pdf/hourslog.php?cid='.$this->courseid);
    }
    public function get_id(){
        return $this->objectid;
    }
}