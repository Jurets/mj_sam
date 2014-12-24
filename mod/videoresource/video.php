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
    $action = optional_param('action', '', PARAM_TEXT); //action name for process different operations
    $action = (!empty($action) ? $action : 'index');

    //actions list
    $actionIndex = 'index';
    $actionAdd = 'add';
    $actionEdit = 'edit';
    $actionDelete = 'delete';
    $actionView = 'view';
    $actionAddChapter = 'addchapter';
    $actionEditChapter = 'editchapter';
    $actionDelChapter = 'delchapter';
    
    /// Security
    $systemcontext = context_system::instance();
    require_login();
    require_capability('moodle/site:config', $systemcontext);

    /// Build page
    $returnurl = $CFG->wwwroot.'/mod/videoresource/video.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'videoresource'), new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettingvideoresource'))); 
    
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
            $items = videoresource_get_videos();
            //show_resource_items(videoresource_get_videos(), $returnurl);
            if (!$items || empty($items)) {
                echo $OUTPUT->notification(get_string('no_resources', 'resourcelib'), 'redirectmessage');
            } else {
                if (!isset($buttons)) //default buttons
                    $buttons = array('delete'=>'delete', 'edit'=>'edit');
                
                $table = new html_table();
                $table->head = array(
                    get_string('videoid', 'videoresource'), 
                    get_string('internal_name', 'videoresource'), 
                    get_string('video_title', 'videoresource')
                );
                
                foreach ($items as $item) {
                    $buttons_column = array();
                    if (key_exists('delete', $buttons))
                        $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
                    if (key_exists('edit', $buttons))
                        $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
                    $table->data[] = array(
                        html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$item->id)), $item->video_id),
                        $item->internal_title, 
                        $item->title, 
                        implode(' ', $buttons_column) 
                    );
                }
                echo html_writer::table($table);
            }
            
            echo $OUTPUT->footer();
            break;

        case $actionView:
            $video = $DB->get_record('resource_videos', array('id'=>$id), '*', MUST_EXIST); //get data from DB

            $head_str = !empty($video->title) ? $video->title : $video->internal_title;
            $PAGE->navbar->add($head_str);
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_str);
            
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('videoid', 'videoresource'));
            echo html_writer::tag('dd', $video->video_id);
            echo html_writer::end_tag('dl');
            
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('internal_name', 'videoresource'));
            echo html_writer::tag('dd', $video->internal_title);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('internal_notes', 'videoresource'));
            echo html_writer::tag('dd', $video->internal_notes);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('video_title', 'videoresource'));
            echo html_writer::tag('dd', $video->title);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('description_text', 'videoresource'));
            echo html_writer::tag('dd', $video->description, array('style'=>'max-height: 300px; overflow: auto;'));
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('podcast_url', 'videoresource'));
            echo html_writer::tag('dd', html_writer::link(new moodle_url($video->podcast_url), $video->podcast_url, array('target'=>'_blank')));
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('transcript', 'videoresource'));
            echo html_writer::tag('dd', $video->transcript, array('style'=>'max-height: 300px; overflow: auto;'));
            echo html_writer::end_tag('dl');
            //show edit button
            show_editbutton(new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$video->id)), get_string('edit_video', 'videoresource'));
            //
            echo html_writer::tag('hr', '');
            //add section button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAddChapter, 'video'=>$id)), get_string('add_video_chapter', 'videoresource'));
            
            //chapters in table format
            $items = get_video_chapters($video);
            if (!$items || empty($items)) {
                echo $OUTPUT->notification(get_string('no_chapters', 'videoresource'), 'redirectmessage');
            } else {
                if (!isset($buttons)) //default buttons
                    $buttons = array('delete'=>$actionDelChapter, 'edit'=>$actionEditChapter);
                
                $table = new html_table();
                $table->head = array(
                    get_string('chapter_timecode', 'videoresource'), 
                    get_string('chapter_title', 'videoresource')
                );
                
                foreach ($items as $item) {
                    $buttons_column = array();
                    if (key_exists('delete', $buttons))
                        //$buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id, true);
                        $buttons_column[] = videoresource_show_deletebutton(
                            $returnurl, $buttons['delete'], $item->id, 
                            get_string('delete_video_chapter', 'videoresource').'?'
                        );
                    if (key_exists('edit', $buttons))
                        $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
                    $table->data[] = array(
                        //html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$item->id)), $item->video_id),
                        //$item->video_id, 
                        $item->timecode, 
                        $item->title, 
                        implode(' ', $buttons_column) 
                    );
                }
                echo html_writer::table($table);
            }
//            show_video_chapters(get_video_chapters($video), $returnurl, array('delete'=>$actionDelChapter));
            
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
                    $success = videoresource_edit_video($data);
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
            $head_str = get_string('delete_video', 'videoresource');
            //breadcrumbs
            $PAGE->navbar->add($head_str);
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
                $item = videoresource_get_video($id); //get data from DB
                
                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($head_str);
                    //before delete do check existing of video resources in any course
                    if (isset($item->c_count) && $item->c_count > 0) {
                        $str = get_string('deletednot', '', $item->title) . ' ' . get_string('videoresource_exists_in_course', 'videoresource');
                        echo $OUTPUT->notification($str);
                    } else {
                        $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$item->internal_title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    }
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (videoresource_delete_video($item)) {
                        $url = new moodle_url($returnurl, array('action' => $actionIndex));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name));
                    }
                }
            }
            break;

        case $actionAddChapter:
        case $actionEditChapter: 
            require_once($CFG->dirroot.'/mod/videoresource/form_editchapter.php'); //include form_edittype.php  
            
            if ($action == $actionAddChapter) { //add new type
                //check input param
                $video_id = optional_param('video', -1, PARAM_INT);
                $video = $DB->get_record('resource_videos', array('id'=>$video_id), '*', MUST_EXIST);
                //new Chapter instance 
                $chapter = new stdClass();
                $chapter->resource_video_id = $video->id;
                //build url's
                $action_str = get_string('add_video_chapter', 'videoresource');
                $urlAction = new moodle_url($returnurl, array('action'=>$actionAddChapter, 'video'=>$video->id));
            } else if ($action == $actionEditChapter && isset($id)){     //edit existing type ($id parameter must be present in URL)
                //get Video Chapter
                $chapter = $DB->get_record('resource_video_chapters', array('id'=>$id), '*', MUST_EXIST);
                $video = $DB->get_record('resource_videos', array('id'=>$chapter->resource_video_id), '*', MUST_EXIST);
                //build url's
                $action_str = get_string('edit_video_chapter', 'videoresource');
                $urlAction = new moodle_url($returnurl, array('action'=>$actionEditChapter, 'id'=>$id));
            }
            //heads
            $head_str = !empty($video->title) ? $video->title : $video->internal_title;
            $urlView = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$video->id));
            //breadcrumbs
            $PAGE->navbar->add($head_str, $urlView);
            $PAGE->navbar->add($action_str);
            //create and show form instance
            $editform = new mod_resourcelib_form_editchapter($urlAction->out(false), array('chapter'=>$chapter));
            
            //form data process
            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                redirect($urlView);
            } else if ($data = $editform->get_data()) {
                if ($action == $actionAddChapter) {
                    $inserted_id = videoresource_add_chapter($data);
                    $success = ($inserted_id > 0);
                } else if ($action == $actionEditChapter && isset($id)) {
                    $success = videoresource_edit_chapter($data);
                }
                redirect($urlView);
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading($action_str);
            $editform->display();
            
            echo $OUTPUT->footer();
            break;  
            
        case $actionDelChapter: //DebugBreak();
            //breadcrumbs
            //$head_str = get_string('delete_video_chapter', 'videoresource');
            //$PAGE->navbar->add($head_str);
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected chapter from video, after confirmation
                $chapter = videoresource_get_chapter($id);

                /*if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($head_str);
                    $optionsyes = array('action'=>$actionDelChapter, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheck_chapter_fromvideo', 'videoresource', "'$chapter->title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    echo $OUTPUT->footer();
                } else*/ /*if (data_submitted() )*/{
                    if (videoresource_delete_chapter($chapter)) {
                        $url = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$chapter->resource_video_id));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification(get_string('deletednot', '', $chapter->title));
                    }
                }
            }
            break;
    }
