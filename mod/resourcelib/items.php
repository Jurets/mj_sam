<?php
    /**
    * Controller for ResourceLib Module
    * 
    * @author  Yuriy Hetmanskiy
    * @version 0.0.1
    * @license -
    * @package resourcelib
    *
    *-----------------------------------------------------------
    */

    /// Includes 
    require_once("../../config.php");
    require_once('lib.php');
    require_once('locallib.php');
    require_once($CFG->libdir.'/outputcomponents.php');

    /// Input params
    $id = optional_param('id', 0, PARAM_INT); //admin action for mooc-settings
    $confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
    //get action name
    $action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
    $action = (!empty($action) ? $action : 'index');

    //actions list
    $actionIndex = 'index';
    $actionAdd = 'add';
    $actionEdit = 'edit';
    $actionDelete = 'delete';
    
    /// Security
    $systemcontext = context_system::instance();
    require_login();
    require_capability('moodle/site:config', $systemcontext);

    /// Build page
    $mainurl = $CFG->wwwroot.'/mod/resourcelib/admin.php';
    $returnurl = $CFG->wwwroot.'/mod/resourcelib/items.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($mainurl)); 
    if ($action == $actionIndex) {
        $PAGE->navbar->add(get_string('manage_items', 'resourcelib'));
    } else {
        $PAGE->navbar->add(get_string('manage_items', 'resourcelib'), new moodle_url($returnurl));
    }

    switch($action) {
        case $actionIndex:
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('manage_items', 'resourcelib'));
            //add type button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('additem', 'resourcelib'));
            //show table with items data
            show_resource_items(get_resourceitems(), $returnurl);
            echo $OUTPUT->footer();
            break;
        case $actionAdd:
        case $actionEdit:
            require_once($CFG->dirroot.'/mod/resourcelib/form_edititem.php'); //include form_edittype.php  
            
            if ($action == $actionAdd) { //add new type
                $PAGE->navbar->add(get_string('additem', 'resourcelib'));
                $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
                $item = null;        //empty data
            } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
                $PAGE->navbar->add(get_string('edititem', 'resourcelib'));
                $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
                $item = $DB->get_record('resource_items', array('id'=>$id), '*', MUST_EXIST); //get data from DB
            }
            
            //get Resource Types List
            $types = $DB->get_records_menu('resource_types', null, '', 'id,name');
            //build form
            $editform = new mod_resourcelib_form_edititem($actionurl->out(false), array('item'=>$item, 'types'=>$types)); //create form instance
            //$editform->is_submitted()
            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                $url = new moodle_url($returnurl, array('action' => 'index'));
                redirect($url);
            } else if ($data = $editform->get_data()) {
                if ($action == $actionAdd) {
                    $inserted_id = add_resourceitem($data);
                    $success = isset($id);
                } else if (isset($id)){
                    $success = edit_resourceitem($data);
                }
                if ($success){  //call create Resource Type function
                    $url = new moodle_url($returnurl, array('action' => 'index'));
                    redirect($url);
                }
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('additem', 'resourcelib'));
            $editform->display();
            echo $OUTPUT->footer();
            break;
        case $actionDelete: 
            //breadcrumbs
            $PAGE->navbar->add(get_string('deleteitem', 'resourcelib'));
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
                $item = $DB->get_record('resource_items', array('id'=>$id), '*', MUST_EXIST);

                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading(get_string('deleteitem', 'resourcelib'));
                    $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$item->title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (deletete_resourcetype($item)) {
                        $url = new moodle_url($returnurl, array('action' => 'types'));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name));
                    }
                }
            }
            break;
    }
