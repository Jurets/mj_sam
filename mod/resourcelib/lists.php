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
    //get sort param (if present)
    $sort = optional_param('sort', 'name', PARAM_TEXT);
    $dir = optional_param('dir', 'ASC', PARAM_TEXT);

    //actions list
    $actionIndex = 'index';
    $actionAdd = 'add';
    $actionEdit = 'edit';
    $actionDelete = 'delete';
    $actionView = 'view';
    $actionAddSection = 'addtolist';
    $actionDelSection = 'delfromlist';
    
    /// Security
    $systemcontext = context_system::instance();
    require_login();
    require_capability('moodle/site:config', $systemcontext);

    /// Build page
    $mainurl = $CFG->wwwroot.'/mod/resourcelib/admin.php';
    $returnurl = $CFG->wwwroot.'/mod/resourcelib/lists.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    //$PAGE->set_focuscontrol(build_navigation(array()));

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettingresourcelib'))); 
    //$PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($mainurl));
    if ($action == $actionIndex) {
        $PAGE->navbar->add(get_string('manage_lists', 'resourcelib'));
    } else {
        $PAGE->navbar->add(get_string('manage_lists', 'resourcelib'), new moodle_url($returnurl));
    }

    // ------ process actions --------
    switch($action) {
        case $actionIndex:
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('manage_lists', 'resourcelib'));

            $stredit   = get_string('edit');
            $strdelete = get_string('delete');
            $strview = get_string('settings');
            
            $table = new html_table();
            $table->head = array();
            $table->head[] = resourcelib_get_column_title($returnurl, 'name', get_string('name'), $sort, $dir);
            $table->head[] = resourcelib_get_column_title($returnurl, 'display_name', get_string('display_name', 'resourcelib'), $sort, $dir);
            $table->head[] = get_string('icon');
            $table->head[] = get_string('section_count', 'resourcelib');
            
            $table->size[4] = '80px';
            
            //get list of data
            $lists = resourcelib_get_lists($sort, $dir);
            foreach ($lists as $list) {
                $buttons = array();
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionEdit, 'id'=>$list->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$list->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$strview));
                if (!$list->s_count)
                    $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>$actionDelete, 'id'=>$list->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
                $table->data[] = array(
                    html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$list->id)), $list->name),
                    $list->display_name, 
                    html_writer::empty_tag('img', array(
                        'src'=>$list->icon_path, 
                        'alt'=>$list->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;')),
                    ($list->s_count ? $list->s_count : ''), 
                    implode(' ', $buttons)
                );
            }
            //add type button
            resourcelib_show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('addlist', 'resourcelib'));
            //table with types data
            echo html_writer::table($table);
            echo $OUTPUT->footer();
            break;
            
        case $actionView:
            $list = $DB->get_record('resource_lists', array('id'=>$id), '*', MUST_EXIST); //get data from DB

            $head_str = !empty($list->display_name) ? $list->display_name : $list->name;
            $PAGE->navbar->add($head_str);
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_str);
            
            if (!empty($list->icon_path)/* && $hasuploadedpicture*/) {
                $imagevalue = html_writer::empty_tag('img', array('src'=>$list->icon_path, 'alt'=>$list->icon_path));
            } else {
                $imagevalue = get_string('none');
            }
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('name'));
            echo html_writer::tag('dd', $list->name);
            echo html_writer::end_tag('dl');
            
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('display_name', 'resourcelib'));
            echo html_writer::tag('dd', $list->display_name);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('writingheader'));
            echo html_writer::tag('dd', $list->heading);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('currentpicture'));
            echo html_writer::tag('dd', $imagevalue);
            echo html_writer::end_tag('dl');
            //show edit button
            resourcelib_show_editbutton(new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$list->id)), get_string('editlist', 'resourcelib'));
            //
            echo html_writer::tag('hr', '');
            //add section button
            resourcelib_show_addbutton(new moodle_url($returnurl, array('action' => $actionAddSection, 'list'=>$id)), get_string('add_list_section', 'resourcelib'));
            //sections in table format
            show_list_sections(get_list_sections($list), $returnurl, array('delete'=>$actionDelSection));
            
            echo $OUTPUT->footer();
            break;
            
        case $actionAdd:
        case $actionEdit:
            require_once($CFG->dirroot.'/mod/resourcelib/form_editlist.php'); //include form_edittype.php  
            
            $head_str = ($action == $actionAdd) ? get_string('addlist', 'resourcelib') : get_string('editlist', 'resourcelib');
            $PAGE->navbar->add($head_str);

            if ($action == $actionAdd) { //add new type
                $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
                $list = array();        //empty data
            } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
                $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
                $list = $DB->get_record('resource_lists', array('id'=>$id), '*', MUST_EXIST); //get data from DB
            }
            $editform = new mod_resourcelib_form_editlist($actionurl->out(false), array('data'=>$list)); //create form instance

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
                    $inserted_id = add_list($data);
                    $success = isset($id);
                } else if (isset($id)){
                    $success = edit_list($data);
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
            $PAGE->navbar->add(get_string('deletelist', 'resourcelib'));
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource type, after confirmation
                $list = get_list($id);

                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading(get_string('deletelist', 'resourcelib'));
                    //before delete do check existing of resources of this type
                    if ($list->s_count > 0) {
                        $str = get_string('deletednot', '', $list->name) . ' ' . get_string('section_exists', 'resourcelib');
                        echo $OUTPUT->notification($str);
                    } else {
                        $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$list->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    }
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (delete_list($list)) {
                        $url = new moodle_url($returnurl, array('action' => $actionIndex));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $list->name));
                    }
                }
            }
            break;
            
        case $actionAddSection:
            require_once($CFG->dirroot.'/mod/resourcelib/form_addsectiontolist.php'); //include form_edittype.php  
            //arbitrary param: section_id
            $list_id = optional_param('list', 0, PARAM_INT);
            //get List
            $list = $DB->get_record('resource_lists', array('id'=>$list_id), '*', MUST_EXIST);
            
            $head_str = !empty($list->display_name) ? $list->display_name : $list->name;
            //build url's
            $urlView = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$list_id));
            $urlAction = new moodle_url($returnurl, array('action'=>$actionAddSection, 'list'=>$list_id));
            //breadcrumbs
            $PAGE->navbar->add($head_str /*get_string('viewlist', 'resourcelib')*/, $urlView);
            $PAGE->navbar->add(get_string('add_list_section', 'resourcelib'));
            //get Resources
            $sections = get_notlist_sections($list);
            //create and show form instance
            $editform = new mod_resourcelib_form_addsectiontolist($urlAction->out(false), array('list'=>$list, 'sections'=>$sections)); 
            
            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                redirect($urlView);
            } else if ($data = $editform->get_data()) {
                $inserted_id = add_section_to_list($data);
                redirect($urlView);
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('add_list_section', 'resourcelib'));
            $editform->display();
            
            echo $OUTPUT->footer();
            break;  
            
        case $actionDelSection: 
            //breadcrumbs
            $head_str = get_string('del_list_section', 'resourcelib');
            $PAGE->navbar->add($head_str);
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource from section, after confirmation
                $section = get_section_fromlist($id);

                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($head_str);
                    $optionsyes = array('action'=>$actionDelSection, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheck_section_fromlist', 'resourcelib', "'$section->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (delete_section_from_list($id)) {
                        $url = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$section->resource_list_id));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification(get_string('deletednot', '', $section->name));
                    }
                }
            }
            break;
    }
