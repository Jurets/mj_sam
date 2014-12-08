<?php
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
        
        $mform->addElement('text', 'name', get_string('name')); // Add elements to your form
        $mform->setType('name', PARAM_TEXT);                   //Set type of element
        //$mform->setDefault('name', 'Please enter name');        //Default value
        $mform->addRule('name', get_string('missingname'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'display_name', get_string('display_name', 'resourcelib')); // Add elements to your form
        $mform->setType('display_name', PARAM_TEXT);                   //Set type of element
        $mform->addRule('display_name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $mform->addElement('textarea', 'heading', get_string('writingheader'), array(
            'rows'=>3, 
            'style'=>'width: 100%',
        )); // Add elements to your form
        $mform->setType('heading', PARAM_TEXT);                   //Set type of element

        $currentpicture = $mform->addElement('static', 'currentpicture', get_string('currentpicture'));
        
        //$filepicker = $mform->addElement('filepicker', 'icon_path', get_string('newpicture'), null/*, array('accepted_types' => 'gif,png,ico')*/); // Add elements to your form
        $mform->addElement('text', 'icon_path', get_string('newpicture'), array('style'=>'width: 500px;')); // Add elements to your form
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
