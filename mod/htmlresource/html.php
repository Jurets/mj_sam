<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
* Controller for Html Resource Module
* 
* @author  Yuriy Hetmanskiy
* @version 0.0.1
* @package    mod_htmlresource
* @copyright  2014 Jurets
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
//get sort param (if present)
$sort = optional_param('sort', 'internal_title', PARAM_TEXT);
$dir = optional_param('dir', 'ASC', PARAM_TEXT);

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
        $items = htmlresource_get_items($sort, $dir);
        if (!$items || empty($items)) {
            echo $OUTPUT->notification(get_string('no_resources', 'htmlresource'), 'redirectmessage');
        } else {
            if (!isset($buttons)) //default buttons
                $buttons = array('delete'=>'delete', 'edit'=>'edit');
            
            // build table header
            $table = new html_table();
            $table->head = array(
                htmlresource_get_sort_column($returnurl, 'internal_title', get_string('internal_name', 'htmlresource'), $sort, $dir), 
                htmlresource_get_sort_column($returnurl, 'title', get_string('html_title', 'htmlresource'), $sort, $dir),
                htmlresource_get_sort_column($returnurl, 'category', get_string('html_category', 'htmlresource'), $sort, $dir)
            );
            
            foreach ($items as $item) {
                $buttons_column = array();
                if (key_exists('delete', $buttons))
                    $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
                if (key_exists('edit', $buttons))
                    $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
                $table->data[] = array(
                    html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$item->id)), $item->internal_title),
                    //$item->internal_title, 
                    $item->title, 
                    $item->category, 
                    implode(' ', $buttons_column) 
                );
            }
            echo html_writer::table($table);
        }
        
        echo $OUTPUT->footer();
        break;

    /// View page
    case $actionView:
        $item = $DB->get_record('resource_html', array('id'=>$id), '*', MUST_EXIST); //get data from DB

        $head_str = !empty($item->title) ? $item->title : $item->internal_title;
        $PAGE->navbar->add($head_str);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($head_str);
        
        echo html_writer::start_tag('dl', array('class' => 'list'));
        echo html_writer::tag('dt', get_string('internal_name', 'htmlresource'));
        echo html_writer::tag('dd', $item->internal_title);
        echo html_writer::end_tag('dl');

        echo html_writer::start_tag('dl', array('class' => 'list'));
        echo html_writer::tag('dt', get_string('internal_notes', 'htmlresource'));
        echo html_writer::tag('dd', $item->internal_notes);
        echo html_writer::end_tag('dl');

        echo html_writer::start_tag('dl', array('class' => 'list'));
        echo html_writer::tag('dt', get_string('html_title', 'htmlresource'));
        echo html_writer::tag('dd', $item->title);
        echo html_writer::end_tag('dl');

        echo html_writer::start_tag('dl', array('class' => 'list'));
        echo html_writer::tag('dt', get_string('description_text', 'htmlresource'));
        echo html_writer::tag('dd', $item->description, array('style'=>'max-height: 300px; overflow: auto;'));
        echo html_writer::end_tag('dl');

        echo html_writer::start_tag('dl', array('class' => 'list'));
        echo html_writer::tag('dt', get_string('html_text', 'htmlresource'));
        echo html_writer::tag('dd', $item->html, array('style'=>'max-height: 300px; overflow: auto;'));
        echo html_writer::end_tag('dl');
        //show edit button
        show_editbutton(new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$item->id)), get_string('edit_html', 'htmlresource'));
        
        echo $OUTPUT->footer();
        break;
        
    case $actionAdd:
    case $actionEdit:
        require_once($CFG->dirroot.'/mod/htmlresource/form_edithtml.php'); //include form_edittype.php  
        
        $head_str = ($action == $actionAdd) ? get_string('add_html', 'htmlresource') : get_string('edit_html', 'htmlresource');
        
        if ($action == $actionAdd) { //add new type
            $PAGE->navbar->add($head_str);
            $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
            $item = null;        //empty data
        } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
            $PAGE->navbar->add($head_str);
            $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
            $item = htmlresource_get_item($id); //get data from DB
        }
        
        //build form
        $editform = new mod_htmlresource_form_edit($actionurl->out(false), array('item'=>$item)); //create form instance
        //$editform->is_submitted()
        if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
            $url = new moodle_url($returnurl, array('action' => $actionIndex));
            redirect($url);
        } else if ($data = $editform->get_data()) {
            $text = $data->html['text'];
            $format = $data->html['format'];
            unset($data->html);
            $data->html = $text;
            if ($action == $actionAdd) {
                $inserted_id = htmlresource_add_item($data);
                $success = isset($id);
            } else if (isset($id)){
                $success = htmlresource_edit_item($data);
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
        echo $OUTPUT->header();
        echo $OUTPUT->heading($head_str);
        
        if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
            $item = htmlresource_get_item($id); //get data from DB
            if (!$item) {
                echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $id), 'redirectmessage');
            } else if ($count = htmlresource_count_in_courses($item)) {
                echo $OUTPUT->notification(get_string('deletednot', '', $id) . ' ' . get_string('htmlresource_exists_in_course', 'htmlresource'), 'notifyproblem');
                echo html_writer::start_div('continuebutton');
                echo html_writer::link($returnurl, '('.get_string('continue').')');
                echo html_writer::end_div();
                //print_error('deletednot');
                //echo $OUTPUT->redirect_message($returnurl, get_string('deletednot', '', $id), 5, false);
            } else if ($confirm != md5($id)) {
                $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$item->internal_title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
            } else if (data_submitted() /*&& !$data->deleted*/){
                if (htmlresource_delete_item($item)) {
                    $url = new moodle_url($returnurl, array('action' => $actionIndex));
                    redirect($url);
                } else {
                    echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name), 'redirectmessage');
                }
            }
        }
        echo $OUTPUT->footer();
        break;
}
