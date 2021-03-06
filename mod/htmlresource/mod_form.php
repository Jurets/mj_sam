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
 * The main htmlresource configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_htmlresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

require_once('locallib.php');


/**
 * Module instance settings form
 */
class mod_htmlresource_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB, $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('htmlresourcename', 'htmlresource'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'htmlresourcename', 'htmlresource');

        // Adding the standard "intro" and "introformat" fields.
        $this->add_intro_editor();
        
        // --- group of elements
        $mform->addElement('header', 'htmlresourcefieldset', get_string('htmlresourcefieldset', 'htmlresource'));
        // select HTML resource
        $items = htmlresource_get_items_select();  //get list of html
        $select = $mform->addElement('select', 'resource_html_id', get_string('modulename', 'htmlresource'), $items);
        $mform->setType('resource_html_id', PARAM_INT); //Set type of element
        $mform->addHelpButton('resource_html_id', 'listfield', 'htmlresource');

        // -- select Forum
        //list of forums of this course
        $forums = array(''=>''); // add first empty item into select
        foreach ($DB->get_records_menu('forum', array('course'=>$COURSE->id), null, 'id, name') as $id=>$name) $forums[$id] = $name;
        $mform->addElement('select', 'forum_id', get_string('addforum', 'htmlresource'), $forums);
        
        // -- select Forum
        //list of forums of this course
        $questionnaires = array(''=>''); // add first empty item into select
        $sql = '
            SELECT cm.id, q.name 
            FROM {course_modules} cm LEFT JOIN 
                 {questionnaire} q ON q.id = cm.instance LEFT JOIN 
                 {modules} m ON m.id = cm.module
            WHERE cm.course = :course AND m.name = :module
        ';
        foreach ($DB->get_records_sql_menu($sql, array('course'=>$COURSE->id, 'module'=>'questionnaire')) as $key=>$value) $questionnaires[$key]=$value;
        $mform->addElement('select', 'questionnaire_id', get_string('addquestionnaire', 'htmlresource'), $questionnaires);
        
        $mform->setExpanded('htmlresourcefieldset');
        
        //$mform->addElement('modgrade', 'scale', get_string('grade'), false);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
