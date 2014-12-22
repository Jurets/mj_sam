<?php
    /**
    * Controller for Video Resource Module
    * 
    * @author  Yuriy Hetmanskiy
    * @version 0.0.1
    * @license -
    * @package videoresource
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
    //$mainurl = $CFG->wwwroot.'/mod/videoresource/admin.php';
    $returnurl = $CFG->wwwroot.'/mod/videoresource/video.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'videoresource'), new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettingvideoresource'))); 
    //$PAGE->navbar->add(get_string('administration', 'videoresource'), new moodle_url($mainurl)); 
    
    $head_index = get_string('manage_videos', 'videoresource');
    if ($action == $actionIndex) {
        $PAGE->navbar->add($head_index);
    } else {
        $PAGE->navbar->add($head_index, new moodle_url($returnurl));
    }

    switch($action) {
        case $actionIndex:
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_index);
            //add type button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('add_video', 'videoresource'));
            //show table with items data
            show_resource_items(videoresource_get_videos(), $returnurl);
            echo $OUTPUT->footer();
            break;
            
        case $actionAdd:
        case $actionEdit:
            require_once($CFG->dirroot.'/mod/videoresource/form_editvideo.php'); //include form_edittype.php  
            
            $head_str = ($action == $actionAdd) ? get_string('add_video', 'videoresource') : get_string('edit_video', 'videoresource');
            
            if ($action == $actionAdd) { //add new type
                $PAGE->navbar->add($head_str);
                $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
                $video = null;        //empty data
            } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
                $PAGE->navbar->add($head_str);
                $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
                $video = videoresource_get_video($id); //get data from DB
            }
            
            //get Resource Types List
            //$types = $DB->get_records_menu('resource_types', null, '', 'id,name');
            //build form
            $editform = new mod_videoresource_form_editvideo($actionurl->out(false), array('video'=>$video)); //create form instance
            //$editform->is_submitted()
            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                $url = new moodle_url($returnurl, array('action' => $actionIndex));
                redirect($url);
            } else if ($data = $editform->get_data()) {
                if ($action == $actionAdd) {
                    $inserted_id = videoresource_add_video($data);
                    $success = isset($id);
                } else if (isset($id)){
                    $success = resource_videos($data);
                }
                if ($success){  //call create Resource Type function
                    $url = new moodle_url($returnurl, array('action' => $actionIndex));
                    redirect($url);
                }
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_str);
            $editform->display();
            echo $OUTPUT->footer();
            break;
            
        case $actionDelete: 
            //breadcrumbs
            $PAGE->navbar->add(get_string('deleteitem', 'resourcelib'));
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
                $item = get_resource($id); //get data from DB

                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading(get_string('deleteitem', 'resourcelib'));
                    //before delete do check existing of resources in any section
                    if ($item->rs_count > 0) {
                        $str = get_string('deletednot', '', $item->title) . ' ' . get_string('resources_exists_in_section', 'resourcelib');
                        echo $OUTPUT->notification($str);
                    } else {
                        $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$item->title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    }
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (delete_resource($item)) {
                        $url = new moodle_url($returnurl, array('action' => $actionIndex));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name));
                    }
                }
            }
            break;
    }
