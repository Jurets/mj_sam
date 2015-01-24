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

class mod_resourcelib_form_editchapter extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['chapter']) && is_object($this->_customdata['chapter'])) {
            $chapter = $this->_customdata['chapter'];
            $this->set_data($chapter);
        } else
            $chapter = null;
        
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        //video chapter: video ID (readonly, hidden)
        $video_id = $mform->addElement('hidden', 'resource_video_id');
        $mform->setType('resource_video_id', PARAM_INT);
        
        //video chapter: timecode
        $mform->addElement('text', 'timecode', get_string('chapter_timecode', 'videoresource')); // Add elements to your form
        $mform->setType('timecode', PARAM_INT); //Set type of element
        $mform->addRule('timecode', get_string('missing_timecode', 'videoresource'), 'required', null, 'client');
        
        //video chapter: timecode
        $mform->addElement('text', 'title', get_string('chapter_title', 'videoresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('title', PARAM_TEXT); //Set type of element
        $mform->addRule('title', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        
        //$this->add_action_buttons();
        $buttonarray=array();
        if (isset($chapter->id)) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        } else {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save_return_view', 'videoresource'));
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', get_string('save_new_chapter', 'videoresource'));
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        
    }
}  
?>