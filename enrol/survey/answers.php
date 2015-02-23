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

$site = get_site();
$systemcontext = context_system::instance();

//get action name
$action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
$action = (!empty($action) ? $action : 'index');
//get sort param (if present)
//$sort = optional_param('sort', 'title', PARAM_TEXT);
//$dir = optional_param('dir', 'ASC', PARAM_TEXT);

// get instance of user_enrolments
$ue_id = optional_param('ue', 0, PARAM_INT);
$ue = $DB->get_record('user_enrolments', array('id'=>$ue_id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id'=>$ue->userid), '*', MUST_EXIST);
$enrol = $DB->get_record('enrol', array('id'=>$ue->enrolid), '*', MUST_EXIST);

/*$enrolid = optional_param('enrolid', 0, PARAM_INT);
$instance = $DB->get_record('enrol', array('id'=>$enrolid), '*', MUST_EXIST);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);*/   //md5 confirmation hash

// get question id
//$id = optional_param('id', 0, PARAM_INT); //admin action for mooc-settings

// get course
$course = $DB->get_record('course', array('id'=>$enrol->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

/// Security
require_login($course);
require_capability('enrol/survey:manage', context_system::instance());

//actions list
$actionIndex = 'index';
$actionView = 'view';

//URL settings
$returnurl = new moodle_url($CFG->wwwroot.'/enrol/survey/answers.php', array('action'=>$action));
$returnurl = $returnurl->out(false);

$PAGE->set_url('/enrol/survey/answers.php');
//$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($course->fullname);
$PAGE->set_title("$site->shortname: " . get_string ('user_answers', 'enrol_survey'));
//breadcrumbs
$PAGE->navbar->add(get_string('enrolledusers', 'enrol'), new moodle_url($CFG->wwwroot.'/enrol/users.php', array('id'=>$course->id))); 
$PAGE->navbar->add(get_string('user_answers', 'enrol_survey'));

/// ------------- main process --------------
switch($action) {
    case $actionIndex:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('user_answers', 'enrol_survey'));
        // ...
        echo $OUTPUT->footer();
        break;
        
    case $actionView:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('user_answers', 'enrol_survey') . ': ' . fullname($user, true), 2);
        //get answer data
        $answers_group = enrol_survey_get_user_answers($enrol, $user);
        // show answers by datetime of 
        foreach ($answers_group as $timecreated=>$answers)
        {
            echo $OUTPUT->heading(date('Y-m-d h:i:s', $timecreated), 3, 'helptitle', 'timecreated'.$timecreated);
            $table = new html_table();
            $table->head = array(
                get_string('question_text', 'enrol_survey'),
                get_string('question_type', 'enrol_survey'),
                get_string('is_required', 'enrol_survey'),
                get_string('answer_text', 'enrol_survey'),
            );
            
            foreach ($answers as $answer) {
                $table->data[] = array(
                    $answer->questiontext, 
                    $answer->questiontype, 
                    $answer->required ? html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/check'))) : '', 
                    $answer->answertext, 
                );
            }
            echo html_writer::table($table);
        }
        echo $OUTPUT->footer();
        break;
        
}