<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class import_assign_import_form extends moodleform {

    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $cmid = $this->_customdata['id'];
        $options = $this->_customdata['options']; 
        $fileoptions['accepted_types'] = array('archive');
        
        $mform->addElement('filepicker', 'file', get_string('filetoimport', 'block_import_assign'), null, $options); 
        $mform->addRule('file', null, 'required');
        $mform->addHelpButton('file', 'filetoimport', 'block_import_assign'); 
//        $mform->addElement('filepicker', 'images', get_string('fileimages','block_import_assign'), 'size="40"', $fileoptions);
//        //$mform->addRule('file', null, 'required');
//        $mform->addHelpButton('images', 'fileimages', 'block_import_assign'); 
        $submit_string = get_string('submit');
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, $submit_string);
    }
}
function assign_validate_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 4) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // test columns
    $processed = array();
    foreach ($columns as $key=>$unused) {
        $field = $columns[$key];
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // standard fields are only lowercase
            $newfield = $lcfield;

        } else if (in_array($field, $profilefields)) {
            // exact profile field name match - these are case sensitive
            $newfield = $field;

        } else if (in_array($lcfield, $profilefields)) {
            // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
            $newfield = $lcfield;

        } else if (preg_match('/^(cohort|course|group|type|role|enrolperiod)\d+$/', $lcfield)) {
            // special fields for enrolments
            $newfield = $lcfield;

        } else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
    }

    return $processed;
}