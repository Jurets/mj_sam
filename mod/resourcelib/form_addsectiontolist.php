<?php
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class mod_resourcelib_form_addsectiontolist extends moodleform {
    public function definition() {
        global $CFG;
        
        if (isset($this->_customdata['list']) && is_object($this->_customdata['list'])) {
            $list = $this->_customdata['list'];
        } else
            $list = null;
        
        if (isset($this->_customdata['sections']) && is_array($this->_customdata['sections'])) {
            $_items = $this->_customdata['sections'];
            $items = array('0'=>'');
            foreach($_items as $key=>$value) {$items[$key] = $value;}
            //$items = array_merge(array(0=>''), $items);
            //array_unshift($items, );
        } else
            $items = array();

        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        //resourceItem: ID (readonly, hidden)
        $section_id = $mform->addElement('hidden', 'resource_list_id');
        $mform->setType('resource_list_id', PARAM_INT);
        $section_id->setValue($list->id);
        
        //resourceItem: Type
        $mform->addElement('select', 'resource_section_id', get_string('section', 'resourcelib'), $items);
        $mform->setType('resource_section_id', PARAM_INT); //Set type of element
        $mform->addRule('resource_section_id', get_string('missing_section', 'resourcelib'), 'required', null, 'client');

        //resourceItem: Title
        $mform->addElement('text', 'sort_order', get_string('order')); // Add elements to your form
        $mform->setType('sort_order', PARAM_INT); //Set type of element
        //$mform->addRule('title', get_string('missingname'), 'required', null, 'client');
        
        $this->add_action_buttons();
        
    }
}
  
?>
