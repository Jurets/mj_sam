<?php
    /**
    * Controller for Video Resource Module
    * 
    * @author  Yuriy Hetmanskiy
    * @version 0.0.1
    * @license -
    * @package htmlresource
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
    
    /// Security
    $systemcontext = context_system::instance();
    require_login();
    require_capability('moodle/site:config', $systemcontext);

    /// Build page
    $returnurl = $CFG->wwwroot.'/mod/htmlresource/html.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);

    //page layout
    $PAGE->set_pagelayout('admin');     
    //breadcrumbs
    $PAGE->navbar->add(get_string('administration', 'htmlresource'), new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettinghtmlresource'))); 
    
    $head_index = get_string('manage_html', 'htmlresource');
    if ($action == $actionIndex) {
        $PAGE->navbar->add($head_index);
    } else {
        $PAGE->navbar->add($head_index, new moodle_url($returnurl));
    }

    switch($action) {
        ///  Index page
        case $actionIndex:
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_index);
            //add type button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('add_html', 'htmlresource'));
            //show table with items data
            $items = htmlresource_get_items();
            //show_resource_items(htmlresource_get_videos(), $returnurl);
            if (!$items || empty($items)) {
                echo $OUTPUT->notification(get_string('no_resources', 'htmlresource'), 'redirectmessage');
            } else {
                if (!isset($buttons)) //default buttons
                    $buttons = array('delete'=>'delete', 'edit'=>'edit');
                
                $table = new html_table();
                $table->head = array(
                    get_string('internal_name', 'htmlresource'), 
                    get_string('html_title', 'htmlresource')
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

        /// View page
        case $actionView:
            $html = $DB->get_record('resource_html', array('id'=>$id), '*', MUST_EXIST); //get data from DB

            $head_str = !empty($html->title) ? $html->title : $html->internal_title;
            $PAGE->navbar->add($head_str);
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_str);
            
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('videoid', 'htmlresource'));
            echo html_writer::tag('dd', $html->video_id);
            echo html_writer::end_tag('dl');
            
            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('internal_name', 'htmlresource'));
            echo html_writer::tag('dd', $html->internal_title);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('internal_notes', 'htmlresource'));
            echo html_writer::tag('dd', $html->internal_notes);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('video_title', 'htmlresource'));
            echo html_writer::tag('dd', $html->title);
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('description_text', 'htmlresource'));
            echo html_writer::tag('dd', $html->description, array('style'=>'max-height: 300px; overflow: auto;'));
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('podcast_url', 'htmlresource'));
            echo html_writer::tag('dd', html_writer::link(new moodle_url($html->podcast_url), $html->podcast_url, array('target'=>'_blank')));
            echo html_writer::end_tag('dl');

            echo html_writer::start_tag('dl', array('class' => 'list'));
            echo html_writer::tag('dt', get_string('transcript', 'htmlresource'));
            echo html_writer::tag('dd', $html->transcript, array('style'=>'max-height: 300px; overflow: auto;'));
            echo html_writer::end_tag('dl');
            //show edit button
            show_editbutton(new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$html->id)), get_string('edit_video', 'htmlresource'));
            //
            echo html_writer::tag('hr', '');
            //add section button
            show_addbutton(new moodle_url($returnurl, array('action' => $actionAddChapter, 'video'=>$id)), get_string('add_video_chapter', 'htmlresource'));
            
            //chapters in table format
            /*$items = get_video_chapters($html);
            if (!$items || empty($items)) {
                echo $OUTPUT->notification(get_string('no_chapters', 'htmlresource'), 'redirectmessage');
            } else {
                if (!isset($buttons)) //default buttons
                    $buttons = array('delete'=>$actionDelChapter, 'edit'=>$actionEditChapter);
                
                $table = new html_table();
                $table->head = array(
                    get_string('chapter_timecode', 'htmlresource'), 
                    get_string('chapter_title', 'htmlresource')
                );
                
                foreach ($items as $item) {
                    $buttons_column = array();
                    if (key_exists('delete', $buttons))
                        $buttons_column[] = htmlresource_show_deletebutton(
                            $returnurl, $buttons['delete'], $item->id, 
                            get_string('delete_video_chapter', 'htmlresource').'?'
                        );
                    if (key_exists('edit', $buttons))
                        $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
                    $table->data[] = array(
                        htmlresource_time_convert($item->timecode), 
                        $item->title, 
                        implode(' ', $buttons_column) 
                    );
                }
                echo html_writer::table($table);
            } */
            
            echo $OUTPUT->footer();
            break;
            
        case $actionAdd:
        case $actionEdit:
            require_once($CFG->dirroot.'/mod/htmlresource/form_edithtml.php'); //include form_edittype.php  
            
            $head_str = ($action == $actionAdd) ? get_string('add_html', 'htmlresource') : get_string('edit_html', 'htmlresource');
            
            if ($action == $actionAdd) { //add new type
                $PAGE->navbar->add($head_str);
                $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
                $html = null;        //empty data
            } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
                $PAGE->navbar->add($head_str);
                $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
                $html = htmlresource_get_video($id); //get data from DB
            }
            
            //build form
            $editform = new mod_htmlresource_form_editvideo($actionurl->out(false), array('video'=>$html)); //create form instance
            //$editform->is_submitted()
            if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
                $url = new moodle_url($returnurl, array('action' => $actionIndex));
                redirect($url);
            } else if ($data = $editform->get_data()) {
                if ($action == $actionAdd) {
                    $inserted_id = htmlresource_add_video($data);
                    $success = isset($id);
                } else if (isset($id)){
                    $success = htmlresource_edit_video($data);
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
            $head_str = get_string('delete_html', 'htmlresource');
            //breadcrumbs
            $PAGE->navbar->add($head_str);
            
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
                $item = htmlresource_get_video($id); //get data from DB
                
                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($head_str);
                    //before delete do check existing of video resources in any course
                    if (isset($item->c_count) && $item->c_count > 0) {
                        $str = get_string('deletednot', '', $item->title) . ' ' . get_string('htmlresource_exists_in_course', 'htmlresource');
                        echo $OUTPUT->notification($str);
                    } else {
                        $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$item->internal_title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    }
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (htmlresource_delete_video($item)) {
                        $url = new moodle_url($returnurl, array('action' => $actionIndex));
                        redirect($url);
                    } else {
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name));
                    }
                }
            }
            break;

        /*case $actionAddChapter:
        case $actionEditChapter: 
            require_once($CFG->dirroot.'/mod/htmlresource/form_editchapter.php'); //include form_edittype.php  
            
            if ($action == $actionAddChapter) { //add new type
                //check input param
                $html_id = optional_param('html', -1, PARAM_INT);
                $html = $DB->get_record('resource_videos', array('id'=>$html_id), '*', MUST_EXIST);
                //new Chapter instance 
                $chapter = new stdClass();
                $chapter->resource_video_id = $html->id;
                //build url's
                $action_str = get_string('add_video_chapter', 'htmlresource');
                $urlAction = new moodle_url($returnurl, array('action'=>$actionAddChapter, 'video'=>$html->id));
            } else if ($action == $actionEditChapter && isset($id)){     //edit existing type ($id parameter must be present in URL)
                //get Video Chapter
                $chapter = $DB->get_record('resource_video_chapters', array('id'=>$id), '*', MUST_EXIST);
                $html = $DB->get_record('resource_videos', array('id'=>$chapter->resource_video_id), '*', MUST_EXIST);
                //build url's
                $action_str = get_string('edit_video_chapter', 'htmlresource');
                $urlAction = new moodle_url($returnurl, array('action'=>$actionEditChapter, 'id'=>$id));
            }
            //heads
            $head_str = !empty($html->title) ? $html->title : $html->internal_title;
            $urlView = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$html->id));
            $urlAddChapter = new moodle_url($returnurl, array('action'=>$actionAddChapter, 'video'=>$html->id));
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
                    $inserted_id = htmlresource_add_chapter($data);
                    $success = ($inserted_id > 0);
                } else if ($action == $actionEditChapter && isset($id)) {
                    $success = htmlresource_edit_chapter($data);
                }
                if (isset($data->submitbutton))
                    redirect($urlView);
                else 
                    redirect($urlAddChapter);
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading($action_str);
            $editform->display();
            
            echo $OUTPUT->footer();
            break;*/  
            
        //delete video chapter: after js-confirmation
        /*case $actionDelChapter:
            if (isset($id) && confirm_sesskey()) { // Delete a selected chapter from video, after confirmation
                $chapter = htmlresource_get_chapter($id);
                if (htmlresource_delete_chapter($chapter)) {
                    $url = new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$chapter->resource_video_id));
                    redirect($url);
                } else {
                    echo $OUTPUT->notification(get_string('deletednot', '', $chapter->title));
                }
            }
            break;*/
        
        //show transcript of Video Resource
        case $actionTranscript:
            $html = $DB->get_record('resource_html', array('id'=>$id), '*', MUST_EXIST); //get data from DB

            $head_str = !empty($html->title) ? $html->title : $html->internal_title;
            $PAGE->navbar->add($head_str);
            echo $OUTPUT->header();
            echo $OUTPUT->heading($head_str);
            //header
            echo html_writer::tag('h3', get_string('video_transcript', 'htmlresource'));
            //transcript text
            echo html_writer::start_div('panel panel-default');
            echo html_writer::tag('div', $html->transcript);
            echo html_writer::end_div();
            //footer
            echo $OUTPUT->footer();
            break;
    }
