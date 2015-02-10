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

        //list($instance, $plugin, $context) = $this->_customdata;
        $instance = $this->_customdata['instance'];
        $plugin = $this->_customdata['plugin'];
        $questions = isset($this->_customdata['questions']) ? $this->_customdata['questions']: array();

        //$mform->addElement('header', 'header', get_string('pluginname', 'enrol_apply'));

        //$mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        //$mform->setType('name', PARAM_TEXT);

        //$options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
        //                 ENROL_INSTANCE_DISABLED => get_string('no'));
        // $mform->addElement('select', 'status', get_string('status', 'enrol_apply'), $options);
        //$mform->addHelpButton('status', 'status', 'enrol_apply');
        //$mform->setDefault('status', $plugin->get_config('status'));

        /*if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }*/
        //$mform->addElement('select', 'roleid', get_string('defaultrole', 'role'), $roles);
        //$mform->setDefault('roleid', $plugin->get_config('roleid'));

        //$mform->addElement('textarea', 'customtext1', get_string('editdescription', 'enrol_apply'));
    
        //DebugBreak();
        $instanceid = $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $instanceid->setValue($instance->courseid);
        
        $courseid = $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $courseid->setValue($instance->id);

        //$this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        ///////$this->set_data($instance);
        
        // show question items (survey)
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
                        $radioarray[] =& $mform->createElement('radio', $question->name, $label, $item, $key, array()/*$attributes*/);
                    }
                }
                $mform->addGroup($radioarray, 'radioar', $label, array(' '), false);
            } else if ($question->type == 'select') {
                $items = array('0'=>'');
                if (isset($question->items) && is_array($question->items)) {
                    foreach($question->items as $key=>$value) {
                            $items[$key] = $value;
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
    //$DB->get_records(...);
    
    //dummy
    $result = array();
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
    $result[] = $question;
    // result
    return $result;
}