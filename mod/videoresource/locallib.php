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
* Internal library of functions for module videoresource
*
* All the videoresource specific functions, needed to implement the module
* logic, should go here. Never include this file from your lib.php!
*
* @package    mod_videoresource
* @copyright  2014 Jurets
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/*
* Does something really useful with the passed things
*
* @param array $things
* @return object
*function videoresource_do_something_useful(array $things) {
*    return new stdClass();
*}
*/

/**
* Show add button (usually near data table)
* 
* @param mixed $url
* @param mixed $label
* @param mixed $attributes
*/
function show_addbutton($url, $label, $attributes = array('class' => 'mdl-right')) {
    global $OUTPUT;
    echo html_writer::start_tag('div', $attributes);
    echo html_writer::tag('a', $OUTPUT->pix_icon('t/add', '') . ' ' . $label, array('href' => $url->out(false)));
    echo html_writer::end_tag('div');
}

/**
* Show edit button (usually near data table)
* 
* @param mixed $url
* @param mixed $label
* @param mixed $attributes
*/
function show_editbutton($url, $label, $attributes = array('class' => 'mdl-right')) {
    global $OUTPUT;
    echo html_writer::start_tag('div', $attributes);
    echo html_writer::tag('a', $OUTPUT->pix_icon('t/editstring', '') . ' ' . $label, array('href' => $url->out(false)));
    echo html_writer::end_tag('div');
}

/**
* create a delete button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @return string
*/
function create_deletebutton($url, $action, $id, $confirm = false) {
    global $OUTPUT;
    
    $strdelete = get_string('delete');
    $link = html_writer::link(
        new moodle_url($url, array('action'=>$action, 'id'=>$id, 'sesskey'=>sesskey())), 
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), 
        array('title'=>$strdelete)
    );
    if ($confirm)
        $link->add_action('click', 'confirm_dialog', array('message' => 'Are you sure?'));
    return $link;
}

/**
* create a delete button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @param mixed $confirm
* @return string
*/
function videoresource_show_deletebutton($url, $action, $id, $confirm = false) {
    global $OUTPUT;
    $strdelete = get_string('delete');
    $url = new moodle_url($url, array('action'=>$action, 'id'=>$id, 'sesskey'=>sesskey()));
    $text = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall'));
    $link = new action_link($url, $text); //create action link
    $action = new component_action('click', 'M.util.show_confirm_dialog', array('message' => $confirm)); //attach confirm dialog
    $link->add_action($action);
    return $OUTPUT->render($link);
}

/**
* create a edit button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @return string
*/
function create_editbutton($url, $action = 'edit', $id) {
    global $OUTPUT;
    
    $stredit   = get_string('edit');
    return html_writer::link(
        new moodle_url($url, array('action'=>$action, 'id'=>$id)), 
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>$stredit, 'class'=>'iconsmall')), 
        array('title'=>$stredit)
    );
}

/**
* get all Video Resource Instances
* 
*/
function videoresource_get_videos() {
    global $DB;
    return $DB->get_records_sql('SELECT * FROM {resource_videos}');
}

/**
* get Video Resource items for selecting
* 
* @return array
*/
function videoresource_get_videos_select() {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    $sql = 'SELECT v.id, v.internal_title
            FROM {resource_videos} v';
    $items = $DB->get_records_sql_menu($sql);
    if (!$items)
        $items = array();
    return $items;
}

/**
* get chapters by Video Resource
* 
*/
function get_video_chapters($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_video_chapters() detected');
    }
    $items = $DB->get_records('resource_video_chapters', array('resource_video_id'=>$data->id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* add new Resource to database
* 
* @param mixed $data - Resource Instance
*/
function videoresource_add_video($data) {
    global $DB;
    return $DB->insert_record('resource_videos', $data);
}

/**
* update Resource in database
* 
* @param mixed $data - Resource Instance
*/
function videoresource_edit_video($data) {
    global $DB;
    return $DB->update_record('resource_videos', $data);
}

/**
* get Video Resource record from database
* 
* @param mixed $id - Resource ID
*/
function videoresource_get_video($id) {
    global $DB;
    /*$chapters = $DB->get_records_sql('
        SELECT 
        FROM {resource_video_chapters} vc LEFT JOIN
             {resource_videos} v ON v.
    ', array('id'=>$id));*/
    $video = $DB->get_record('resource_videos', array('id'=>$id));
    $video->chapters = $DB->get_records('resource_video_chapters', array('resource_video_id'=>$video->id));
    return $video;
}

/**
* delete Video Resource from database
* 
* @param mixed $data - instance of Video Resource
* @return bool
*/
function videoresource_delete_video($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in videoresource_delete_video() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$item = $DB->get_record('resource_videos', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Video Resource.');
        return false;
    }
    return $DB->delete_records('resource_videos', array('id' => $data->id));
}

/**
* get Chapter instance
* 
* @param mixed $id - chapter ID
*/
function videoresource_get_chapter($id) {
    global $DB;
    return $DB->get_record('resource_video_chapters', array('id'=>$id));
}

/**
* add new Chapter to Video Resource
* 
* @param mixed $data - Video Resource Instance
*/
function videoresource_add_chapter($data) {
    global $DB;
    return $DB->insert_record('resource_video_chapters', $data);
}

/**
* update Video Chapter from Video Resource
* 
* @param mixed $data - Video Chapter Instance
*/
function videoresource_edit_chapter($data) {
    global $DB;
    return $DB->update_record('resource_video_chapters', $data);
}

/**
* delete Video Chapter
* 
* @param mixed $data - Video Chapter Instance
*/
function videoresource_delete_chapter($data) {
    global $DB;
     // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in videoresource_edit_chapter() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$item = $DB->get_record('resource_video_chapters', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Video Chapter.');
        return false;
    }
    return $DB->delete_records('resource_video_chapters', array('id' => $data->id));
}

/**
* convert time in seconds to "hh:mm:ss"
* 
* @param mixed $time
*/
function videoresource_time_convert($time) {
    return sprintf('%02d:%02d:%02d', $time/3600, ($time % 3600)/60, ($time % 3600) % 60);
}