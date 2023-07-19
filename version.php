<?php
// This file is part of the hourslog plugin
/**
 * @package     local_hourslog
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_hourslog';
$plugin->version = 10;
$plugin->requires = 2016052314;
$plugin->dependencies = [
    'local_trainingplan' => 12
];
