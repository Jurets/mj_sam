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
 * edit form for videoresource
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

class mod_videoresource_form_addvideoresource extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['items'])/* && is_object($this->_customdata['items'])*/) {
            $items = $this->_customdata['items'];
        } else
            $items = null;

        $mform = $this->_form; // Don't forget the underscore! 
 
        //resourceItem: ID (readonly, hidden)
        //$mform->addElement('hidden', 'id');
        //$mform->setType('id', PARAM_INT);
        
        //  Publicly Accessible Fields
        //$mform->addElement('header', 'publiclyinfofieldset', get_string('publicly_info', 'videoresource'));

        //video: select
        $mform->addElement('select', 'instance_id', get_string('modulename', 'videoresource'), $items);
        $mform->setType('instance_id', PARAM_TEXT);                   //Set type of element
        $mform->addRule('instance_id', get_string('missingname'), 'required', null, 'client');

        //video: text above
        $mform->addElement('editor', 'textabove', get_string('text_above', 'videoresource'), array('rows'=>6, 'style'=>'width: 100%'));
        $mform->setType('textabove', PARAM_RAW); //Set type of element
        
        //video: text below
        $mform->addElement('editor', 'textbelow', get_string('text_below', 'videoresource'), array('rows'=>6, 'style'=>'width: 100%'));
        $mform->setType('textbelow', PARAM_RAW); //Set type of element

        /*if (isset($data)) {
            $this->set_data($data);
        }*/
        
        $this->add_action_buttons(false, get_string('add'));
        
    }
}
  
?>
