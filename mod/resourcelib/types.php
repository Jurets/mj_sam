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
    $returnurl = $CFG->wwwroot.'/mod/resourcelib/types.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    //$PAGE->set_focuscontrol(build_navigation(array()));

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($mainurl));
    if ($action == $actionIndex) {
        $PAGE->navbar->add(get_string('manage_types', 'resourcelib'));
    } else {
        $PAGE->navbar->add(get_string('manage_types', 'resourcelib'), new moodle_url($returnurl));
    }

    switch($action) {
        case $actionIndex:
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('manage_types', 'resourcelib'));

            $stredit   = get_string('edit');
            $strdelete = get_string('delete');
            $table = new html_table();
            $table->head = array(get_string('name'), get_string('icon'));
            //get list of types
            $types = get_types();
            foreach ($types as $type) {
                $buttons = array();
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionDelete, 'id'=>$type->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionEdit, 'id'=>$type->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
                $table->data[] = array(
                    $type->name, 
                    html_writer::empty_tag('img', array(
                        'src'=>$type->icon_path, 
                        'alt'=>$type->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;'
                    )) . ' ' . $type->icon_path,
                    implode(' ', $buttons)
                );
            }
            //add type button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('addtype', 'resourcelib'));
            //table with types data
            echo html_writer::table($table);
            echo $OUTPUT->footer();
            break;
            
        case $actionAdd:
        case $actionEdit:
            require_once($CFG->dirroot.'/mod/resourcelib/form_edittype.php'); //include form_edittype.php  
            
            if ($action == $actionAdd) { //add new type
                $PAGE->navbar->add(get_string('addtype', 'resourcelib'));
                $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
                $type = array();        //empty data
            } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
                $PAGE->navbar->add(get_string('edittype', 'resourcelib'));
                $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
                $type = $DB->get_record('resource_types', array('id'=>$id), '*', MUST_EXIST); //get data from DB
            }
            $editform = new mod_resourcelib_form_edittype($actionurl->out(false), array('data'=>$type)); //create form instance

            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                $url = new moodle_url($returnurl, array('action' => 'index'));
                redirect($url);
            } else if ($data = $editform->get_data()) {
                if ($file = $editform->get_file_content('icon_path')) {
                    $realfilename = $editform->get_new_filename('icon_path');
                    $importfile = $CFG->dirroot . '/mod/resourcelib/pix/' . $realfilename;
                    if ($editform->save_file('icon_path', $importfile, true)) {
                        $data->icon_path = $CFG->wwwroot . '/mod/resourcelib/pix/' . $realfilename;
                    }
                }
                if ($action == $actionAdd) {
                    $inserted_id = add_type($data);
                    $success = isset($id);
                } else if (isset($id)){
                    $success = edit_type($data);
                }
                if ($success){  //call create Resource Type function
                    $url = new moodle_url($returnurl, array('action' => 'index'));
                    redirect($url);
                }
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('addtype', 'resourcelib'));
            $editform->display();
            echo $OUTPUT->footer();
            break;
            
        case $actionDelete: 
            //breadcrumbs
            $PAGE->navbar->add(get_string('deletetype', 'resourcelib'));
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource type, after confirmation
                //$type = $DB->get_record('resource_types', array('id'=>$id), '*', MUST_EXIST);
                $type = get_type($id);

                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading(get_string('deletetype', 'resourcelib'));
                    //before delete do check existing of resources of this type
                    if ($type->resource_count > 0) {
                        $str = get_string('deletednot', '', $type->name) . ' ' . get_string('resources_exists', 'resourcelib');
                        echo $OUTPUT->notification($str);
                    } else {
                        $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$type->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    }
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (delete_type($type)) {
                        //\core\session\manager::gc(); // Remove stale sessions.
                        $url = new moodle_url($returnurl, array('action' => 'index'));
                        redirect($url);
                    } else {
                        //\core\session\manager::gc(); // Remove stale sessions.
                        echo $OUTPUT->notification(get_string('deletednot', '', $type->name));
                    }
                }
            }
            break;

    }
