<?php
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
            $types = $this->_customdata['types'];
            array_unshift($types, '');
        } else
            $types = null;

        $mform = $this->_form; // Don't forget the underscore! 
 
        //resourceItem: ID (readonly, hidden)
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        //resourceItem: Type
        $mform->addElement('select', 'type_id', get_string('type', 'resourcelib'), $types);
        $mform->setType('type_id', PARAM_TEXT);                   //Set type of element
        $mform->addRule('type_id', get_string('missingname'), 'required', null, 'client');

        //resourceItem: Title
        $mform->addElement('text', 'title', get_string('name')); // Add elements to your form
        $mform->setType('title', PARAM_TEXT);                   //Set type of element
        $mform->addRule('title', get_string('missingname'), 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //resourceItem: URL
        $mform->addElement('text', 'url', get_string('url')); // Add elements to your form
        $mform->setType('url', PARAM_TEXT);                   //Set type of element
        $mform->addRule('url', get_string('missingurl'), 'required', null, 'client');
        $mform->addRule('url', get_string('maximumchars', '', 512), 'maxlength', 512, 'client');
        
        //resourceItem: author
        $mform->addElement('text', 'author', get_string('author', 'resourcelib')); // Add elements to your form
        $mform->setType('author', PARAM_TEXT);                   //Set type of element
        $mform->addRule('author', get_string('maximumchars', '', 128), 'maxlength', 128, 'client');
        
        //resourceItem: Source
        $mform->addElement('text', 'source', get_string('source', 'resourcelib')); // Add elements to your form
        $mform->setType('source', PARAM_TEXT);                   //Set type of element
        $mform->addRule('source', get_string('maximumchars', '', 128), 'maxlength', 128, 'client');
        
        //resourceItem: Copyright
        $mform->addElement('text', 'copyright', get_string('copyright', 'resourcelib')); // Add elements to your form
        $mform->setType('copyright', PARAM_TEXT);                   //Set type of element
        $mform->addRule('copyright', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        //resourceItem: Time Estimate
        $mform->addElement('text', 'time_estimate', get_string('time_estimate', 'resourcelib')); // Add elements to your form
        $mform->setType('time_estimate', PARAM_INT);                   //Set type of element

        //resourceItem: Tags
        $mform->addElement('text', 'tags', get_string('tags')); // Add elements to your form
        $mform->setType('tags', PARAM_TAGLIST);                   //Set type of element
        $mform->addRule('tags', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //resourceItem: Embed Code
        $mform->addElement('textarea', 'embed_code', get_string('embed_code', 'resourcelib')); // Add elements to your form
        $mform->setType('embed_code', PARAM_TEXT);                   //Set type of element

        //resourceItem: description
        $mform->addElement('textarea', 'description', get_string('description')); // Add elements to your form
        $mform->setType('description', PARAM_TEXT);                   //Set type of element
        $mform->addRule('description', get_string('missingdescription'), 'required', null, 'client');

        //$currentpicture = $mform->addElement('static', 'currentpicture', get_string('currentpicture'));
        //$filepicker = $mform->addElement('filepicker', 'icon_path', get_string('newpicture'), null, array('accepted_types' => 'gif,png,ico')); // Add elements to your form
        //$mform->setType('icon_path', PARAM_TEXT);                   //Set type of element
        //$mform->setDefault('icon_path', 'Please select icon');        //Default value
        
        //DebugBreak();        
        //if (isset($this->_customdata['data']) && is_object($this->_customdata['data'])) {
        /*if (isset($data)) {
            $this->set_data($data);
            if (!empty($data->icon_path) && $hasuploadedpicture) {
                $imagevalue = html_writer::empty_tag('img', array('src'=>$data->icon_path, 'alt'=>$data->icon_path));
            } else {
                $imagevalue = get_string('none');
            }
            $currentpicture->setValue($imagevalue);
        }*/
        
        $this->add_action_buttons();
        
    }
}
  
?>
