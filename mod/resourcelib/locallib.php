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
* Internal library of functions for module resourcelib
*
* All the resourcelib specific functions, needed to implement the module
* logic, should go here. Never include this file from your lib.php!
*
* @package    mod_resourcelib
* @copyright  2011 Your Name
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/*
* Does something really useful with the passed things
*
* @param array $things
* @return object
*function resourcelib_do_something_useful(array $things) {
*    return new stdClass();
*}
*/

/**
* put your comment there...
* 
*/
function get_resourcetypes() {
    global $DB;
    return $DB->get_records('resource_types');
}

/**
* create resource type
*  
* @param mixed $data
*/
function add_type($data) {
    global $DB;
    $id = $DB->insert_record('resource_types', $data);
    //$DB->insert_records('resource_types', array($data));
    return $id;
}

function edit_type($data) {
    global $DB;
    return $DB->update_record('resource_types', $data);
}

function delete_type($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in deletete_resourcetype() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_types', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Resource Type.');
        return false;
    }
    return $DB->delete_records('resource_types', array('id' => $data->id));
}

function get_resources() {
    global $DB;
    //return $DB->get_records('resource_items');
    return $DB->get_records_sql('SELECT ri.*, rt.name AS type_name, rt.icon_path
                          FROM {resource_items} ri LEFT JOIN {resource_types} rt ON rt.id = ri.type_id');
}

function add_resource($data) {
    global $DB;
    return $DB->insert_record('resource_items', $data);
}

function edit_resource($data) {
    global $DB;
    return $DB->update_record('resource_items', $data);
}


function get_lists() {
    global $DB;
    return $DB->get_records('resource_lists');
}

function add_list($data) {
    global $DB;
    return $DB->insert_record('resource_lists', $data);
}

function edit_list($data) {
    global $DB;
    return $DB->update_record('resource_lists', $data);
}

/**
* delete Resource List
* 
* @param mixed $data - instance of List
* @return bool
*/
function delete_list($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in deletete_resourcelist() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_lists', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Resource List.');
        return false;
    }
    return $DB->delete_records('resource_lists', array('id' => $data->id));
}


function get_sections() {
    global $DB;
    return $DB->get_records('resource_sections');
}

function add_section($data) {
    global $DB;
    return $DB->insert_record('resource_sections', $data);
}

function edit_section($data) {
    global $DB;
    return $DB->update_record('resource_sections', $data);
}

function deletete_resourcesection($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in deletete_resourcesection() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_lists', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Resource Section.');
        return false;
    }
    return $DB->delete_records('resource_sections', array('id' => $data->id));
}

/**
* get resource items for section
* 
* @param mixed $data - section instance
* @return array
*/
function get_section_items($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_section_items() detected');
    }
    $sql = 'SELECT si.id, r.title, r.description, t.name AS type_name, t.icon_path
            FROM {resource_section_items} si 
                LEFT JOIN {resource_items} r ON r.id = si.resource_item_id
                LEFT JOIN {resource_types} t ON t.id = r.type_id
            WHERE si.resource_section_id = ?
            ORDER BY si.sort_order';
    $items = $DB->get_records_sql($sql, array($data->id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* get resource instance from section
* 
* @param mixed $id - id of resource instance in section
*/
function get_resource_fromsection($id) {
    global $DB;
    $sql = 'SELECT r.*, si.resource_section_id
            FROM {resource_section_items} si 
                LEFT JOIN {resource_items} r ON r.id = si.resource_item_id
            WHERE si.id = ?';
    return $DB->get_record_sql($sql, array($id));
}

/**
* get resource items wich is not in section
* 
* @param mixed $data - section instance
* @return array
*/
function get_notsection_items($data) {
     global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_notsection_items() detected');
    }
    $sql = 'SELECT r.id, r.title
            FROM {resource_items} r 
              LEFT JOIN {resource_types} t ON t.id = r.type_id
            WHERE r.id NOT IN 
              (SELECT si.resource_item_id FROM {resource_section_items} si WHERE si.resource_section_id = ?)';
    $items = $DB->get_records_sql_menu($sql, array($data->id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* add Resource to Section
* 
* @param mixed $data - instance of Resource
*/
function add_resource_to_section($data) {
    global $DB;
    $result = $DB->insert_record('resource_section_items', $data);
    return $result;
}

/**
* put your comment there...
* 
* @param mixed $id - ID of Resource in Section
* @return bool
*/
function delete_resource_from_section($id) {
    global $DB;
    return $DB->delete_records('resource_section_items', array('id' => $id));
}

/**
* create a delete button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @return string
*/
function create_deletebutton($url, $action, $id) {
    global $OUTPUT;
    
    $strdelete = get_string('delete');
    return html_writer::link(
        new moodle_url($url, array('action'=>$action, 'id'=>$id, 'sesskey'=>sesskey())), 
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), 
        array('title'=>$strdelete)
    );
}

/**
* create a edit button for data table
* 
* @param mixed $url
* @param mixed $action
* @param mixed $id
* @return string
*/
function create_editbutton($url, $action, $id) {
    global $OUTPUT;
    
    $stredit   = get_string('edit');
    return html_writer::link(
        new moodle_url($url, array('action'=>'edit', 'id'=>$id)), 
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), 
        array('title'=>$stredit)
    );
}

/**
* show resource items in HTML table
* 
* @param mixed $items - array of resource instances
*/
function show_resource_items($items, $returnurl, $buttons = null) {
    global $OUTPUT;
    //$strview   = get_string('view');

    if (!$items || empty($items)) {
        echo $OUTPUT->box(get_string('no_resources', 'resourcelib'), 'generalbox', 'notice');
    } else {
        if (!isset($buttons)) //default buttons
            $buttons = array('delete'=>'delete', 'edit'=>'edit');
        
        $table = new html_table();
        $table->head = array(get_string('name'), get_string('description'), get_string('type', 'resourcelib'));
        
        foreach ($items as $item) {
            $buttons_column = array();
            if (key_exists('delete', $buttons))
                $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
            if (key_exists('edit', $buttons))
                $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
            $table->data[] = array(
                $item->title, 
                $item->description, 
                //$type->icon_path, 
                html_writer::empty_tag('img', array(
                    'src'=>$item->icon_path, 
                    'alt'=>$item->icon_path, 
                    'title'=>$item->type_name,
                    'class'=>'iconsmall', 
                    'style'=>'width: 30px; height: 30px;')),
                implode(' ', $buttons_column) 
            );
        }
        echo html_writer::table($table);
    }
}

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