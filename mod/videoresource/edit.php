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
 * Provides the editing of Course Module Content (VideoResource Lists)
 *
 * @package mod_videoresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/videoresource/locallib.php');

/// Input params
$id = required_param('id', PARAM_INT);
//get action name
$action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
$action = (!empty($action) ? $action : 'index');
// param for moving and deleting
$itemid = optional_param('itemid', 0, PARAM_INT);
// params for adding
$add_item = optional_param('add_item', null, PARAM_TEXT);
//$video_id = optional_param('video_id', 0, PARAM_INT);

//actions list
$actionIndex = 'index';
$actionAddToList = 'addtolist';
$actionDelFromList = 'delfromlist';
$actionMoveDown = 'movedown';
$actionMoveUp = 'moveup';

/// Get main instances
$cm = get_coursemodule_from_id('videoresource', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$videoresource  = $DB->get_record('videoresource', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

// requires
require_login($course, false, $cm);
require_capability('mod/videoresource:edit', $context);
//require_capability('mod/resourcelib:edit', $context);

// if adding new item was submitted
if (!is_null($add_item)) {
    // get options of new video content
    $posted_item = optional_param_array('video', array(), PARAM_RAW);
    //create new instance
    $item = (object)$posted_item;  //$item = new stdClass();
    $item->resource_id = $cm->instance;
    $item->type = 'videoresource';
    //$item->instance_id = $video_id;
    $item->timecreated = time();
    //get next sort_order
    $sort_order = $DB->get_field('videoresource_content', 'MAX(sort_order)', array('resource_id'=>$item->resource_id));
    $item->sort_order = $sort_order + 1;
    $DB->insert_record('videoresource_content', $item);
}

// page params
$PAGE->set_url('/mod/videoresource/edit.php', array('id'=>$cm->id));
$returnurl = $CFG->wwwroot.'/mod/videoresource/edit.php';
$moodle_returnurl = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
$listurl = $CFG->wwwroot.'/mod/videoresource/video.php';
/// ----- Main process
switch($action) {
    case $actionIndex:
        $PAGE->navbar->add(get_string('edit'));
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('edit_activity_content', 'videoresource'));
        
        echo html_writer::tag('h3', get_string('listfield', 'videoresource'));
        //get list of course module content (set of lists)
        $items = videoresource_get_courcemodule_contents($videoresource);
        if (empty($items)) {
            echo $OUTPUT->notification(get_string('no_lists_in_course_module', 'videoresource'), 'redirectmessage');
        } else {
            $table = new html_table();
            $table->head = array();
            $table->head[] = get_string('name');
            //$table->head[] = get_string('type', 'videoresource');

            $table->size[2] = '120px';
            $strmoveup = get_string('moveup');
            $strmovedown = get_string('movedown');

            $first_item = reset($items);
            $last_item = end($items);
            foreach ($items as $item) {
                $buttons_column = array();
                // Move up
                if ($item->sort_order != $first_item->sort_order) {
                    $buttons_column[] = get_action_icon($returnurl . '?id=' . $id . '&amp;action=moveup&amp;itemid=' . $item->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
                } else {
                    $buttons_column[] = get_spacer();
                }
                // Move down
                if ($item->sort_order != $last_item->sort_order) {
                    $buttons_column[] = get_action_icon($returnurl . '?id=' . $id . '&amp;action=movedown&amp;itemid=' . $item->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
                } else {
                    $buttons_column[] = get_spacer();
                }
                // delete button
                $buttons_column[] = videoresource_confirm_deletebutton(
                    $returnurl . '?id=' . $id, $actionDelFromList, $item->id, 
                    get_string('deletecheck_item_fromlist', 'resourcelib', $item->name)
                );
                
                $url = new moodle_url($listurl, array('action'=>'view', 'id'=>$item->instance_id));
                $table->data[] = array(
                    html_writer::link($url, $item->name),
                    /*html_writer::empty_tag('img', array(
                        'src'=>$item->icon_path, 
                        'alt'=>$item->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;')),*/
                    implode(' ', $buttons_column)
                );
            }
            echo html_writer::table($table); //show table
        }
        
        // get lists, wich is not in course module
        $items = videoresource_get_notcource_lists($cm->instance);
        if (empty($items)) {
            $count = $DB->get_field('resource_videos', 'COUNT(*)', array());
            if ($count > 0) {
                echo $OUTPUT->notification(get_string('all_videos_in_course_module', 'videoresource'), 'redirectmessage');
            } else {
                $url = new moodle_url($CFG->wwwroot.'/mod/videoresource/video.php');
                echo $OUTPUT->notification(get_string('there_are_no_videos', 'videoresource', $url->out(false)), 'notifyproblem');
            }
        } else {
            // form for adding Video Resource
            echo html_writer::tag('h3', get_string('videoresource:addinstance', 'videoresource'));
            echo html_writer::start_tag('form', array('method'=>'POST', 'action'=>$moodle_returnurl->out(false)));
            
            echo html_writer::start_div();
            //echo html_writer::start_tag('select', array('id'=>'id_video_id', 'name'=>'video_id' , 'style'=>'width: 500px; float: left;'));
            echo html_writer::start_tag('select', array('id'=>'id_video_id', 'name'=>'video[instance_id]' , 'style'=>'width: 100%;'));
            foreach($items as $value=>$name) {
                echo html_writer::tag('option', $name, array('value'=>$value));
            }
            echo html_writer::end_tag('select');
            echo html_writer::end_div();
            
            echo html_writer::start_div();
            echo html_writer::tag('label', get_string('text_above', 'videoresource'), array('for'=>'id_textabove'));
            echo html_writer::tag('textarea', null, array('id'=>'id_textabove', 'name'=>'video[textabove]', 'style'=>'width: 100%;'));
            echo html_writer::end_div();
            
            echo html_writer::start_div();
            echo html_writer::tag('label', get_string('text_below', 'videoresource'), array('for'=>'id_textbelow'));
            echo html_writer::tag('textarea', null, array('id'=>'id_textbelow', 'name'=>'video[textbelow]', 'style'=>'width: 100%;'));
            echo html_writer::end_div();
            
            echo html_writer::tag('input', null, array('type'=>'submit', 'name'=>'add_item', 'value'=>get_string('add')));
            echo html_writer::end_tag('form');
        }
        // end of page
        echo $OUTPUT->footer();
        break;

    // Move Section Up in section List
    case $actionMoveUp:
    case $actionMoveDown:
        // get section in list
        $item = $DB->get_record('videoresource_content', array('id'=>$itemid));
        // build url for return
        $url = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
        if (confirm_sesskey()) {
            if ($action == $actionMoveDown)
                $result = resourcelib_item_move_down($item);  //move down
            else if ($action == $actionMoveUp)
                $result = videoresource_item_move_up($item);    //move up
            if (!$result) {
                print_error('cannotmoveitem', 'videoresource', $url->out(false), $id);
            }
        }
        redirect($url);
        break;
        
   case $actionDelFromList: 
        if (isset($itemid) && confirm_sesskey()) { // Delete a selected chapter from video, after confirmation
            $item = $DB->get_record('videoresource_content', array('id'=>$itemid));
            $url = new moodle_url($returnurl, array('action'=>$actionIndex, 'id'=>$cm->id));
            if (!$item) {
                print_error('cannotdelitem', 'videoresource', $url->out(false), $itemid);
            }
            if ($DB->delete_records('videoresource_content', array('id'=>$itemid))) {
                //$url = new moodle_url($returnurl, array('action'=>$actionIndex, 'id'=>$item->resourcelib_id));
                redirect($url);
            } else {
                echo $OUTPUT->notification(get_string('deletednot', '', $item->id));
            }
        }
        break;
}
