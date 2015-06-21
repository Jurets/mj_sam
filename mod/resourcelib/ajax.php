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

$cm = get_coursemodule_from_id('resourcelib', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
//$resourcelib = $DB->get_record('resourcelib', array('id'=>$cm->instance), '*', MUST_EXIST);

//check for session
require_sesskey();

// get course and context
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/resourcelib:view', $context);

$return = array('success'=>false);

//analize action name param
switch ($action) {
    // save resource viewing to log
    case 'logview':
        try {
            $event = \mod_resourcelib\event\resource_viewed::create(array(
                'objectid' => $objectid, //$PAGE->cm->instance,
                'context' => $context,
            ));
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
        break;
        
    case 'bookmark':
        $resource = $DB->get_record('resource_items', array('id' => $objectid), 'id, url, title, internal_title', MUST_EXIST);
        // get bookmark 
        $bookmarkid = optional_param('bookmarkid', false, PARAM_INT);
        $bookmark = resourcelib_bookmark($resource->id, ($bookmarkid ? $bookmarkid : null));
        $return['success'] = (bool)$bookmark;
        $return['html'] = resourcelib_button_bookmark($resource->id, $bookmark);
        break;
        
    case 'unbookmark':
        if ($bookmarkid = optional_param('bookmarkid', false, PARAM_INT)) {
            if ($bookmark = resourcelib_unbookmark($bookmarkid)) {
                $return['html'] = resourcelib_button_bookmark($objectid, $bookmark);
                $return['success'] = true;
            }
        }
        break;
}

echo json_encode($return); //return response
die;
