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
$plugin = enrol_get_plugin('survey');

// get course
$course = $DB->get_record('course', array('id'=>$enrol->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

/// Security
require_login($course);
if ($USER->id <> $user->id) {
    require_capability('enrol/survey:manage', context_system::instance());
}

//actions list
$actionIndex = 'index';
$actionView = 'view';
$actionEdit = 'edit';

//URL settings
$returnurl = new moodle_url($CFG->wwwroot.'/enrol/survey/answers.php', array('action'=>$action, 'ue'=>$ue_id));

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
    case $actionEdit:
        require_capability('enrol/survey:manage', context_system::instance());
        // get answer
        $answer_id = optional_param('answerid', 0, PARAM_INT);
        if ($answer = $DB->get_record('enrol_survey_answers', array('id'=>$answer_id))) {
            // get question
            $question = enrol_survey_get_one_question($answer->questionid);
            
            // put question into array (for work with survey form)
            $questions[$question->id] = $question;
            $returnurl->param('answerid', $answer_id);
            $form = new enrol_survey_user_form($returnurl->out(false), array(
                'enrol'=>$enrol, 
                'plugin'=>$plugin, 
                'context'=>$context,
                'questions'=>$questions,
                'answer'=>$answer,
            ));
            if ($enroldata = $form->get_data()) {
                //$answer->answertext = $data->id;
                $user_answers = $enroldata->questions;
                $user_answer = $user_answers[$question->id];
                // analize question type
                $option = null;
                if (isset($question->items) && is_array($question->items) && !empty($question->items)) {
                    $items = $question->items;          //check: if options exists
                    if (isset($items[$user_answer]))    // check: if option selected
                        $option = $items[$user_answer];
                } 
                if (isset($option)) {
                    $answer->answertext = $option->label;
                    $answer->optionid = $option->id;
                } else {
                    $answer->answertext = $user_answer;
                    $answer->optionid = null;
                }
                if ($DB->update_record('enrol_survey_answers', $answer)) {
                    $url = new moodle_url($CFG->wwwroot.'/enrol/survey/answers.php', array('action'=>$actionView, 'ue'=>$ue_id));
                    redirect($url->out(false));
                }
            }
        }
        // show survey form page (with one question) for user answer editing
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('edit_user_answers', 'enrol_survey'));
        $form->display();
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
            $str = $timecreated ? date('Y-m-d h:i:s', $timecreated) : 'Non answered';
            echo $OUTPUT->heading($str, 3, 'helptitle', 'timecreated'.$timecreated);
            $table = new html_table();
            $table->head = array(
                get_string('question_text', 'enrol_survey'),
                get_string('question_type', 'enrol_survey'),
                get_string('is_required', 'enrol_survey'),
                get_string('answer_text', 'enrol_survey'),
            );
            $stredit = get_string('edit');
            foreach ($answers as $answer) {
                $buttons_column = array(
                    //create_editbutton($returnurl->out(false), get_string('edit'), $answer->answerid)
                    html_writer::link(
                        new moodle_url($returnurl->out(false), array('action'=>'edit', 'answerid'=>$answer->answerid)), 
                        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>$stredit, 'class'=>'iconsmall')), 
                        array('title'=>$stredit)
                    ),
                );
                $data = array(
                    $answer->questiontext, 
                    $answer->questiontype, 
                    $answer->required ? html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/check'))) : '', 
                    $answer->answertext, 
                );
                if (has_capability('enrol/survey:manage', context_system::instance())) {
                    $data[] = implode(' ', $buttons_column);
                }
                $table->data[] = $data;
            }
            echo html_writer::table($table);
        }
        echo $OUTPUT->footer();
        break;
        
    case $actionIndex:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('user_answers', 'enrol_survey'));
        // ...
        echo $OUTPUT->footer();
        break;
        
}