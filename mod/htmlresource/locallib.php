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
* Internal library of functions for module htmlresource
*
* All the htmlresource specific functions, needed to implement the module
* logic, should go here. Never include this file from your lib.php!
*
* @package    mod_htmlresource
* @copyright  2014 Jurets
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/*
* Does something really useful with the passed things
*
* @param array $things
* @return object
*function htmlresource_do_something_useful(array $things) {
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
function htmlresource_show_deletebutton($url, $action, $id, $confirm = false) {
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
function htmlresource_get_items() {
    global $DB;
    return $DB->get_records_sql('SELECT * FROM {resource_html}');
}

/**
* get Video Resource items for selecting
* 
* @return array
*/
function htmlresource_get_items_select() {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    $sql = 'SELECT v.id, v.internal_title
            FROM {resource_html} v';
    $items = $DB->get_records_sql_menu($sql);
    if (!$items)
        $items = array();
    return $items;
}

/**
* add new Html Resource to database
* 
* @param mixed $data - Resource Instance
*/
function htmlresource_add_item($data) {
    global $DB;
    return $DB->insert_record('resource_html', $data);
}

/**
* update Html Resource in database
* 
* @param mixed $data - Resource Instance
*/
function htmlresource_edit_item($data) {
    global $DB;
    return $DB->update_record('resource_html', $data);
}

/**
* get Html Resource record from database
* 
* @param mixed $id - Resource ID
*/
function htmlresource_get_item($id) {
    global $DB;
    /*$sql = '
        SELECT r.*, (select count(*) from {resource_section_items} si where si.resource_section_id = s.id) AS c_count
        FROM {resource_html} r
        WHERE r.id = ?
    ';*/
    if ($item = $DB->get_record('resource_html', array('id'=>$id))) {
        $text = $item->html;
        $item->html = array();
        $item->html['text'] = $text;
        $item->html['format'] = 1;
    }
    return $item;
}

/**
* delete Html Resource from database
* 
* @param mixed $data - instance of Video Resource
* @return bool
*/
function htmlresource_delete_item($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in htmlresource_delete_item() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$item = $DB->get_record('resource_html', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Html Resource.');
        return false;
    }
    return $DB->delete_records('resource_html', array('id' => $data->id));
}

/**
* Returns count of all the active instances of a particular module in all courses
* 
* @param mixed $data - instance of Html Resource
*/
function htmlresource_count_in_courses($data) {
    global $DB;
    /*$sql = 'SELECT count(*)
            FROM mdl_course_modules cm, 
              mdl_course_sections cw, 
              mdl_modules md
            WHERE cm.course = 4 AND
            cm.section = cw.id AND
            md.name = "resourcelib" AND
            md.id = cm.module';*/
    $sql = 'SELECT count(*) FROM {htmlresource} r
            WHERE r.resource_html_id = ?';
    $count = $DB->get_field_sql($sql, array($data->id));
    return $count;
}