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

/**
* get Lists wich is in Cource
* 
* @param mixed $data - resourcelib instance
* @return array
*/
function videoresource_get_courcemodule_contents($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in videoresource_get_courcemodule_contents() detected');
    }
    $sql = 'SELECT rc.id, r.id as content_id, r.internal_title as name, rc.instance_id, rc.sort_order
            FROM mdl_videoresource_content rc 
              LEFT JOIN mdl_resource_videos r ON rc.instance_id = r.id
            WHERE rc.type = ? AND rc.resource_id = ?
            ORDER BY rc.sort_order ASC';
    return $DB->get_records_sql($sql, array('videoresource', $data->id));
}

/**
* get Lists wich is not in Cource
* 
* @param mixed $data - resourcelib instance
* @return array
*/
function videoresource_get_notcource_lists($resource_id, $with_id = null) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    /*if (!property_exists($data, 'id')) {
        throw new coding_exception('Invalid $data parameter in get_notlist_sections() detected');
    }*/
    $sql = 'SELECT v.id, v.internal_title as name
            FROM {resource_videos} v 
            WHERE v.id NOT IN 
              (SELECT rc.instance_id FROM {videoresource_content} rc WHERE rc.type = ? AND rc.resource_id = ?)';
    $params = array('videoresource', $resource_id);
    if (isset($with_id)) {
        $sql .= ' OR v.id = ?';
        $params[] = $with_id;
    }
    $items = $DB->get_records_sql_menu($sql, $params);
    if (!$items)
        $items = array();
    return $items;
}

function get_action_icon($url, $icon, $alt, $tooltip) {
    global $OUTPUT;
    return '<a title="' . $tooltip . '" href="'. $url . '">' .
            '<img src="' . $OUTPUT->pix_url('t/' . $icon) . '" class="iconsmall" alt="' . $alt . '" /></a> ';
}

function get_spacer() {
    global $OUTPUT;
    return '<img src="' . $OUTPUT->pix_url('spacer') . '" class="iconsmall" alt="" /> ';
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
function videoresource_confirm_deletebutton($url, $action, $itemid, $confirm = false) {
    global $OUTPUT;
    $strdelete = get_string('delete');
    $url = new moodle_url($url, array('action'=>$action, 'itemid'=>$itemid, 'sesskey'=>sesskey()));
    $text = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'title'=>$strdelete, 'class'=>'iconsmall'));
    $link = new action_link($url, $text); //create action link
    $action = new component_action('click', 'M.util.show_confirm_dialog', array('message' => $confirm)); //attach confirm dialog
    $link->add_action($action);
    return $OUTPUT->render($link);
}

/**
* Move Item down in Items List of Course module content
* 
* @param mixed $section
* @return bool
*/
function videoresource_item_move_down($item) {
    return move_item($item, 'down');
}

/**
* Move Item up in Items List of Course module content
* 
* @param mixed $section
* @return bool
*/
function videoresource_item_move_up($item) {
    return move_item($item, 'up');
}

/**
* Move Item
* 
* @param mixed $item
* @param mixed $direction - direction of moving ("down" or "up")
* @return bool
*/
function move_item($item, $direction = 'down') {
    global $DB;
    $sql = 'SELECT * FROM {videoresource_content}
            WHERE resource_id = ? 
                  AND sort_order ' . ($direction == 'down' ? '>' : '<') . ' ?
            ORDER BY sort_order ' . ($direction == 'down' ? 'ASC' : 'DESC') . '
            LIMIT 1';
    $other_item = $DB->get_record_sql($sql, array($item->resource_id, $item->sort_order));
    if (!$other_item) { //if other section not exists - return false
        return false;
    }
    $result = $DB->set_field('videoresource_content', 'sort_order', $other_item->sort_order, array('id' => $item->id))
           && $DB->set_field('videoresource_content', 'sort_order', $item->sort_order,  array('id' => $other_item->id));
    return $result;
}

/**
* Add bookmark
* 
* @param string $url
* @param string $title
* @return bool
*/
function videoresource_bookmark($url = '', $title = 'bookmark', $id = null) {
    global $DB, $USER;
    //if ($bookmark = $DB->get_record('resbookmarks', array('user_id'=>$USER->id, 'url'=>$url))) {
    if (isset($id)) {
        $bookmark = new stdClass();
        $bookmark->id = $id;
        $bookmark->active = 1;
        //$success = $DB->update_record('resbookmarks', $bookmark);
        $success = $DB->update_record_raw('resbookmarks', $bookmark);
    } else {
        $bookmark = new stdClass();
        $bookmark->timecreated = time();
        $bookmark->user_id = $USER->id;
        $bookmark->url = $url; //$returnurl->out(false);
        $bookmark->title = $title; //$videoresource->name;
        $bookmark->active = 1;
        $success = $DB->insert_record('resbookmarks', $bookmark, false);
    }
    return $bookmark;
}

/**
* Delete bookmark
* 
* @param int $id
* @return bool
*/
function videoresource_unbookmark($id) {
    global $DB;
    //if ($bookmark = $DB->get_record('resbookmarks', array('id'=>$id))) {
    if ($id) {
        $bookmark = new stdClass();
        $bookmark->id = $id;
        $bookmark->active = 0;
        if ($success = $DB->update_record_raw('resbookmarks', $bookmark))
            return $bookmark;
    }
    return false;
}

/**
* button for bookmark
* 
* @param bool $url
* @return bool
*/
function videoresource_button_bookmark($bookmark = null) {
    global $CFG;
    $b_exists = isset($bookmark) && is_object($bookmark);
    $b_active = $b_exists && $bookmark->active;
    $baseurl = $CFG->wwwroot.'/mod/videoresource';
    $response = html_writer::start_div('', array('id'=>'bookmark_container')) 
              . html_writer::start_tag('a', array(
                    'id'=>'bookmarklink', 
                    'data-objectid'=>($b_exists ? $bookmark->id : ''), 
                    'data-action'=>($b_active ? 'unbookmark' : 'bookmark'),
                    'style'=>'cursor: pointer;'
              ))
              . html_writer::empty_tag('img', array('src'=>$baseurl . '/pix/'.(!$b_active ? 'bookmark_3.png' : 'bookmark_2.png'), 'alt'=>'!', 'class'=>'iconsmall'))
              . html_writer::tag('span', get_string((!$b_active ? 'bookmark' : 'unbookmark'), 'videoresource'))
              . html_writer::end_tag('a')
              . html_writer::end_div();
    return $response;
}