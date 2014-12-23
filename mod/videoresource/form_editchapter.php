<?php
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
        $mform->addElement('text', 'title', get_string('chapter_title', 'videoresource')); // Add elements to your form
        $mform->setType('title', PARAM_TEXT); //Set type of element
        $mform->addRule('title', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        
        $this->add_action_buttons();
        
    }
}  
?>
