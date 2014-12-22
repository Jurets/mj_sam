<?php
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class mod_videoresource_form_editvideo extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['video']) && is_object($this->_customdata['video'])) {
            $data = $this->_customdata['video'];
        } else
            $data = null;
        /*if (isset($this->_customdata['types']) && is_array($this->_customdata['types'])) {
            $_items = $this->_customdata['types'];
            //add empty item to the begin of options
            $items = array('0'=>'');
            foreach($_items as $key=>$value) {$items[$key] = $value;}
            //array_unshift($types, '');
        } else
            $types = null;*/

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
        $mform->addElement('textarea', 'description', get_string('description_text', 'videoresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('description', PARAM_TEXT); //Set type of element
        //video: Podcast URL
        $mform->addElement('text', 'podcast_url', get_string('podcast_url', 'videoresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('podcast_url', PARAM_TEXT);  
        //video: transcript
        $mform->addElement('textarea', 'transcript', get_string('transcript', 'videoresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('transcript', PARAM_TEXT); //Set type of element
        
        if (isset($data)) {
            $this->set_data($data);
        }
        
        $this->add_action_buttons();
        
    }
}
  
?>
