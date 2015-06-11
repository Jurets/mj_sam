<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Process ajax requests
 *
 * @copyright Jurets
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('lib.php');
require_once('locallib.php');

//process input params
$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$sesskey = optional_param('sesskey', false, PARAM_TEXT);
$objectid = optional_param('objectid', false, PARAM_INT);

$cm = get_coursemodule_from_id('videoresource', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

//check for session
require_sesskey();

// get course and context
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoresource:view', $context);

$return = array('success'=>false);

$classes = array(
    'logpodcast'=>'\mod_videoresource\event\podcast_viewed',
    'logtranscript'=>'\mod_videoresource\event\transcript_viewed',
    'logchapter'=>'\mod_videoresource\event\chapter_clicked',
    'logvideoplay'=>'\mod_videoresource\event\video_played',
    'logvideopause'=>'\mod_videoresource\event\video_paused',
);

// analize action value
if (isset($classes[$action])) { // --------- if event
    //analize action name param
    $classname = $classes[$action];//'\mod_videoresource\event\chapter_clicked';
    // create event instance
    $event = $classname::create(array(
        'objectid' => $objectid, //$PAGE->cm->instance,
        'context' => $context,
    ));
    try {
        $event->add_record_snapshot('course', $PAGE->course);
        // In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
        ////////$event->add_record_snapshot($PAGE->cm->modname, $activityrecord);
        $event->trigger();
        $return['success'] = true;
    } catch (moodle_exception $e) {
        //any exception process
        $return['success'] = false;  //set false to success flag
        $return['error'] = $e->a;    //set error text
    }
} else if ($action == 'bookmark'){ // ------------- if bookmark
    $url = new moodle_url($CFG->wwwroot.'/mod/videoresource/view.php', array('id' => $cm->id));
    $bookmark = $DB->get_record('resbookmarks', array('user_id'=>$USER->id, 'url'=>$url->out(false)));
    // check: if bookmark already exists
    if ($bookmark) {
        videoresource_delete_bookmark($id);
        $return['html'] = videoresource_button_bookmark(false);
        $return['success'] = true;
    } else {
        $videoresource  = $DB->get_record('videoresource', array('id' => $cm->instance), 'name', MUST_EXIST);
        $inserted_id = videoresource_add_bookmark($url->out(false), $videoresource->name);
        $return['success'] = (bool)$inserted_id;
        $return['html'] = videoresource_button_bookmark(true);
    }
} else {
    $return['success'] = false;  //set false to success flag
}

echo json_encode($return); //return response
die;
