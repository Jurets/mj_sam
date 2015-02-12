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
 * Survey enrolment plugin.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    enroll
 * @subpackage survey
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require ('../../config.php');
require_once ('lib.php');
require_once('locallib.php');


$site = get_site ();
$systemcontext = context_system::instance();

// get instance of enrolment
$instanceid = optional_param('enrolid', 0, PARAM_INT);
$instance = $DB->get_record('enrol', array('id'=>$instanceid), '*', MUST_EXIST);

// get course
$course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

/// Security
require_login($course);
require_capability('enrol/survey:manage', context_system::instance());

//get action name
$action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
$action = (!empty($action) ? $action : 'index');
//get sort param (if present)
$sort = optional_param('sort', 'title', PARAM_TEXT);
$dir = optional_param('dir', 'ASC', PARAM_TEXT);

//actions list
$actionIndex = 'index';
$actionAdd = 'add';
$actionEdit = 'edit';
$actionDelete = 'delete';

//URL settings
$returnurl = $CFG->wwwroot.'/enrol/survey/manage.php';

$PAGE->set_url ('/enrol/survey/manage.php');
//$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($course->fullname);

//$PAGE->navbar->add(get_string('confirmusers', 'enrol_apply'));
$PAGE->set_title("$site->shortname: " . get_string ('confirmusers', 'enrol_apply'));

//breadcrumbs
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url($CFG->wwwroot.'/enrol/instances.php', array('id'=>$course->id))); 
$PAGE->navbar->add(get_string('enrolname', 'enrol_survey') /*, new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettingresourcelib'))*/); 
/*if ($action == $actionIndex) {
    $PAGE->navbar->add(get_string('manage_items', 'resourcelib'));
} else {
    $PAGE->navbar->add(get_string('manage_items', 'resourcelib'), new moodle_url($returnurl));
}*/


/// ------------- main process --------------
switch($action) {
    case $actionIndex://    DebugBreak();
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage_questions', 'enrol_survey'));
        //add type button
        ///resourcelib_show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('additem', 'resourcelib'));
        // get list of questions
        $questions = enrol_survey_get_questions($instance);
        //show table with items data
        enrol_survey_show_questions($questions, $returnurl, null, $sort, $dir);
        echo $OUTPUT->footer();
        break;
}