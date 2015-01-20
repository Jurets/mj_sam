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
     * Provides the interface for overall authoring of lessons
     *
     * @package mod_lesson
     * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     **/

    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/resourcelib/locallib.php');

    /// Input params
    $id = required_param('id', PARAM_INT);
    //get action name
    $action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
    $action = (!empty($action) ? $action : 'index');

    //actions list
    $actionIndex = 'index';
    $actionAddSection = 'addtolist';
    $actionDelSection = 'delfromlist';
    $actionMoveDown = 'movedown';
    $actionMoveUp = 'moveup';

    /// Get main instances
    $cm = get_coursemodule_from_id('resourcelib', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $resourcelib  = $DB->get_record('resourcelib', array('id' => $cm->instance), '*', MUST_EXIST);

    require_login($course, false, $cm);

    $context = context_module::instance($cm->id);
    require_capability('mod/resourcelib:manage', $context);

    //$mode    = optional_param('mode', get_user_preferences('lesson_view', 'collapsed'), PARAM_ALPHA);
    $PAGE->set_url('/mod/resourcelib/edit.php', array('id'=>$cm->id/*,'mode'=>$mode*/));

    /*if ($mode != get_user_preferences('lesson_view', 'collapsed') && $mode !== 'single') {
        set_user_preference('lesson_view', $mode);
    }*/
    $returnurl = $CFG->wwwroot.'/mod/resourcelib/edit.php';
    //$lessonoutput = $PAGE->get_renderer('mod_resourcelib');
    $PAGE->navbar->add(get_string('edit'));
    //echo $lessonoutput->header($lesson, $cm, $mode, false, null, get_string('edit', 'lesson'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('edit') . ' ' . get_string('resourcelibfieldset', 'resourcelib'));

    //require_capability('mod/resourcelib:edit', $context);

    switch($action) {
        case $actionIndex:
            //get list of course module content (set of lists)
            $items = resourcelib_get_courcemodule_contents($resourcelib);
            //$lists = resourcelib_get_lists($sort, $dir);

            $table = new html_table();
            $table->head = array();
            //$table->head[] = resourcelib_get_column_title($returnurl, 'name', get_string('name'), $sort, $dir);
            //$table->head[] = resourcelib_get_column_title($returnurl, 'display_name', get_string('display_name', 'resourcelib'), $sort, $dir);
            $table->head[] = get_string('name');
            //$table->head[] = get_string('section_count', 'resourcelib');

            $table->size[1] = '120px';
            $strmoveup = get_string('moveup');
            $strmovedown = get_string('movedown');

            $first_item = reset($items);
            $last_item = end($items);

            foreach ($items as $item) {
                $buttons_column = array();
                if ($item->sort_order != $first_item->sort_order) {
                    $buttons_column[] = get_action_icon($returnurl . '?id=' . $id . 'action=moveup&amp;itemid=' . $item->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
                } else {
                    $buttons_column[] = get_spacer();
                }
                // Move down.
                if ($item->sort_order != $last_item->sort_order) {
                    $buttons_column[] = get_action_icon($returnurl . '?id=' . $id . 'action=movedown&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
                } else {
                    $buttons_column[] = get_spacer();
                }
                $buttons_column[] = create_deletebutton($returnurl, 'delete', $item->id);

                $table->data[] = array(
                    //html_writer::link(new moodle_url($returnurl, array('action'=>$actionView, 'id'=>$list->id)), $list->name),
                    $item->name,                          
                    /*html_writer::empty_tag('img', array(
                        'src'=>$list->icon_path, 
                        'alt'=>$list->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;')),*/
                    //($list->s_count ? $list->s_count : ''), 
                    implode(' ', $buttons_column)
                );
            }
            //add type button
            //resourcelib_show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('addlist', 'resourcelib'));
            //table with types data
            echo html_writer::table($table);
            break;

        // Move Section Up in section List
        case $actionMoveUp:
        case $actionMoveDown:
            // get section in list
            $item = $DB->get_record('resource_list_sections', array('id'=>$id));
            // build url for return
            $url = new moodle_url($returnurl, array('action' => $actionView, 'id'=>$section->resource_list_id));
            if (confirm_sesskey()) {
                if ($action == $actionMoveSectionDown)
                    $result = resourcelib_section_move_down($section);  //move down
                else if ($action == $actionMoveSectionUp)
                    $result = resourcelib_section_move_up($section);    //move up
                if (!$result) {
                    print_error('cannotmovesection', 'resourcelib', $url->out(false), $id);
                }
            }
            redirect($url);
            break;

    }



    echo $OUTPUT->footer();
