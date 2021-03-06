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
 * Provides the editing of Course Module Content (ResourceLib Lists)
 *
 * @package mod_resourcelib
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/resourcelib/locallib.php');

/// Input params
$id = required_param('id', PARAM_INT);
//get action name
$action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
$action = (!empty($action) ? $action : 'index');
// param for moving and deleting
$itemid = optional_param('itemid', 0, PARAM_INT);
// params for adding
$add_item = optional_param('add_item', null, PARAM_TEXT);
$add_forum = optional_param('add_forum', null, PARAM_TEXT);
$add_questionnaire = optional_param('add_questionnaire', null, PARAM_TEXT);

$list_id = optional_param('list_id', 0, PARAM_INT);

//actions list
$actionIndex = 'index';
$actionAddToList = 'addtolist';
$actionDelFromList = 'delfromlist';
$actionMoveDown = 'movedown';
$actionMoveUp = 'moveup';

/// Get main instances
$cm = get_coursemodule_from_id('resourcelib', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$resourcelib  = $DB->get_record('resourcelib', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

// requires
require_login($course, false, $cm);
require_capability('mod/resourcelib:manage', $context);
//require_capability('mod/resourcelib:edit', $context);

// if adding new item was submitted
if (!is_null($add_item)) {
    //create new instance
    $item = new stdClass();
    $item->resourcelib_id = $cm->instance;
    $item->type = 'list';
    $item->instance_id = $list_id;
    //get next sort_order
    $sort_order = $DB->get_field('resourcelib_content', 'MAX(sort_order)', array('resourcelib_id'=>$item->resourcelib_id));
    $item->sort_order = $sort_order + 1;
    $DB->insert_record('resourcelib_content', $item);
}

// if adding forum was submitted
if (!is_null($add_forum)) {
    // firstly get already added forumforum
    $added_forum = $DB->get_record_select(
        'resourcelib_content', 'resourcelib_id = :resourcelib_id AND type = :type', array('resourcelib_id'=>$resourcelib->id, 'type'=>'forum'));
    // get options of new video content
    $posted_item = optional_param_array('resourcelib', array(), PARAM_RAW);
    // check post
    if (empty($posted_item['instance_id'])) { // if clearing of forum 
        $DB->delete_records('resourcelib_content', array('id'=>$added_forum->id));
    } else if ($added_forum) {  // if exist
        $item = $added_forum; // get it
        $item->instance_id = $posted_item['instance_id'];
        $item->timecreated = time();
        $DB->update_record('resourcelib_content', $item);
    } else { // else create new instance
        $item = (object)$posted_item;  //$item = new stdClass();
        $item->resourcelib_id = $cm->instance;
        $item->type = 'forum';
        $item->timecreated = time();
        //set next sort_order ????
        //$sort_order = $DB->get_field('videoresource_content', 'MAX(sort_order)', array('resource_id'=>$item->resource_id));
        //$item->sort_order = $sort_order + 1;
        $DB->insert_record('resourcelib_content', $item);
    }
}

// if adding forum was submitted
if (!is_null($add_questionnaire)) {
    // firstly get already added forumforum
    $added = $DB->get_record_select(
        'resourcelib_content', 'resourcelib_id = :resourcelib_id AND type = :type', array('resourcelib_id'=>$resourcelib->id, 'type'=>'questionnaire'));
    // get options of new video content
    $posted_item = optional_param_array('resourcelib', array(), PARAM_RAW);
    // check post
    if (empty($posted_item['instance_id'])) { // if clearing of forum 
        $DB->delete_records('resourcelib_content', array('id'=>$added->id));
    } else if ($added) {  // if exist
        $item = $added; // get it
        $item->instance_id = $posted_item['instance_id'];
        $item->timecreated = time();
        $DB->update_record('resourcelib_content', $item);
    } else { // else create new instance
        $item = (object)$posted_item;  //$item = new stdClass();
        $item->resourcelib_id = $cm->instance;
        $item->type = 'questionnaire';
        $item->timecreated = time();
        //set next sort_order ????
        //$sort_order = $DB->get_field('videoresource_content', 'MAX(sort_order)', array('resource_id'=>$item->resource_id));
        //$item->sort_order = $sort_order + 1;
        $DB->insert_record('resourcelib_content', $item);
    }
}

// page params
$PAGE->set_url('/mod/resourcelib/edit.php', array('id'=>$cm->id));
$returnurl = $CFG->wwwroot.'/mod/resourcelib/edit.php';
$moodle_returnurl = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
$listurl = $CFG->wwwroot.'/mod/resourcelib/lists.php';
/// ----- Main process
switch($action) {
    case $actionIndex:
        $PAGE->navbar->add(get_string('edit'));
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('edit') . ' ' . get_string('resourcelibfieldset', 'resourcelib'));
        //get list of course module content (set of lists)
        $items = resourcelib_get_courcemodule_contents($resourcelib);
        if (empty($items)) {
            echo $OUTPUT->notification(get_string('no_lists_in_course_module', 'resourcelib'), 'redirectmessage');
        } else {
            $table = new html_table();
            $table->head = array();
            $table->head[] = get_string('name');
            $table->head[] = get_string('type', 'resourcelib');

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
                $buttons_column[] = resourcelib_confirm_deletebutton(
                    $returnurl . '?id=' . $id, $actionDelFromList, $item->id, 
                    get_string('deletecheck_item_fromlist', 'resourcelib', $item->name)
                );
                
                $url = new moodle_url($listurl, array('action'=>'view', 'id'=>$item->instance_id));
                $table->data[] = array(
                    html_writer::link($url, $item->name),
                    html_writer::empty_tag('img', array(
                        'src'=>$item->icon_path, 
                        'alt'=>$item->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;')),
                    implode(' ', $buttons_column)
                );
            }
            echo html_writer::table($table); //show table
        }
        
        // get lists, wich is not in course module
        $items = resourcelib_get_notcource_lists($cm->instance);
        if (empty($items)) {
            $count = $DB->get_field('resource_lists', 'COUNT(*)', array());
            if ($count > 0) {
                echo $OUTPUT->notification(get_string('all_lists_in_course_module', 'resourcelib'), 'redirectmessage');
            } else {
                $url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/lists.php');
                echo $OUTPUT->notification(get_string('there_are_no_lists', 'resourcelib', $url->out(false)), 'notifyproblem');
            }
        } else {
            // form for adding
            echo html_writer::start_tag('form', array('method'=>'POST', 'action'=>$moodle_returnurl->out(false)));
            echo html_writer::start_tag('select', array('id'=>'id_list_id', 'name'=>'list_id' , 'style'=>'width: 500px; float: left;'));
            foreach($items as $value=>$name) {
                echo html_writer::tag('option', $name, array('value'=>$value));
            }
            echo html_writer::end_tag('select');
            echo html_writer::tag('input', null, array('type'=>'submit', 'name'=>'add_item', 'value'=>get_string('add')));
            echo html_writer::end_tag('form');
        }
        
        // ---- form for adding forum to video activity page
        echo html_writer::tag('h3', get_string('resourcelib:addforum', 'resourcelib'));
        // get added forum
        $added_forum = $DB->get_record_select(
            'resourcelib_content', 'resourcelib_id = :resourcelib_id AND type = :type', array('resourcelib_id'=>$resourcelib->id, 'type'=>'forum'), 'instance_id');
        // get forums from current course
        $forums = $DB->get_records_menu('forum', array('course'=>$course->id), null, 'id, name');
        if (empty($forums)) {
            echo $OUTPUT->notification(get_string('there_are_no_forums', 'resourcelib', $url->out(false)), 'redirectmessage');
        } else {
            echo html_writer::start_tag('form', array('method'=>'POST', 'action'=>$moodle_returnurl->out(false)));

            //echo html_writer::start_div();
            echo html_writer::start_tag('select', array('id'=>'id_forum_id', 'name'=>'resourcelib[instance_id]' /*, 'style'=>'width: 100%;'*/));
            $attributes = array('value'=>'');
            if (!$added_forum) {
                $attributes['selected'] = '';
            }
            echo html_writer::tag('option', '', $attributes);
            foreach($forums as $value=>$name) {
                $attributes = array('value'=>$value);
                if ($added_forum && $value == $added_forum->instance_id) {
                    $attributes['selected'] = '';
                }
                echo html_writer::tag('option', $name, $attributes);
            }
            echo html_writer::end_tag('select');
            //echo html_writer::end_div();

            echo html_writer::tag('input', null, array('type'=>'submit', 'name'=>'add_forum', 'value'=>get_string('ok')));
            echo html_writer::end_tag('form');
        }
        
        // ---- form for adding questionnaire 
        echo html_writer::tag('h3', get_string('resourcelib:addquestionnaire', 'resourcelib'));
        // get added questionnaire
        $added_questionnaire = $DB->get_record_select(
            'resourcelib_content', 'resourcelib_id = :resourcelib_id AND type = :type', array('resourcelib_id'=>$resourcelib->id, 'type'=>'questionnaire'), 'instance_id');
        // get questionnaire from current course
        $sql = '
            SELECT cm.id, q.name 
            FROM {course_modules} cm LEFT JOIN 
                 {questionnaire} q ON q.id = cm.instance LEFT JOIN 
                 {modules} m ON m.id = cm.module
            WHERE cm.course = :course AND m.name = :module
        ';
        $questionnaires = $DB->get_records_sql_menu($sql, array('course'=>$course->id, 'module'=>'questionnaire'));
        if (empty($questionnaires)) {
            echo $OUTPUT->notification(get_string('there_are_no_questionnaires', 'resourcelib', $url->out(false)), 'redirectmessage');
        } else {
            echo html_writer::start_tag('form', array('method'=>'POST', 'action'=>$moodle_returnurl->out(false)));

            //echo html_writer::start_div();
            echo html_writer::start_tag('select', array('id'=>'id_questionnaire_id', 'name'=>'resourcelib[instance_id]' /*, 'style'=>'width: 100%;'*/));
            $attributes = array('value'=>'');
            if (!$added_questionnaire) {
                $attributes['selected'] = '';
            }
            echo html_writer::tag('option', '', $attributes);
            foreach($questionnaires as $value=>$name) {
                $attributes = array('value'=>$value);
                if ($added_questionnaire && $value == $added_questionnaire->instance_id) {
                    $attributes['selected'] = '';
                }
                echo html_writer::tag('option', $name, $attributes);
            }
            echo html_writer::end_tag('select');
            //echo html_writer::end_div();

            echo html_writer::tag('input', null, array('type'=>'submit', 'name'=>'add_questionnaire', 'value'=>get_string('ok')));
            echo html_writer::end_tag('form');
        }
        
        // end of page
        echo $OUTPUT->footer();
        break;

    // Move Section Up in section List
    case $actionMoveUp:
    case $actionMoveDown:
        // get section in list
        $item = $DB->get_record('resourcelib_content', array('id'=>$itemid));
        // build url for return
        $url = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
        if (confirm_sesskey()) {
            if ($action == $actionMoveDown)
                $result = resourcelib_item_move_down($item);  //move down
            else if ($action == $actionMoveUp)
                $result = resourcelib_item_move_up($item);    //move up
            if (!$result) {
                print_error('cannotmoveitem', 'resourcelib', $url->out(false), $id);
            }
        }
        redirect($url);
        break;
        
   case $actionDelFromList: 
        if (isset($itemid) && confirm_sesskey()) { // Delete a selected chapter from video, after confirmation
            $item = $DB->get_record('resourcelib_content', array('id'=>$itemid));
            $url = new moodle_url($returnurl, array('action'=>$actionIndex, 'id'=>$cm->id));
            if (!$item) {
                print_error('cannotdelitem', 'resourcelib', $url->out(false), $itemid);
            }
            if ($DB->delete_records('resourcelib_content', array('id'=>$itemid))) {
                //$url = new moodle_url($returnurl, array('action'=>$actionIndex, 'id'=>$item->resourcelib_id));
                redirect($url);
            } else {
                echo $OUTPUT->notification(get_string('deletednot', '', $item->id));
            }
        }
        break;
}
