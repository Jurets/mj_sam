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

class mod_videoresource_form_editvideo extends moodleform {
    public function definition() {
        global $CFG;
        //DebugBreak();
        if (isset($this->_customdata['video']) && is_object($this->_customdata['video'])) {
            $data = $this->_customdata['video'];
        } else
            $data = null;

        $mform = $this->_form; // Don't forget the underscore! 
 
        //resourceItem: ID (readonly, hidden)
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        //video: Youtube Video ID
        $mform->addElement('text', 'video_id', get_string('videoid', 'videoresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('video_id', PARAM_TEXT);                   //Set type of element
        $mform->addRule('video_id', get_string('missing_videoid', 'videoresource'), 'required', null, 'client');
        $mform->addRule('video_id', get_string('maximumchars', '', 32), 'maxlength', 32, 'client');

        //  Internal Reference Information
        $mform->addElement('header', 'internalinfofieldset', get_string('internal_info', 'videoresource'));
        //video: internal name
        $mform->addElement('text', 'internal_title', get_string('internal_name', 'videoresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('internal_title', PARAM_TEXT);                   //Set type of element
        $mform->addRule('internal_title', get_string('missing_internal_title', 'videoresource'), 'required', null, 'client');
        $mform->addRule('internal_title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        //video: Internal notes
        $mform->addElement('textarea', 'internal_notes', get_string('internal_notes', 'videoresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('internal_notes', PARAM_TEXT); //Set type of element
        
        //  Publicly Accessible Fields
        $mform->addElement('header', 'publiclyinfofieldset', get_string('publicly_info', 'videoresource'));
        //video: Video Title
        $mform->addElement('text', 'title', get_string('video_title', 'videoresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('title', PARAM_TEXT);                   //Set type of element
        //video: Description/Followup Text
        $description = $mform->addElement('editor', 'description', get_string('description_text', 'videoresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('description', PARAM_RAW); //Set type of element
        //video: Podcast URL
        $mform->addElement('text', 'podcast_url', get_string('podcast_url', 'videoresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('podcast_url', PARAM_TEXT);  
        //video: transcript
        $transcript = $mform->addElement('editor', 'transcript', get_string('transcript', 'videoresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('transcript', PARAM_RAW); //Set type of element
        
        if (isset($data)) {
            $this->set_data($data);
            $description->setValue(array('text' => $data->description));
            $transcript->setValue(array('text' => $data->transcript));
        }
        
        $this->add_action_buttons();
        
    }
}
  
?>
