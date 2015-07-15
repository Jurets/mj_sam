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

require_once($CFG->libdir.'/formslib.php');

class enrol_self_edit_form extends moodleform {

    function definition() {
        if (!isset($this->_customdata)) return true;
        
        $mform = $this->_form;

        list($instance, $plugin, $context, $course, $groups) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_survey'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_survey'), $options);
        $mform->addHelpButton('status', 'status', 'enrol_survey');
        $mform->setDefault('status', $plugin->get_config('status'));

        // role
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('defaultrole', 'role'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid'));

        // group
        $selected = !empty($instance->customchar1) ? array_values(explode(',', $instance->customchar1)) : array();
        /*$label = 'Groups';
        foreach ($groups as $groupid=>$groupname) {
            $checkbox = $mform->addElement('advcheckbox', 'groupid'.$groupid, $label, $groupname, array('group' => 1));
            $mform->setDefault('groupid'.$groupid, in_array($groupid, $selected));
            if (!empty($label)) {
                $mform->addHelpButton('groupid'.$groupid, 'groupid', 'enrol_survey');
            }
            $label = null;
        }
        $this->add_checkbox_controller(1);*/
        
        $group_array = array();

        //creating days of the week
        foreach ($groups as $groupid=>$groupname) {
            $group_array[] =& $mform->createElement('advcheckbox', $groupid, '', $groupname);
            $mform->setDefault("groups[$groupid]", in_array($groupid, $selected));
        }
        //display them into one row
        $mform->addGroup($group_array, 'groups', 'Groups', array(' '), true);

        // version with 'select'
        //$select = $mform->addElement('select', 'groupid', get_string('defaultgroupname', 'group'), $groups);
        //$select->setMultiple(true);
        //$select->setSelected($selected);
        //$mform->addHelpButton('groupid', 'groupid', 'enrol_survey');

        $options = array(1 => get_string('yes'), 0 => get_string('no'));
        $mform->addElement('select', 'customint1', get_string('isdeleteanswers', 'enrol_survey'), $options);
        $mform->addHelpButton('customint1', 'isdeleteanswers', 'enrol_survey');
        $mform->setDefault('customint1', 0/*$plugin->get_config('customint1')*/);
		
        $mform->addElement('textarea', 'customtext1', get_string('editdescription', 'enrol_survey'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
        /*if (!empty($instance->customchar1)) {
            $selected = array_values(explode(',', $instance->customchar1));
            foreach ($selected as $item) {
                $mform->getElement('groupid'.$item)->setValue(1);
            }
        }*/
        
    }
}