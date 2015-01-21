<?php
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class mod_htmlresource_form_edit extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['item']) && is_object($this->_customdata['item'])) {
            $data = $this->_customdata['item'];
        } else
            $data = null;

        $mform = $this->_form; // Don't forget the underscore! 
 
        //resourceItem: ID (readonly, hidden)
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        //  Internal Reference Information
        $mform->addElement('header', 'internalinfofieldset', get_string('internal_info', 'htmlresource'));
        //html: internal name
        $mform->addElement('text', 'internal_title', get_string('internal_name', 'htmlresource'), array('style'=>'width: 100%')); // Add elements to your form
        $mform->setType('internal_title', PARAM_TEXT); //Set type of element
        $mform->addRule('internal_title', get_string('missing_internal_title', 'htmlresource'), 'required', null, 'client');
        $mform->addRule('internal_title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        //html: Internal notes
        $mform->addElement('textarea', 'internal_notes', get_string('internal_notes', 'htmlresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('internal_notes', PARAM_TEXT);
        //html: Category
        $mform->addElement('text', 'category', get_string('html_category', 'htmlresource'), array('style'=>'width: 100%'));
        $mform->setType('category', PARAM_TEXT);
        
        //  Publicly Accessible Fields
        $mform->addElement('header', 'publiclyinfofieldset', get_string('publicly_info', 'htmlresource'));
        //html: Title
        $mform->addElement('text', 'title', get_string('html_title', 'htmlresource'), array('style'=>'width: 100%'));
        $mform->setType('title', PARAM_TEXT);
        //html: Description/Followup Text
        $mform->addElement('textarea', 'description', get_string('description_text', 'htmlresource'), array('rows'=>3, 'style'=>'width: 100%'));
        $mform->setType('description', PARAM_TEXT);
        
        //html: text
        $editor = $mform->addElement('editor', 'html', get_string('html_text', 'htmlresource'), array('style'=>'width: 100%'));
        $mform->setType('html', PARAM_RAW);
        
        if (isset($data)) {
            $this->set_data($data);
        }
        
        $this->add_action_buttons();
        
    }
}
  
?>
