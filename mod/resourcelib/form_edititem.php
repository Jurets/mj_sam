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
 * Edit Item form  for resourcelib
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

class mod_resourcelib_form_edititem extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['item']) && is_object($this->_customdata['item'])) {
            $data = $this->_customdata['item'];
        } else
            $data = null;
        if (isset($this->_customdata['types']) && is_array($this->_customdata['types'])) {
            $_items = $this->_customdata['types'];
            //add empty item to the begin of options
            $items = array('0'=>'');
            foreach($_items as $key=>$value) {$items[$key] = $value;}
            //array_unshift($types, '');
        } else
            $types = null;

        $mform = $this->_form; // Don't forget the underscore! 
 
        //resourceItem: ID (readonly, hidden)
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        //resourceItem: Type
        $mform->addElement('select', 'type_id', get_string('type', 'resourcelib'), $items);
        $mform->setType('type_id', PARAM_TEXT);                   //Set type of element
        $mform->addRule('type_id', get_string('missingname'), 'required', null, 'client');

        //resourceItem: Internal Namee
        $mform->addElement('text', 'internal_title', get_string('internal_title', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('internal_title', PARAM_TEXT);                   //Set type of element
        $mform->addRule('internal_title', get_string('missingname'), 'required', null, 'client');
        $mform->addRule('internal_title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //resourceItem: Title
        $mform->addElement('text', 'title', get_string('name'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('title', PARAM_TEXT);                   //Set type of element
        $mform->addRule('title', get_string('missingname'), 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //resourceItem: URL
        $mform->addElement('text', 'url', get_string('url'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('url', PARAM_TEXT);                   //Set type of element
        $mform->addRule('url', get_string('missingurl'), 'required', null, 'client');
        $mform->addRule('url', get_string('maximumchars', '', 512), 'maxlength', 512, 'client');
        
        //resourceItem: author
        $mform->addElement('text', 'author', get_string('author', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('author', PARAM_TEXT);                   //Set type of element
        $mform->addRule('author', get_string('maximumchars', '', 128), 'maxlength', 128, 'client');
        
        //resourceItem: Source
        $mform->addElement('text', 'source', get_string('source', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('source', PARAM_TEXT);                   //Set type of element
        $mform->addRule('source', get_string('maximumchars', '', 128), 'maxlength', 128, 'client');
        
        //resourceItem: Copyright
        $mform->addElement('text', 'copyright', get_string('copyright', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('copyright', PARAM_TEXT);                   //Set type of element
        $mform->addRule('copyright', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        //resourceItem: Length
        $mform->addElement('text', 'length', get_string('length', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('length', PARAM_TEXT);                   //Set type of element
        $mform->addRule('length', get_string('maximumchars', '', 20), 'maxlength', 20, 'client');

        //resourceItem: Publication Date
        //$mform->addElement('date_time_selector', 'public_date', get_string('public_date', 'resourcelib')); // Add elements to your form
        $mform->addElement('text', 'public_date', get_string('public_date', 'resourcelib'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('public_date', PARAM_TEXT);                   //Set type of element
        $mform->addRule('public_date', get_string('maximumchars', '', 30), 'maxlength', 30, 'client');

        //resourceItem: Time Estimate
        $mform->addElement('text', 'time_estimate', get_string('time_estimate', 'resourcelib'), array(
            'placeholder'=>get_string('enter_estimated_time', 'resourcelib'),
            'style'=>'width: 100%',
        )); // Add elements to your form
        $mform->setType('time_estimate', PARAM_TEXT);                   //Set type of element

        //resourceItem: Tags
        $mform->addElement('text', 'tags', get_string('tags'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('tags', PARAM_TAGLIST);                   //Set type of element
        $mform->addRule('tags', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //resourceItem: Embed Code
        $mform->addElement('textarea', 'embed_code', get_string('embed_code', 'resourcelib'), array(
            'rows'=>3, 
            'style'=>'width: 100%',
        )); // Add elements to your form
        $mform->setType('embed_code', PARAM_TEXT);                   //Set type of element

        //resourceItem: description
        $mform->addElement('textarea', 'description', get_string('description'), array(
            'rows'=>5, 
            'style'=>'width: 100%',
        )); // Add elements to your form
        $mform->setType('description', PARAM_TEXT);                   //Set type of element
        $mform->addRule('description', get_string('missingdescription'), 'required', null, 'client');

        if (isset($data)) {
            $this->set_data($data);
        }
        
        $this->add_action_buttons();
        
    }
}
  
?>
