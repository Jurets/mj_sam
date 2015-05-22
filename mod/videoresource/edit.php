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
$add_forum = optional_param('add_forum', null, PARAM_TEXT);
$add_questionnaire = optional_param('add_questionnaire', null, PARAM_TEXT);

//actions list
$actionIndex = 'index';
$actionAdd = 'add';
$actionEdit = 'edit';
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

$returnurl = $CFG->wwwroot.'/mod/videoresource/edit.php';
$moodle_returnurl = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
$listurl = $CFG->wwwroot.'/mod/videoresource/video.php';

// if adding forum was submitted
if (!is_null($add_forum)) {
    // firstly get already added forumforum
    $added_forum = $DB->get_record_select(
        'videoresource_content', 'resource_id = :resource_id AND type = :type', array('resource_id'=>$videoresource->id, 'type'=>'forum'));
    // get options of new video content
    $posted_item = optional_param_array('video', array(), PARAM_RAW);
    // check post
    if (empty($posted_item['instance_id'])) { // if clearing of forum 
        $DB->delete_records('videoresource_content', array('id'=>$added_forum->id));
    } else if ($added_forum) {  // if exist
        $item = $added_forum; // get it
        $item->instance_id = $posted_item['instance_id'];
        $item->timecreated = time();
        $DB->update_record('videoresource_content', $item);
    } else { // else create new instance
        $item = (object)$posted_item;  //$item = new stdClass();
        $item->resource_id = $cm->instance;
        $item->type = 'forum';
        $item->timecreated = time();
        //set next sort_order ????
        //$sort_order = $DB->get_field('videoresource_content', 'MAX(sort_order)', array('resource_id'=>$item->resource_id));
        //$item->sort_order = $sort_order + 1;
        $DB->insert_record('videoresource_content', $item);
    }
}

// if adding forum was submitted
if (!is_null($add_questionnaire)) {
    // firstly get already added forumforum
    $added = $DB->get_record_select(
        'videoresource_content', 'resource_id = :resource_id AND type = :type', array('resource_id'=>$videoresource->id, 'type'=>'questionnaire'));
    // get options of new video content
    $posted_item = optional_param_array('video', array(), PARAM_RAW);
    // check post
    if (empty($posted_item['instance_id'])) { // if clearing of forum 
        $DB->delete_records('videoresource_content', array('id'=>$added->id));
    } else if ($added) {  // if exist
        $item = $added; // get it
        $item->instance_id = $posted_item['instance_id'];
        $item->timecreated = time();
        $DB->update_record('videoresource_content', $item);
    } else { // else create new instance
        $item = (object)$posted_item;  //$item = new stdClass();
        $item->resource_id = $cm->instance;
        $item->type = 'questionnaire';
        $item->timecreated = time();
        $DB->insert_record('videoresource_content', $item);
    }
}

// page params
$PAGE->set_url('/mod/videoresource/edit.php', array('id'=>$cm->id));
$PAGE->set_cacheable(false);

/// ----- Main process
switch($action) {
    case $actionIndex:
        $PAGE->navbar->add(get_string('edit'));
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('edit_activity_content', 'videoresource'));
        
        echo html_writer::tag('h3', get_string('listfield', 'videoresource'));
        //get list of course module content (set of videos)
        $items = videoresource_get_courcemodule_contents($videoresource);
        if (empty($items)) {
            echo $OUTPUT->notification(get_string('no_lists_in_course_module', 'videoresource'), 'redirectmessage');
        } else {
            $table = new html_table();
            $table->head = array();
            $table->head[] = get_string('name');

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
                // edit button
                $buttons_column[] = html_writer::link(
                    new moodle_url($returnurl, array('id'=>$id, 'action'=>'edit', 'itemid'=>$item->id, 'sesskey'=>sesskey())), 
                    html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>get_string('edit'), 'class'=>'iconsmall')), 
                    array('title'=>get_string('edit')));
                // delete button
                $buttons_column[] = videoresource_confirm_deletebutton(
                    $returnurl . '?id=' . $id, $actionDelFromList, $item->id, 
                    get_string('deletecheck_item_fromlist', 'resourcelib', $item->name)
                );
                
                $url = new moodle_url($listurl, array('action'=>'view', 'id'=>$item->instance_id));
                $table->data[] = array(
                    html_writer::link($url, $item->name),
                    implode(' ', $buttons_column)
                );
            }
            echo html_writer::table($table); //show table
        }
        
        // get videos, wich is not in this course module
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
            $url = new moodle_url($returnurl, array('id'=>$id, 'action' => $actionAdd));
            echo html_writer::div(
                html_writer::tag('a', $OUTPUT->pix_icon('t/add', '') . ' ' . get_string('additem', 'videoresource'), array('href' => $url->out(false))), 'mdl-right');
        }
        
        // ---- form for adding forum to video activity page
        echo html_writer::tag('h3', get_string('videoresource:addforum', 'videoresource'));
        // get added forum
        $added_forum = $DB->get_record_select('videoresource_content', 'resource_id = :resource_id AND type = :type', 
            array('resource_id'=>$videoresource->id, 'type'=>'forum'), 'instance_id');
        // get forums from current course
        $forums = $DB->get_records_menu('forum', array('course'=>$course->id), null, 'id, name');
        if (empty($forums)) {
            echo $OUTPUT->notification(get_string('there_are_no_forums', 'videoresource', $url->out(false)), 'redirectmessage');
        } else {
            // build simple form
            echo html_writer::start_tag('form', array('method'=>'POST', 'action'=>$moodle_returnurl->out(false)));
            echo html_writer::start_tag('select', array('id'=>'id_forum_id', 'name'=>'video[instance_id]' /*, 'style'=>'width: 100%;'*/));
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
            echo html_writer::tag('input', null, array('type'=>'submit', 'name'=>'add_forum', 'value'=>get_string('ok')));
            echo html_writer::end_tag('form');
        }
        
        // ---- form for adding questionnaire 
        echo html_writer::tag('h3', get_string('videoresource:addquestionnaire', 'videoresource'));
        // get added questionnaire
        $added_questionnaire = $DB->get_record_select(
            'videoresource_content', 'resource_id = :resource_id AND type = :type', array('resource_id'=>$videoresource->id, 'type'=>'questionnaire'), 'instance_id');
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
            echo $OUTPUT->notification(get_string('there_are_no_questionnaires', 'videoresource', $url->out(false)), 'redirectmessage');
        } else {
            // build simple form
            echo html_writer::start_tag('form', array('method'=>'POST', 'action'=>$moodle_returnurl->out(false)));
            echo html_writer::start_tag('select', array('id'=>'id_questionnaire_id', 'name'=>'video[instance_id]' /*, 'style'=>'width: 100%;'*/));
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
                redirect($url);
            } else {
                echo $OUTPUT->notification(get_string('deletednot', '', $item->id));
            }
        }
        break;
        
    case $actionAdd:
    case $actionEdit:
        require_once($CFG->dirroot.'/mod/videoresource/form_addvideoresource.php');
        // set title
        $head_str = ($action == $actionAdd) ? get_string('additem', 'videoresource') : get_string('edititem', 'videoresource');
        // analize - add or edit
        if ($action == $actionAdd) { //add new type
            $PAGE->navbar->add($head_str);
            $item = null;        //empty data
            // get videos, wich is not in this course module
            $items = videoresource_get_notcource_lists($cm->instance);
            $actionurl = new moodle_url($returnurl, array('id'=>$id, 'action' => $actionAdd));
        } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
            $PAGE->navbar->add($head_str);
            $item = $DB->get_record('videoresource_content', array('id'=>$itemid)); //get data from DB
            // get videos, wich is not in this course module
            $items = videoresource_get_notcource_lists($cm->instance, $item->instance_id);
            $actionurl = new moodle_url($returnurl, array('id'=>$id, 'action' => $actionEdit, 'itemid'=>$item->id));
        }
        //build form
        $editform = new mod_videoresource_form_addvideoresource($actionurl->out(false), array('item'=>$item, 'items'=>$items)); //create form instance
        
        //$editform->is_submitted()
        if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
            $url = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
            redirect($url);
        } else if ($data = $editform->get_data()) {
            if ($action == $actionAdd) {
                $item = new stdClass();
                $item->resource_id = $cm->instance;
                //get next sort_order
                $sort_order = $DB->get_field('videoresource_content', 'MAX(sort_order)', array('resource_id'=>$item->resource_id));
                $item->sort_order = $sort_order + 1;  //set sort order
                $item->timecreated = time(); //set create time
                $item->type = 'videoresource'; //set type of content (resource)
                //$inserted_id = resourcelib_add_resource($data);
                $success = isset($id);
            } 
            // set field values:
            $item->textabove = $data->textabove['text'];
            $item->textbelow = $data->textbelow['text'];
            $item->instance_id = $_POST['instance_id'];
            if ($action == $actionAdd) {
                $success = $DB->insert_record('videoresource_content', $item);
            } else {
                $success = $DB->update_record('videoresource_content', $item);
            }
            if ($success){  //call create Resource Type function
                $url = new moodle_url($returnurl, array('action' => $actionIndex, 'id'=>$cm->id));
                redirect($url);
            }
        }
        //show form page
        echo $OUTPUT->header();
        echo $OUTPUT->heading($head_str);
        $editform->display();
        echo $OUTPUT->footer();
        break;
        
}
