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

class enrol_survey_user_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        // get main instances
        $instance = $this->_customdata['instance'];
        $plugin = $this->_customdata['plugin'];
        $questions = isset($this->_customdata['questions']) ? $this->_customdata['questions']: array();

        //$mform->addElement('header', 'header', get_string('pluginname', 'enrol_apply'));

        $instanceid = $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $instanceid->setValue($instance->courseid);
        
        $courseid = $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $courseid->setValue($instance->id);
        //DebugBreak();
        /// Show question items (survey)
        foreach($questions as $key=>$question) {
            $label = ($key + 1) . '. ' . $question->label;
            if (isset($question->items) && is_array($question->items)) {
                $items = $question->items;
            } else {
                $items = null;
            }
            
            if ($question->type == 'radio') {
                if (isset($question->items) && is_array($question->items)) {
                    $radioarray = array();
                    foreach($question->items as $key=>$item) {
                        //$radioarray[] =& $mform->createElement('radio', $question->name, '', get_string('yes'), 1, $attributes);
                        $radioarray[] =& $mform->createElement('radio', $question->name, $label, $item->label, $item->id, array()/*$attributes*/);
                        //$radioarray[] =& $mform->createElement('radio', $question->name, $label, $item, $key, array()/*$attributes*/);
                    }
                }
                $mform->addGroup($radioarray, 'radioar', $label, array(' '), false);
            } else if ($question->type == 'select') {
                $items = array(''=>'');
                if (isset($question->items) && is_array($question->items)) {
                    foreach($question->items as $key=>$value) {
                        //$items[$key] = $value;
                        $items[$value->id] = $value->label;
                    }
                }
                $mform->addElement('select', $question->name, $label, $items);
                //$mform->setType($question->name, PARAM_TEXT);
            } else if ($question->type == 'text') {
                $mform->addElement('text', $question->name, $label);
                //$mform->setType($question->name, PARAM_TEXT);
            }
            //if (isset($question->default) && !empty($question->default)) {
            //    $mform->addHelpButton($question->name, 'status', 'enrol_apply');
            //}
            if (isset($question->default) && !empty($question->default)) {
                $mform->setDefault($question->name, $question->default);
            }
            //
            $mform->setType($question->name, PARAM_TEXT);
            if (isset($question->required) && $question->required) {
                $mform->addRule($question->name, get_string('missinanswer', 'enrol_survey'), 'required', null, 'client');
            }
        }
        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));
    }
}

/**
* get list of questions, wich attached to course enrol plugin
* 
* @param mixed $instance - instance of enrol
*/
function enrol_survey_get_questions($instance = null) {
    global $DB;

    //dummy
    /*$result = array();
    //example of Text entry
    $question = new stdClass();
    $question->id = 1;
    $question->name = 'question1';
    $question->type = 'text';
    $question->label = 'Example of Text entry';
    $question->required = true;
    $result[] = $question;
    //example of Dropdown
    $question = new stdClass();
    $question->id = 2;
    $question->name = 'question2';
    $question->type = 'select';
    $question->label = 'Example of Dropdown';
    $question->items = array(1=>'First item', 2=>'Second item', 3=>'Third item');
    $question->required = true;
    $result[] = $question;
    //example of Radio
    $question = new stdClass();
    $question->id = 3;
    $question->name = 'question3';
    $question->type = 'radio';
    $question->label = 'Example of Radio';
    $question->items = array(1=>'First item', 2=>'Second item', 3=>'Third item');
    $question->required = false;
    $result[] = $question;*/
    //return $result;
    //DebugBreak();
    // 
    $questions = $DB->get_records('enrol_survey_questions', array('enrolid'=>$instance->id));
    foreach ($questions as $question) {
        $question->items = array();
        $question->items = $DB->get_records('enrol_survey_options', array('questionid'=>$question->id));
        /*$options = $DB->get_records('enrol_survey_options', array('questionid'=>$question->id));
        foreach ($options as $option) {
            $question->items
        }*/
    }
    // result
    return $questions;
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
        echo $OUTPUT->notification(get_string('no_resources', 'resourcelib'), 'redirectmessage');
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
            get_string('type', 'enrol_survey'),
        );
        
        $first_item = reset($items);
        $last_item = end($items);
        
        /*if (isset($first_item->sort_order)) {
            $table->size[2] = '120px';
        } else {
            $table->size[2] = '80px';
        }*/
        
        foreach ($items as $item) {
            $buttons_column = array();
            if (isset($item->sort_order)) {
                // Move up.
                if ($item->sort_order != $first_item->sort_order) {
                    $buttons_column[] = get_action_icon($returnurl . '?action=moveup&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
                } else {
                    $buttons_column[] = get_spacer();
                }
                // Move down.
                if (isset($item->sort_order) && ($item->sort_order != $last_item->sort_order)) {
                    $buttons_column[] = get_action_icon($returnurl . '?action=movedown&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
                } else {
                    $buttons_column[] = get_spacer();
                }
            }
            if (key_exists('delete', $buttons))
                $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
            if (key_exists('edit', $buttons))
                $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
            $table->data[] = array(
                $item->label, 
                $item->name, 
                $item->type,
                //$type->icon_path, 
                /*html_writer::empty_tag('img', array(
                    'src'=>$item->icon_path, 
                    'alt'=>$item->icon_path, 
                    'title'=>$item->type_name,
                    'class'=>'iconsmall', 
                    'style'=>'width: 30px; height: 30px;')),*/
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
