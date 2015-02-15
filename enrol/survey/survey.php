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
require_once($CFG->dirroot.'/enrol/renderer.php');
require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->dirroot.'/lib/outputcomponents.php');
require_once ('lib.php');

$site = get_site();
//$systemcontext = context_system::instance();
//DebugBreak();
$id = required_param('id', PARAM_INT); // course id
$course = $DB->get_record ('course', array ('id' => $id ), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
//require_capability ( 'moodle/course:enrolreview', $context );

$PAGE->set_url('/enrol/apply.php', array('id' => $course->id ));
//$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('confirmusers', 'enrol_survey'));
$PAGE->set_title("$site->shortname: " . get_string('confirmusers', 'enrol_apply'));

//include form for survey
require_once("$CFG->dirroot/enrol/survey/locallib.php");

$plugin = enrol_get_plugin('survey');
$instanceid = optional_param('instance', 0, PARAM_INT);
$instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'survey', 'id'=>$instanceid), '*', MUST_EXIST);

// get list of questions
$questions = enrol_survey_get_questions($instance);

$form = new enrol_survey_user_form(null, array(
    'instance'=>$instance, 
    'plugin'=>$plugin, 
    'context'=>$context,
    'questions'=>$questions,
));

if ($data = $form->get_data()) {DebugBreak();
    //$enrol = enrol_get_plugin('self');
    $timestart = time();
    if ($instance->enrolperiod) {
        $timeend = $timestart + $instance->enrolperiod;
    } else {
        $timeend = 0;
    }

    $roleid = $instance->roleid;
    if(!$roleid){
        $role = $DB->get_record_sql("select * from ".$CFG->prefix."role where archetype='student' limit 1");
        $roleid = $role->id;
    }
    // run user enrol procedure
    $plugin->enrol_user($instance, $USER->id, $roleid, $timestart, $timeend,1);
    // redirect to course view main page
    redirect("$CFG->wwwroot/course/view.php?id=$instance->courseid");
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();