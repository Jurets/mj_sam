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
 * The main resourcelib configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_resourcelib
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

require_once('locallib.php');


/**
 * Module instance settings form
 */
class mod_resourcelib_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE;
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('resourcelibname', 'resourcelib'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'resourcelibname', 'resourcelib');

        // Adding the standard "intro" and "introformat" fields.
        $this->add_intro_editor();
        
        //group of resourcelib elements
        if (isset($PAGE->cm)) {
            $mform->addElement('header', 'resourcelibfieldset', get_string('resourcelibfieldset', 'resourcelib'));
            $url = new moodle_url('/mod/resourcelib/edit.php', array('id'=>$PAGE->cm->id, 'action'=>'index'));
            $mform->addElement('static', 'editcontent', get_string('editcontentlabel', 'resourcelib'), get_string('editcontentlink', 'resourcelib', $url->out(false)));
            $mform->setExpanded('resourcelibfieldset');
        }
        //$mform->addElement('modgrade', 'scale', get_string('grade'), false);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
