<?php
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class mod_resourcelib_form_edittype extends moodleform {
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('text', 'name', get_string('name')); // Add elements to your form
        $mform->setType('name', PARAM_TEXT);                   //Set type of element
        //$mform->setDefault('name', 'Please enter name');        //Default value
        //$mform->setAttributes('name', array('title'=>'Please enter name'));        //Default value
        $mform->addRule('name', get_string('maximumchars', '', 32), 'maxlength', 32, 'client');
        $mform->addRule('name', get_string('missingname'), 'required', null, 'client');
        
        $mform->addElement('filepicker', 'icon_path', get_string('icon')); // Add elements to your form
        $mform->setType('icon', PARAM_TEXT);                   //Set type of element
        $mform->setDefault('icon', 'Please select icon');        //Default value
        
        $this->add_action_buttons();
        
    }
}
  
?>
