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
 * The main videoresource configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_videoresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

require_once('locallib.php');


/**
 * Module instance settings form
 */
class mod_videoresource_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('videoresourcename', 'videoresource'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'videoresourcename', 'videoresource');

        // Adding the standard "intro" and "introformat" fields.
        $this->add_intro_editor();
        
        //group of resourcelib elements
        //$mform->addElement('header', 'videoresourcefieldset', get_string('videoresourcefieldset', 'videoresource'));
        //$items = videoresource_get_videos_select();  //get list of videos
        //$select = $mform->addElement('select', 'resource_videos_id', get_string('list'), $items);
        //$mform->setType('resource_videos_id', PARAM_INT); //Set type of element
        //$mform->addHelpButton('resource_videos_id', 'listfield', 'videoresource');
        //$mform->addRule('list_id', get_string('missingname'), 'required', null, 'client');
        //$mform->addElement('textarea', 'activity', get_string('secondary_description', 'videoresource'), array('rows'=>3, 'style'=>'width: 100%'));
        //$mform->setType('activity', PARAM_TEXT); //Set type of element
        //$mform->setExpanded('videoresourcefieldset');

        //$mform->addElement('modgrade', 'scale', get_string('grade'), false);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
