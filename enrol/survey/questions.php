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
$enrolid = optional_param('enrolid', 0, PARAM_INT);
$instance = $DB->get_record('enrol', array('id'=>$enrolid), '*', MUST_EXIST);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash

// get question id
$id = optional_param('id', 0, PARAM_INT); //admin action for mooc-settings

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
$actionMoveDown = 'movedown';
$actionMoveUp = 'moveup';

//URL settings
$returnurl = new moodle_url($CFG->wwwroot.'/enrol/survey/questions.php', array('enrolid'=>$enrolid));
$returnurl = $returnurl->out(false);

$PAGE->set_url('/enrol/survey/questions.php');
//$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($course->fullname);

//$PAGE->navbar->add(get_string('confirmusers', 'enrol_apply'));
$PAGE->set_title("$site->shortname: " . get_string ('manage_questions', 'enrol_survey'));

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
    case $actionIndex:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage_questions', 'enrol_survey'));
        // show add question form
        $url = new moodle_url($returnurl, array('action' => $actionAdd));
        $form = new enrol_survey_addquestion_form($url->out(false), array('enrolid'=>$enrolid));
        $form->display();
        // get list of questions
        $questions = enrol_survey_get_questions($instance);
        //show table with items data
        enrol_survey_show_questions($questions, $returnurl, null, $sort, $dir);
        echo $OUTPUT->footer();
        break;
        
    case $actionAdd:
    case $actionEdit:
        // header string
        $head_str = ($action == $actionAdd) ? get_string('add_question', 'enrol_survey') : get_string('edit_question', 'enrol_survey');
        //
        if ($action == $actionAdd) { //add new type
            $PAGE->navbar->add($head_str);
            $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
            $question = new stdClass();
            $form = new enrol_survey_addquestion_form();
            //$type = optional_param('type', 'text', PARAM_INT);
            if ($data = $form->get_data()) {
                //$type = optional_param('type', 'text', PARAM_INT);
                //$question = null;        //empty data
                $question->type = $data->type;
            }
        } else if (isset($enrolid)){     //edit existing type ($enrolid parameter must be present in URL)
            $PAGE->navbar->add(get_string('edit_question', 'enrol_survey'));
            $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
            $questionid = optional_param('questionid', 0, PARAM_INT);
            $question = enrol_survey_get_one_question($id); //get data from DB
        }
        // run question edit form
        $editform = new enrol_survey_question_form($actionurl->out(false), array(
            'enrolid'=>$enrolid, 
            'question'=>$question,
        )); //create form instance
        if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
            $url = new moodle_url($returnurl, array('action' => $actionIndex));
            redirect($url);
        } else if ($question = $editform->get_data()) {// if form data received
            $question->enrolid = $enrolid;
            $question->courseid = $course->id;
            $success = enrol_survey_save_question($question);
            if ($success) {
                $url = new moodle_url($returnurl, array('action' => $actionIndex));
                redirect($url);
            }
        } 
        
        //show form page
        echo $OUTPUT->header();
        echo $OUTPUT->heading($head_str);
        $editform->display();
        echo $OUTPUT->footer();
        break;
        
    // Move Section Up in section List
    case $actionMoveUp:
    case $actionMoveDown:
        // get section in list
        $question = $DB->get_record('enrol_survey_questions', array('id'=>$id)); //get data from DB
        // build url for return
        $url = new moodle_url($returnurl);
        if (confirm_sesskey()) {
            if ($action == $actionMoveDown)
                $result = enrol_survey_question_move_down($question);  //move down
            else if ($action == $actionMoveUp)
                $result = enrol_survey_question_move_up($question);    //move up
            if (!$result) {
                print_error('cannotmove', 'enrol_survey', $url->out(false), $id);
            }
        }
        redirect($url);
        break;

    case $actionDelete:
        //breadcrumbs
        $str_delete = get_string('delete_question', 'enrol_survey');
        $PAGE->navbar->add($str_delete);
        
        $id = optional_param('id', 0, PARAM_INT);
        
        if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
            $question = $DB->get_record('enrol_survey_questions', array('id'=>$id)); //get data from DB

            if ($confirm != md5($id)) {
                echo $OUTPUT->header();
                echo $OUTPUT->heading($str_delete);
                //before delete do check existing of resources in any section
                /*if ($item->rs_count > 0) {
                    $str = get_string('deletednot', '', $item->title) . ' ' . get_string('resources_exists_in_section', 'resourcelib');
                    echo $OUTPUT->notification($str);
                } else*/ {
                    $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$question->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                }
                echo $OUTPUT->footer();
            } else if (data_submitted() /*&& !$data->deleted*/){
                if (enrol_survey_delete_question($question)) {
                    $url = new moodle_url($returnurl, array('action' => $actionIndex));
                    redirect($url);
                } else {
                    echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name));
                }
            }
        }
        break;
        
}