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
    $actionView = 'view';
    $actionAdd = 'add';
    $actionEdit = 'edit';
    $actionDelete = 'delete';
    $actionView = 'view';
    $actionAddResource = 'addtosection';
    $actionDelResource = 'delfromsection';
    
    /// Security
    $systemcontext = context_system::instance();
    require_login();
    require_capability('moodle/site:config', $systemcontext);

    /// Build page
    $mainurl = $CFG->wwwroot.'/mod/resourcelib/admin.php';
    $returnurl = $CFG->wwwroot.'/mod/resourcelib/sections.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettingresourcelib'))); 
    //$PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($mainurl));
    if ($action == $actionIndex) {
        $PAGE->navbar->add(get_string('manage_sections', 'resourcelib'));
    } else {
        $PAGE->navbar->add(get_string('manage_sections', 'resourcelib'), new moodle_url($returnurl));
    }

    // ------ process actions --------
    switch($action) {
        case $actionIndex:
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('manage_sections', 'resourcelib'));

            $strview   = get_string('view');
            $stredit   = get_string('edit');
            $strdelete = get_string('delete');
            $table = new html_table();
            $table->head = array(
                get_string('name'), 
                get_string('display_name', 'resourcelib'), 
                get_string('icon'),
                get_string('resource_count', 'resourcelib'),
            );
            $table->size[4] = '80px';
            //get list of data
            $sections = get_sections();
            foreach ($sections as $section) {
                $buttons = array();
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionEdit,  'id'=>$section->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionView,  'id'=>$section->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$strview, 'class'=>'iconsmall')), array('title'=>$strview));
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionDelete,'id'=>$section->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
                $table->data[] = array(
                    html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$section->id)), $section->name),
                    $section->display_name, 
                    html_writer::empty_tag('img', array(
                        'src'=>$section->icon_path, 
                        'alt'=>$section->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;')),
                    $section->r_count, 
                    implode(' ', $buttons)
                );
            }
            //add type button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('addsection', 'resourcelib'));
            //table with types data
            echo html_writer::table($table);
            echo $OUTPUT->footer();
            break;
            
        case $actionView:
            //get section
            $section = get_section($id);
            $head_str = !empty($section->display_name) ? $section->display_name : $section->name;

            $PAGE->navbar->add($head_str);
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_str);
            
            if (!empty($section->icon_path)/* && $hasuploadedpicture*/) {
                $imagevalue = html_writer::empty_tag('img', array('src'=>$section->icon_path, 'alt'=>$section->icon_path));
            } else {
                $imagevalue = get_string('none');
            }
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('name'));
            echo html_writer::tag('dd', $section->name);
            echo html_writer::end_tag('dl');
            
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('display_name', 'resourcelib'));
            echo html_writer::tag('dd', $section->display_name);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('writingheader'));
            echo html_writer::tag('dd', $section->heading);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('currentpicture'));
            echo html_writer::tag('dd', $imagevalue);
            echo html_writer::end_tag('dl');
            //show edit button
            show_editbutton(new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$section->id)), get_string('editsection', 'resourcelib'));
            
            echo html_writer::tag('hr', '');
            //add resource button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAddResource, 'section'=>$id)), get_string('add_section_resource', 'resourcelib'));
            //resources in table format
            show_resource_items(get_section_items($section), $returnurl, array('delete'=>$actionDelResource));
            //
            echo $OUTPUT->footer();
            break;
            
        case $actionAdd:
        case $actionEdit:
            require_once($CFG->dirroot.'/mod/resourcelib/form_editlist.php'); //include form_edititem.php  
            
            $head_str = ($action == $actionAdd) ? get_string('addsection', 'resourcelib') : get_string('editsection', 'resourcelib');
            $PAGE->navbar->add($head_str);
            
            if ($action == $actionAdd) { //add new type
                $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
                $section = array();        //empty data
            } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
                $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
                $section = $DB->get_record('resource_sections', array('id'=>$id), '*', MUST_EXIST); //get data from DB
            }
            //create and show form instance
            $editform = new mod_resourcelib_form_editlist($actionurl->out(false), array('data'=>$section)); //create form instance
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
                    $inserted_id = add_section($data);
                    $success = isset($id);
                } else if (isset($id)){
                    $success = edit_section($data);
                }
                if ($success){  //call create Resource Type function
                    $url = new moodle_url($returnurl, array('action' => 'index'));
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
            $PAGE->navbar->add(get_string('deletesection', 'resourcelib'));
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource type, after confirmation
                $section = get_section($id);
                
                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading(get_string('deletesection', 'resourcelib'));
                    //before delete do check existing of sections in ane list
                    if ($section->r_count > 0) {
                        $str = get_string('deletednot', '', $section->name) . ' ' . get_string('section_resource_exists', 'resourcelib');
                        echo $OUTPUT->notification($str);
                    } else {
                        $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$section->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    }
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (delete_section($section)) {
                        $url = new moodle_url($returnurl, array('action' => $actionIndex));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $section->name));
                    }
                }
            }
            break;
            
        case $actionAddResource:
            require_once($CFG->dirroot.'/mod/resourcelib/form_additemtosection.php'); //include form_edittype.php  
            
            //arbitrary param: section_id
            $section = optional_param('section', 0, PARAM_INT);
            //build url's
            $urlView = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$section));
            $urlAction = new moodle_url($returnurl, array('action'=>$actionAddResource, 'section'=>$section));
            //breadcrumbs
            $PAGE->navbar->add(get_string('viewsection', 'resourcelib'), $urlView);
            $PAGE->navbar->add(get_string('add_section_resource', 'resourcelib'));
            //get Section
            $section = $DB->get_record('resource_sections', array('id'=>$section), '*', MUST_EXIST);
            //get Resources
            $items = get_notsection_items($section);
            //create and show form instance
            $editform = new mod_resourcelib_form_additemtosection($urlAction->out(false), array('section'=>$section, 'items'=>$items)); 
            
            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                redirect($urlView);
            } else if ($data = $editform->get_data()) {
                $inserted_id = add_resource_to_section($data);
                redirect($urlView);
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('add_section_resource', 'resourcelib'));
            $editform->display();
            
            echo $OUTPUT->footer();
            break;  
            
        case $actionDelResource: 
            //breadcrumbs
            $head_str = get_string('del_section_resource', 'resourcelib');
            $PAGE->navbar->add($head_str);
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource from section, after confirmation
                $resource = get_resource_fromsection($id);

                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($head_str);
                    $optionsyes = array('action'=>$actionDelResource, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheck_resurce_fromsection', 'resourcelib', "'$resource->title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (delete_resource_from_section($id)) {
                        $url = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$resource->resource_section_id));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification(get_string('deletednot', '', $resource->title));
                    }
                }
            }
            break;
         
    }
