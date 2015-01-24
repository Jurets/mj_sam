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
 * Edit List form for resourcelib
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_videoresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class mod_resourcelib_form_editlist extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['data']) && is_object($this->_customdata['data'])) {
            $data = $this->_customdata['data'];
            //$this->set_data($this->_customdata['data']);
        } else
            $data = null;

        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('text', 'name', get_string('name'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('name', PARAM_TEXT);                   //Set type of element
        //$mform->setDefault('name', 'Please enter name');        //Default value
        $mform->addRule('name', get_string('missingname'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'display_name', get_string('display_name', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('display_name', PARAM_TEXT);                   //Set type of element
        $mform->addRule('display_name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $mform->addElement('textarea', 'heading', get_string('writingheader'), array(
            'rows'=>3, 
            'style'=>'width: 100%',
        )); // Add elements to your form
        $mform->setType('heading', PARAM_TEXT);                   //Set type of element

        $currentpicture = $mform->addElement('static', 'currentpicture', get_string('currentpicture'), array('style'=>'width: 100%'));
        
        //$filepicker = $mform->addElement('filepicker', 'icon_path', get_string('newpicture'), null/*, array('accepted_types' => 'gif,png,ico')*/); // Add elements to your form
        $mform->addElement('text', 'icon_path', get_string('newpicture'), array('style'=>'width: 100%;')); // Add elements to your form
        $mform->setType('icon_path', PARAM_TEXT);                   //Set type of element
        $mform->setDefault('icon_path', 'Please select icon');        //Default value
        
        //if (isset($this->_customdata['data']) && is_object($this->_customdata['data'])) {
        if (isset($data)) {
            $this->set_data($data);
            if (!empty($data->icon_path)/* && $hasuploadedpicture*/) {
                $imagevalue = html_writer::empty_tag('img', array('src'=>$data->icon_path, 'alt'=>$data->icon_path));
            } else {
                $imagevalue = get_string('none');
            }
            $currentpicture->setValue($imagevalue);
        }
        
        $this->add_action_buttons();
        
    }
}
  
?>
