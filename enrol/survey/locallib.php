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
 * @package    enrol_survey
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot.'/group/lib.php');

class enrol_survey_enrol_form extends moodleform {
    protected $instance;

    /**
     * Overriding this function to get unique form id for multiple self enrolments
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    public function definition() {
        //global $DB, $USER, $OUTPUT;
        
        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('self');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'selfheader', $heading);

        /*if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            //TODO: maybe we should tell them they are already enrolled, but can not access the course
            //return null;
            $message = $OUTPUT->notification(get_string('notification', 'enrol_survey'));
            $mform->addElement('static', 'error', $message);
            return;
        }*/
        
        if ($instance->password) {
            $heading = $plugin->get_instance_name($instance);
            $mform->addElement('header', 'selfheader', $heading);
            //change the id of self enrolment key input as there can be multiple self enrolment methods
            $mform->addElement('passwordunmask', 'enrolpassword', get_string('password', 'enrol_self'),
                    array('id' => $instance->id."_enrolpassword"));
        } else {
            // nothing?
        }
		$mform->addElement('html', '<p>'.$instance->customtext1.'</p>');
        //$mform->addElement('textarea', 'applydescription', get_string('comment', 'enrol_survey'),'cols="80"');
        $this->add_action_buttons(false, get_string('enrolme', 'enrol_self'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        if ($instance->password) {
            if ($data['enrolpassword'] !== $instance->password) {
                if ($instance->customint1) {
                    $groups = $DB->get_records('groups', array('courseid'=>$instance->courseid), 'id ASC', 'id, enrolmentkey');
                    $found = false;
                    foreach ($groups as $group) {
                        if (empty($group->enrolmentkey)) {
                            continue;
                        }
                        if ($group->enrolmentkey === $data['enrolpassword']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // we can not hint because there are probably multiple passwords
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self');
                    }
                } else {
                    $plugin = enrol_get_plugin('self');
                    if ($plugin->get_config('showhint')) {
                        $textlib = textlib_get_instance();
                        $hint = $textlib->substr($instance->password, 0, 1);
                        $errors['enrolpassword'] = get_string('passwordinvalidhint', 'enrol_self', $hint);
                    } else {
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self');
                    }
                }
            }
        }
        return $errors;
    }
}

/**
* form for starting of new question process
*/
class enrol_survey_addquestion_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        
        $enrolid = $this->_customdata['enrolid'];
        
        $enrol = $mform->addElement('hidden', 'enrolid');
        $mform->setType('enrolid', PARAM_INT);
        $enrol->setValue($enrolid);
        // question types
        $qtypes = array('text'=>'Text Question', 'select'=>'Select from a list', 'radio'=>'Radio Buttons', 'selectother'=>'Select from a list w/ Other Option', 'group'=>'Select Group');
        // group of dropdown and submit button
        $addqgroup = array();
        $addqgroup[] =& $mform->createElement('select', 'type', '', $qtypes);
        // The 'sticky' type_id value for further new questions.
        /*if (isset($SESSION->questionnaire->type_id)) {
                $mform->setDefault('type_id', $SESSION->questionnaire->type_id);
        }*/
        $addqgroup[] =& $mform->createElement('submit', 'addqbutton', get_string('addselqtype', 'enrol_survey'));
        $mform->addGroup($addqgroup, 'addqgroup', '', ' ', false);
    }
}

/**
* form for question editing
*/
class enrol_survey_question_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        // set main data
        $enrolid = $this->_customdata['enrolid'];
        $question = $this->_customdata['question'];

        if (isset($this->_customdata['group_questions'])/* && is_array($this->_customdata['types'])*/) {
            $_items = $this->_customdata['group_questions'];
            $group_questions = array('0'=>''); //add empty item to the begin of options
            foreach($_items as $key=>$value) {$group_questions[$key] = $value;}
        } else {
            $group_questions = array();
        }
        
        //
        if (isset($question->id)){
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
        } else if (!isset($question->type) || empty($question->type)) {
            if ($this->is_submitted()) {
                $data = $this->get_submitted_data();
                $question->type = $data->type;
            } else if (isset($_POST['type'])) {
                $question->type = $_POST['type'];  //crutch... !TODO: use moodleform methods instedd $_POST
            } else {
                $question->type = 'text';
            }
        }

        $enrol = $mform->addElement('hidden', 'enrolid');
        $mform->setType('enrolid', PARAM_INT);
        $enrol->setValue($enrolid);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_TEXT);
        
        //$mform->addElement('text', 'group_label', get_string('group'), array('size'=>'30', 'maxlength'=>'30'));
        //$mform->setType('group_label', PARAM_TEXT);
        //$mform->addHelpButton('group_label', 'optional_group', 'enrol_survey');

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'30', 'maxlength'=>'30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'optional_name', 'enrol_survey');
        // question text
        $mform->addElement('text', 'label', get_string('question_text', 'enrol_survey'), array('style'=>'width: 100%'));
        $mform->setType('label', PARAM_TEXT);
        $mform->addRule('label', get_string('missing_value', 'enrol_survey'), 'required', null, 'client');
        $mform->addHelpButton('label', 'required_question', 'enrol_survey');
        // if not group type 
        if ($question->type <> 'group') {
            $stryes = get_string('yes');
            $strno  = get_string('no');
            $reqgroup = array();
            $reqgroup[] =& $mform->createElement('radio', 'required', '', $stryes, '1');
            $reqgroup[] =& $mform->createElement('radio', 'required', '', $strno, '0');
            $mform->addGroup($reqgroup, 'required', get_string('required', 'enrol_survey'), ' ', false);
            $mform->addRule('required', get_string('missing_value', 'enrol_survey'), 'required', null, 'client');
            //$mform->addHelpButton('reqgroup', 'required', 'questionnaire');
            
            // show possible answers
            if ($question->type <> 'text') {
                $answers = $mform->addElement('textarea', 'answers', get_string('possible_answers', 'enrol_survey'), array('rows'=>8, 'style'=>'width: 100%')); // Add elements to your form
                $mform->setType('answers', PARAM_TEXT);                   //Set type of element
                $mform->addRule('answers', get_string('missing_value', 'enrol_survey'), 'required', null, 'client');
                $mform->addHelpButton('answers', 'required_answers', 'enrol_survey');
                
                if (isset($question->id)) {
                    $answers_text = '';
                    foreach ($question->items as $item) {
                        $answers_text .= $item->label . "\n";
                    }
                    $answers->setValue($answers_text);
                }
            }
            // parent question (if group)
            //if (!empty($group_questions))
            $mform->addElement('select', 'parentid', get_string('group'), $group_questions/*, array('size'=>'30', 'maxlength'=>'30')*/);
            $mform->setType('parentid', PARAM_TEXT);
            $mform->addHelpButton('parentid', 'optional_group', 'enrol_survey');
        }
        
        $this->set_data($question);   // set form data
        $this->add_action_buttons();  // form buttons
    }
}

/**
*  form for user answering
*/
class enrol_survey_user_form extends moodleform {

    private $mform;
    
    // form definition function
    function definition() {
        $this->mform = $this->_form;

        // get main instances
        $enrol = $this->_customdata['enrol'];
        $plugin = $this->_customdata['plugin'];
        $questions = isset($this->_customdata['questions']) ? $this->_customdata['questions']: array();
        if (isset($this->_customdata['answer'])) {
            $answer = $this->_customdata['answer'];
        } else {
            $answer = null;
        }

        //$mform->addElement('header', 'header', get_string('pluginname', 'enrol_apply'));

        $instanceid = $this->mform->addElement('hidden', 'id');
        $this->mform->setType('id', PARAM_INT);
        $instanceid->setValue($enrol->courseid);
        
        $courseid = $this->mform->addElement('hidden', 'enrolid');
        $this->mform->setType('enrolid', PARAM_INT);
        $courseid->setValue($enrol->id);
        
        /// Show question items (survey)
        $item_num = 0;
        // rebuild question list according groups
        foreach($questions as $id=>$question) {
            if (isset($question->parentid) && !empty($question->parentid)) {
                if (isset($questions[$question->parentid])) {
                    $parent = &$questions[$question->parentid];
                }
                if (isset($parent)) {
                    if (!isset($parent->children)) {
                        $parent->children = array();
                    }
                    $parent->children[] = $question;
                    unset($questions[$id]);
                }
            }
        }
        // show question cycle
        foreach($questions as $key=>$question) {
            //build label
            $isShow = ($question->type != 'group') || ($question->type == 'group' && isset($question->children) && is_array($question->children));
            $item_num = $isShow ? ($item_num + 1) : $item_num;
            $label = $item_num . '. ' . $question->label;
            if ($isShow) {
                $this->mform->addElement('header', 'group'.$question->id, $label, array('style'=>'font-size: 14px;'));
                $this->mform->setExpanded('group'.$question->id);
            }
            // analize - if group type
            if ($question->type == 'group' && isset($question->children) && is_array($question->children)) {
                foreach($question->children as $child_question) {
                    $this->one_question($child_question, $child_question->label, $answer);
                }
            } else {
                $this->one_question($question, '', $answer);
            }
            if ($isShow) {
                $this->mform->addElement('static', 'endgroup'.$question->id, '');
                $this->mform->closeHeaderBefore('endgroup'.$question->id);
            }
        }
        $this->add_action_buttons(true, ($enrol->id ? null : get_string('addinstance', 'enrol'))); // add buttons
    }
    
    // create one question (not group type)
    private function one_question($question, $label, $answer) {
        //build name of form element
        $name = "questions[$question->id]";
        // analize options existance
        if (isset($question->items) && is_array($question->items)) {
            $items = $question->items;
        } else {
            $items = null;
        }
        // analize question type
        if ($question->type == 'radio') {
            if (isset($question->items) && is_array($question->items)) {
                $radioarray = array();
                foreach($question->items as $key=>$item) {
                    $radioarray[] =& $this->mform->createElement('radio', $name, '', $item->label, $item->id, array()/*$attributes*/);
                }
                if (isset($answer) && is_object($answer)) { //set answer (choice) if present
                    $this->mform->setDefault($name, $answer->optionid);
                }
            }  // must be same name in radio elements and in group
            $this->mform->addGroup($radioarray, $name, $label, array('<br>'), false);
        } else if ($question->type == 'select') {
            $items = array(''=>'');
            if (isset($question->items) && is_array($question->items)) {
                foreach($question->items as $key=>$value) {
                    $items[$value->id] = $value->label;
                }
                if (isset($answer) && is_object($answer)) { //set answer (choice) if present
                    $this->mform->setDefault($name, $answer->optionid);
                }
            }
            $this->mform->addElement('select', $name, $label, $items);
        } else if ($question->type == 'text') {
            $this->mform->addElement('text', $name, $label);
            if (isset($answer) && is_object($answer)) { //set answer (choice) if present
                $this->mform->setDefault($name, $answer->answertext);
            }
        }
        //    $mform->addHelpButton($question->name, 'status', 'enrol_apply');
        if (isset($question->default) && !empty($question->default)) {
            $this->mform->setDefault($question->name, $question->default);
        }
        $this->mform->setType($name, PARAM_TEXT);
        if (isset($question->required) && $question->required) {
            //$mform->addRule($question->name, get_string('missinanswer', 'enrol_survey'), 'required', null, 'client');
            $this->mform->addRule($name, get_string('missinanswer', 'enrol_survey'), 'required');
        }
    }
}

/**
* Show add button (usually near data table)
* 
* @param mixed $url
* @param mixed $label
* @param mixed $attributes
*/
function enrol_survey_show_addbutton($url, $label, $attributes = array('class' => 'mdl-right')) {
    global $OUTPUT;
    echo html_writer::start_tag('div', $attributes);
    echo html_writer::tag('a', $OUTPUT->pix_icon('t/add', '') . ' ' . $label, array('href' => $url->out(false)));
    echo html_writer::end_tag('div');
}


/**
* show Resource items in HTML table
* 
* @param mixed $items - array of resource instances
*/
function enrol_survey_show_questions($items, $returnurl, $buttons = null, $sort = '', $dir = '') {
    global $OUTPUT;
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    
    if (!$items || empty($items)) {
        echo $OUTPUT->notification(get_string('no_questions', 'enrol_survey'), 'redirectmessage');
    } else {
        if (!isset($buttons)) //default buttons
            $buttons = array('delete'=>'delete', 'edit'=>'edit');
        
        // take sorting column, if need
        $title_column = enrol_survey_get_column_title($returnurl, 'label', get_string('label', 'enrol_survey'), $sort, $dir);

        // build table header
        $table = new html_table();
        $table->head = array( //sorting in first column!
            $title_column,
            get_string('name'), 
            get_string('group'), 
            get_string('type', 'enrol_survey'),
            get_string('is_required', 'enrol_survey'),
            get_string('actions'),
            
        );
        
        $first_item = reset($items);
        $last_item = end($items);
        
        foreach ($items as $item) {
            $buttons_column = array();
            if (isset($item->sort_order)) {
                // Move up.
                if ($item->sort_order != $first_item->sort_order) {
                    $buttons_column[] = get_action_icon($returnurl . '&action=moveup&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
                } else {
                    $buttons_column[] = get_spacer();
                }
                // Move down.
                if (isset($item->sort_order) && ($item->sort_order != $last_item->sort_order)) {
                    $buttons_column[] = get_action_icon($returnurl . '&action=movedown&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
                } else {
                    $buttons_column[] = get_spacer();
                }
            }
            if (key_exists('edit', $buttons))
                $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
            if (key_exists('delete', $buttons))
                $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
            $table->data[] = array(
                $item->label, 
                $item->name, 
                $item->group_name, 
                $item->type,
                $item->required ? html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/check'))) : '', 
                implode(' ', $buttons_column) 
            );
        }
        echo html_writer::table($table);
    }
}

/**
* take sorting column, if need
* 
* @param mixed $returnurl
* @param mixed $title
* @param mixed $sort
* @param mixed $dir
* @return string
*/
function enrol_survey_get_column_title($returnurl, $columnname, $columntitle, $sort = '', $dir = '') {
    global $OUTPUT;
    
    if (!empty($sort)) {
        if ($sort != $columnname) {
            $columnicon = '';
            $columndir = "ASC";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
            $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";
            $dir = !empty($dir) ? $dir : 'ASC';
            $columndir = $dir == "ASC" ? "DESC":"ASC";
        }
        $column_title = html_writer::link(new moodle_url($returnurl, array('sort'=>$columnname, 'dir'=>$columndir)), $columntitle . $columnicon);
    } else {
        $column_title = $columntitle;
    }
    return $column_title;
}

/**
* create a delete button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @return string
*/
function create_deletebutton($url, $action, $id) {
    global $OUTPUT;
    
    $strdelete = get_string('delete');
    return html_writer::link(
        new moodle_url($url, array('action'=>$action, 'id'=>$id, 'sesskey'=>sesskey())), 
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), 
        array('title'=>$strdelete)
    );
}

/**
* create a edit button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @return string
*/
function create_editbutton($url, $action, $id) {
    global $OUTPUT;
    
    $stredit   = get_string('edit');
    return html_writer::link(
        new moodle_url($url, array('action'=>'edit', 'id'=>$id)), 
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>$stredit, 'class'=>'iconsmall')), 
        array('title'=>$stredit)
    );
}

//
function get_action_icon($url, $icon, $alt, $tooltip) {
    global $OUTPUT;
    return '<a title="' . $tooltip . '" href="'. $url . '">' .
            '<img src="' . $OUTPUT->pix_url('t/' . $icon) . '" class="iconsmall" alt="' . $alt . '" /></a> ';
}

function get_spacer() {
    global $OUTPUT;
    return '<img src="' . $OUTPUT->pix_url('spacer') . '" class="iconsmall" alt="" /> ';
}

/**
* get list of questions, wich attached to course enrol plugin
* 
* @param mixed $instance - instance of enrol
*/
function enrol_survey_get_questions($instance = null) {
    global $DB;
    try {
        $transaction = $DB->start_delegated_transaction();    // start transaction         //delete:
        // get questions
        $sql = 'SELECT q.id, q.name, q.label, q.type, q.required, q.sort_order, q.parentid, qg.name as group_name
                FROM {enrol_survey_questions} q LEFT JOIN
                     {enrol_survey_questions} qg ON qg.id = q.parentid 
                WHERE q.enrolid = ?
                ORDER BY q.sort_order ASC';
        $questions = $DB->get_records_sql($sql, array($instance->id));
        if ($questions) {
            $sql = 'SELECT o.*
                    FROM {enrol_survey_options} o LEFT JOIN
                         {enrol_survey_questions} q ON q.id = o.questionid 
                    WHERE q.enrolid = ?';
            // get all options, which contains in all questions of enrol
            $options = $DB->get_records_sql($sql, array($instance->id));
            foreach ($options as $option) { //then put each option on appropriate question
                if (!isset($questions[$option->questionid]->items)) {
                    $questions[$option->questionid]->items = array();
                }
                $items = &$questions[$option->questionid]->items;
                $items[$option->id] = $option;
            }
        } else {
            $questions = array();
        }
        $transaction->allow_commit();
    } catch(Exception $e) {
        $transaction->rollback($e);
        $questions = false;
    } 
    return $questions; // result
}

/**
* get one question
* 
* @param mixed $instance - instance of enrol
*/
function enrol_survey_get_one_question($id = null) {
    global $DB;
    try {
        $transaction = $DB->start_delegated_transaction();    // start transaction         //delete:
        if ($question = $DB->get_record('enrol_survey_questions', array('id'=>$id))) {
            $question->items = $DB->get_records('enrol_survey_options', array('questionid'=>$question->id));
        } else {
            $question = false;
        }
        $transaction->allow_commit();
    } catch(Exception $e) {
        $transaction->rollback($e);
        $question = false;
    } 
    return $question; // result
}

/**
* Delete question
* 
* @param mixed $question
*/
function enrol_survey_delete_question($question = null) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($question, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in enrol_survey_delete_question() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$question = $DB->get_record('enrol_survey_questions', array('id' => $question->id))) {
        debugging('Attempt to delete unknown Question.');
        return false;
    }
    try {
        $transaction = $DB->start_delegated_transaction();    // start transaction         
        // null parent id in child questions
        $DB->execute('UPDATE {enrol_survey_questions} SET parentid = NULL WHERE parentid = ?', array($question->id));
        $DB->delete_records('enrol_survey_options', array('questionid' => $question->id)); // delete choices
        $DB->delete_records('enrol_survey_questions', array('id' => $question->id));       // delete question
        $transaction->allow_commit();
        $success = true;
    } catch(Exception $e) {
        $transaction->rollback($e);
        $success = false;
    } 
    return $success;
}

/**
* Move question down 
* 
* @param mixed $question
* @return bool
*/
function enrol_survey_question_move_down($question) {
    return move_question($question, 'down');
}

/**
* Move question up 
* 
* @param mixed $question
* @return bool
*/
function enrol_survey_question_move_up($question) {
    return move_question($question, 'up');
}

/**
* Move question
* 
* @param mixed $question
* @param mixed $direction - direction of moving ("down" or "up")
* @return bool
*/
function move_question($question, $direction = 'down') {
    global $DB;
    $sql = 'SELECT * FROM {enrol_survey_questions}
            WHERE enrolid = ? 
                  AND sort_order ' . ($direction == 'down' ? '>' : '<') . ' ?
            ORDER BY sort_order ' . ($direction == 'down' ? 'ASC' : 'DESC') . '
            LIMIT 1';
    $other_question = $DB->get_record_sql($sql, array($question->enrolid, $question->sort_order));
    if (!$other_question) { //if other question not exists - return false
        return false;
    }
    $result = $DB->set_field('enrol_survey_questions', 'sort_order', $other_question->sort_order, array('id' => $question->id))
           && $DB->set_field('enrol_survey_questions', 'sort_order', $question->sort_order,  array('id' => $other_question->id));
    return $result;
}


/**
* Save question (insert or edit)
* 
* @param mixed $question
*/
function enrol_survey_save_question($question = null) {
    global $DB, $USER;
    $success = false;
    // process possible answer strings
    if (isset($question->answers) && !empty($question->answers)) {
        $form_answers = $question->answers;
        $form_answers = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $form_answers);
        $form_answers = trim($form_answers);
        $form_answers = explode("\n", $form_answers);
    } else {
        $form_answers = array();
    }
    unset($question->answers);unset($question->submitbutton);
    // analize ID of question
    if (isset($question->id)) {//edit question
        //insert question record
        $question->timemodified = time();   //set time
        $question->modifierid = $USER->id;  //set user who created
        try {
            $transaction = $DB->start_delegated_transaction(); 
            // update answers
            $num = 0;
            $db_answers = $DB->get_records('enrol_survey_options', array('questionid'=>$question->id));
            foreach ($db_answers as $db_answer) {
                if (isset($form_answers[$num])) { // if option text is different
                    if ($form_answers[$num] <> $db_answer->label) {
                        $db_answer->label = $form_answers[$num];    //update it
                        $DB->update_record('enrol_survey_options', $db_answer);
                    }
                } else {  // if option not in form response - delete it
                    $DB->delete_records('enrol_survey_options', array('id'=>$db_answer->id));
                }
                $num++;
            }
            // insert new option, if it's there are in form response
            for ($n = $num; $n < count($form_answers); $n++) {
                $answer = new stdClass();
                $answer->questionid = $question->id;
                $answer->label = $form_answers[$n];
                $DB->insert_record('enrol_survey_options', $answer);
            }
            //update question record
            $DB->update_record('enrol_survey_questions', $question);
            $transaction->allow_commit();
            $success = true;
        } catch(Exception $e) {
            $transaction->rollback($e);
            $success = false;
        } 
    } else { //add question
        //insert question record
        $question->timecreated = time();   //set time
        $question->creatorid = $USER->id;  //set user who created
        try {
            $transaction = $DB->start_delegated_transaction(); 
            $question->sort_order = $DB->get_field_select('enrol_survey_questions', 'COALESCE(MAX(sort_order) + 1, 1)', 'enrolid = ?', array($question->enrolid));
            $question_id = $DB->insert_record('enrol_survey_questions', $question);
            //insert answers
            foreach($form_answers as $label) {
                $answer = new stdClass();
                $answer->questionid = $question_id;
                $answer->label = $label;
                $DB->insert_record('enrol_survey_options', $answer);
            }
            $transaction->allow_commit();
            $success = true;
        } catch(Exception $e) {
            $transaction->rollback($e);
            $success = false;
        } 
    }
    return $success;
}

/**
* Save question (insert or edit)
* 
* @param mixed $question
*/
function enrol_survey_save_user_answers($enroldata = null) {//DebugBreak();
    global $DB, $USER;
    //insert answer records
    $timecreated = time();   //set time
    $creatorid = $USER->id;  //set user who created
    // get user answers
    $user_answers = $enroldata->questions;
    if (!is_array($user_answers) || empty($user_answers)) {
        return false;
    }
    // get questions
    $enrol = $DB->get_record('enrol', array('id'=>$enroldata->enrolid), '*', MUST_EXIST);
    $questions = enrol_survey_get_questions($enrol);
    
    try {
        $transaction = $DB->start_delegated_transaction(); 
        //insert answers
        foreach($user_answers as $question_id=>$user_answer) {
            $answer = new stdClass();
            $answer->courseid = $enrol->courseid;
            $answer->enrolid = $enrol->id;
            $answer->userid = $creatorid;
            $answer->timecreated = $timecreated;
            $answer->questionid = $question_id;
            $question = $questions[$question_id];  // question object
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
            $DB->insert_record('enrol_survey_answers', $answer);
        }
        $transaction->allow_commit();
        $success = true;
    } catch(Exception $e) {
        $transaction->rollback($e);
        $success = false;
    } 
    
}

/**
* Delete all user answers
* 
* @param mixed $enrol
*/
function enrol_survey_delete_user_answers($enrol, $user) {
    global $DB;
    return $DB->delete_records('enrol_survey_answers', array('enrolid'=>$enrol->id, 'userid'=>$user->id));
}

/**
* get list of questions, wich attached to course enrol plugin
* 
* @param mixed $instance - instance of enrol
*/
function enrol_survey_get_user_answers($enrol, $user) {
    global $DB;
    $sql = 'SELECT sa.id AS answerid, sq.id AS questionid, sa.timecreated, 
                   sq.label as questiontext, sq.type AS questiontype, sq.required, sa.answertext, sa.optionid
            FROM {enrol_survey_questions} sq LEFT JOIN
                 {enrol_survey_answers} sa ON sa.questionid = sq.id AND sa.userid = ?
            WHERE sq.enrolid = ? AND sq.type <> "group"';
    $answers = array();
    $db_answers = $DB->get_records_sql($sql, array($user->id, $enrol->id));
    foreach ($db_answers as $answer) {
        $answers[$answer->timecreated][] = $answer;
    }
    return $answers;
}

/**
* get ID of group, which contain course, wich consist of minimal count of members
* 
* @param int $courseid - id of course
* return ID of
*/
function enrol_survey_get_group_min_members($courseid) {
    global $DB;
    $sql = 'SELECT g.id, count(*) 
            FROM mdl_groups_members m LEFT JOIN 
                 mdl_groups g ON g.id = m.groupid 
            WHERE g.courseid = ? 
            GROUP BY m.groupid ORDER BY count(*) ASC LIMIT 1';
    $groupid = $DB->get_records_sql($sql, array($courseid));
    return $groupid;
}

/**
* get ID of group, which contain course, wich consist of minimal count of members
* 
* @param int $courseid - id of course
* return ID of
*/
function enrol_survey_add_to_group($enrol) {
    global $DB, $USER;
    if (empty($enrol->customchar1)) return false; // if group config not present in enrol - quit
    //$DB->get_in_or_equal($items, $type=SQL_PARAMS_QM, $prefix='param', $equal=true, $onemptyitems=false)
    $sql = 'SELECT g.id, count(*) 
            FROM mdl_groups_members m LEFT JOIN 
                 mdl_groups g ON g.id = m.groupid 
            WHERE g.courseid = ? AND m.groupid IN ('.$enrol->customchar1.') 
            GROUP BY m.groupid ORDER BY count(*) ASC LIMIT 1';
    // calculate group with smallest count of users
    $groupid = $DB->count_records_sql($sql, array($enrol->courseid));
    // add current user to group
    return groups_add_member($groupid, $USER->id);
}