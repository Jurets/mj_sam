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
* get all Resource Types
* 
*/
function get_types() {
    global $DB;
    return $DB->get_records('resource_types');
}

/**
* get instance of Resource Type
* 
*/
function get_type($id) {
    global $DB;
    return $DB->get_record_sql('
        SELECT t.*, (select count(*) from {resource_items} r where r.type_id = t.id) AS resource_count
        FROM {resource_types} t
        WHERE t.id = ?
    ', array($id));
}

/**
* create resource type
*  
* @param mixed $data
*/
function add_type($data) {
    global $DB;
    $id = $DB->insert_record('resource_types', $data);
    return $id;
}

function edit_type($data) {
    global $DB;
    return $DB->update_record('resource_types', $data);
}

/**
* delete Resource Type
* 
* @param mixed $data - instance of Resource Type
* @return bool
*/
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

/**
* get all Resources
* 
*/
function resourcelib_get_resources($sort = '', $dir = '') {
    global $DB;
    $sql = 'SELECT ri.*, rt.name AS type_name, rt.icon_path
            FROM {resource_items} ri LEFT JOIN {resource_types} rt ON rt.id = ri.type_id';
    if (!empty($sort)) {
        $sql .= ' ORDER BY ri.' . $sort . (!empty($dir) ? ' ' . $dir : '');
    }
    return $DB->get_records_sql($sql);
}

/**
* get Resource record from database
* 
* @param mixed $id - Resource ID
*/
function get_resource($id) {
    global $DB;
    return $DB->get_record_sql('
        SELECT r.*, t.name AS type_name, t.icon_path,
            (select count(*) from {resource_section_items} si where si.resource_item_id = r.id) AS rs_count
        FROM {resource_items} r LEFT JOIN {resource_types} t ON t.id = r.type_id
        WHERE r.id = ?
    ', array($id));
}

/**
* add new Resource to database
* 
* @param mixed $data - Resource Instance
*/
function add_resource($data) {
    global $DB;
    return $DB->insert_record('resource_items', $data);
}

/**
* update Resource in database
* 
* @param mixed $data - Resource Instance
*/
function edit_resource($data) {
    global $DB;
    return $DB->update_record('resource_items', $data);
}

/**
* delete Resource from database
* 
* @param mixed $data - instance of Resource
* @return bool
*/
function delete_resource($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in delete_resource() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_items', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Resource.');
        return false;
    }
    return $DB->delete_records('resource_items', array('id' => $data->id));
}

/**
* get all List Instances
* 
*/
function resourcelib_get_lists($sort = '', $dir = '') {
    global $DB;
    $sql = 'SELECT l.*, (select count(*) from {resource_list_sections} ls where ls.resource_list_id = l.id) AS s_count
        FROM {resource_lists} l';
    if (!empty($sort)) {
        $sql .= ' ORDER BY l.' . $sort . (!empty($dir) ? ' ' . $dir : '');
    }
    return $DB->get_records_sql($sql);
}

/**
* get one Section with other data
* 
* @param mixed $id
* @return array
*/
function get_list($id) {
    global $DB;
    return $DB->get_record_sql('
        SELECT l.*, (select count(*) from {resource_list_sections} ls where ls.resource_list_id = l.id) AS s_count
        FROM {resource_lists} l
        WHERE l.id = ?
    ', array($id));
    //return $DB->get_records('resource_sections');
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
        throw new coding_exception('Invalid $data parameter in delete_list() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_lists', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Resource List.');
        return false;
    }
    return $DB->delete_records('resource_lists', array('id' => $data->id));
}

/**
* get Sections for List
* 
* @param mixed $data - list instance
* @return array
*/
function get_list_sections($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_list_sections() detected');
    }
    $sql = 'SELECT ls.id, s.id AS section_id, s.name, s.display_name, s.heading, s.icon_path, ls.sort_order,
                (select count(*) from {resource_section_items} si where si.resource_section_id = s.id) AS r_count
            FROM {resource_list_sections} ls 
                LEFT JOIN {resource_sections} s ON s.id = ls.resource_section_id
            WHERE ls.resource_list_id = ?
            ORDER BY ls.sort_order';
    $items = $DB->get_records_sql($sql, array($data->id));
    if (!$items)
        $items = array();
    return $items;
}


/**
* get resource items wich is not in section
* 
* @param mixed $data - section instance
* @return array
*/
function get__lists() {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    $sql = 'SELECT l.id, l.name
            FROM {resource_lists} l
    ';
    $items = $DB->get_records_sql_menu($sql);
    if (!$items)
        $items = array();
    return $items;
}

//--------------------------------------------------

/**
* get all Sections
* 
*/
function resourcelib_get_sections($sort = '', $dir = '') {
    global $DB;
    $sql = 'SELECT s.*,
                (select count(*) from {resource_section_items} si where si.resource_section_id = s.id) AS r_count
            FROM {resource_sections} s
    ';
    if (!empty($sort)) {
        $sql .= ' ORDER BY s.' . $sort . (!empty($dir) ? ' ' . $dir : '');
    }
    $items = $DB->get_records_sql($sql);
    if (!$items)
        $items = array();
    return $items;
}

/**
* get one Section with other data
* 
* @param mixed $id
* @return array
*/
function get_section($id) {
    global $DB;
    return $DB->get_record_sql('
        SELECT s.*, s.id AS section_id, (select count(*) from {resource_section_items} si where si.resource_section_id = s.id) AS r_count
        FROM {resource_sections} s
        WHERE s.id = ?
    ', array($id));
    //return $DB->get_records('resource_sections');
}

function add_section($data) {
    global $DB;
    return $DB->insert_record('resource_sections', $data);
}

function edit_section($data) {
    global $DB;
    return $DB->update_record('resource_sections', $data);
}

function delete_section($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in delete_section() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_sections', array('id' => $data->id))) {
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
function resourcelib_get_section_items($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_section_items() detected');
    }
    $sql = 'SELECT si.id, r.url, r.title, r.description, r.author, r.source, t.name AS type_name, t.icon_path, si.sort_order
            FROM {resource_section_items} si 
                LEFT JOIN {resource_items} r ON r.id = si.resource_item_id
                LEFT JOIN {resource_types} t ON t.id = r.type_id
            WHERE si.resource_section_id = ?
            ORDER BY si.sort_order ASC';
    $items = $DB->get_records_sql($sql, array($data->section_id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* get Resource instance from Section
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
* get Section instance from List
* 
* @param mixed $id - id of resource instance in section
*/
function get_section_fromlist($id) {
    global $DB;
    $sql = 'SELECT s.*, ls.resource_list_id
            FROM {resource_list_sections} ls 
                LEFT JOIN {resource_sections} s ON s.id = ls.resource_section_id
            WHERE ls.id = ?';
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
            WHERE r.id NOT IN 
              (SELECT si.resource_item_id FROM {resource_section_items} si WHERE si.resource_section_id = ?)';
    $items = $DB->get_records_sql_menu($sql, array($data->id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* get Sections wich is not in List
* 
* @param mixed $data - section instance
* @return array
*/
function get_notlist_sections($data) {
     global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_notlist_sections() detected');
    }
    $sql = 'SELECT s.id, s.name
            FROM {resource_sections} s 
            WHERE s.id NOT IN 
              (SELECT ls.resource_section_id FROM {resource_list_sections} ls WHERE ls.resource_list_id = ?)';
    $items = $DB->get_records_sql_menu($sql, array($data->id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* get Lists wich is not in Cource
* 
* @param mixed $data - resourcelib instance
* @return array
*/
function get_notcource_lists($data) {
     global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_notlist_sections() detected');
    }
    $sql = 'SELECT l.id, l.name
            FROM {resource_lists} l 
            WHERE l.id NOT IN 
              (SELECT rlc.instance_id FROM {resourcelib_content} rlc WHERE rlc.type = ? AND rlc.resourcelib_id = ?)';
    $items = $DB->get_records_sql_menu($sql, array('list', $data->id));
    if (!$items)
        $items = array();
    return $items;
}

/**
* get Lists wich is in Cource
* 
* @param mixed $data - resourcelib instance
* @return array
*/
function get_cource_lists($data) {
     global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in get_notlist_sections() detected');
    }
    $sql = 'SELECT l.id
            FROM {resource_lists} l 
            WHERE l.id IN 
              (SELECT rlc.instance_id FROM {resourcelib_content} rlc WHERE rlc.type = ? AND rlc.resourcelib_id = ?)';
    $_items = $DB->get_records_sql_menu($sql, array('list', $data->id));
    $items = array();
    foreach ($_items as $key=>$value) 
        $items[] = $key;
    return $items;
}

/**
* add Resource to Section
* 
* @param mixed $data - instance of Resource Item in Section
*/
function add_resource_to_section($data) {
    global $DB;
    $result = $DB->insert_record('resource_section_items', $data);
    return $result;
}

/**
* add Section to List
* 
* @param mixed $data - instance of Section Item in List
*/
function add_section_to_list($data) {
    global $DB;
    $result = $DB->insert_record('resource_list_sections', $data);
    return $result;
}

/**
* delete Resource from Section
* 
* @param mixed $id - ID of Resource in Section
* @return bool
*/
function delete_resource_from_section($id) {
    global $DB;
    return $DB->delete_records('resource_section_items', array('id' => $id));
}

/**
* delete Section from List
* 
* @param mixed $id - ID of Section in List
* @return bool
*/
function delete_section_from_list($id) {
    global $DB;
    return $DB->delete_records('resource_list_sections', array('id' => $id));
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
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/editstring'), 'alt'=>$stredit, 'class'=>'iconsmall')), 
        array('title'=>$stredit)
    );
}

/**
* show Resource Lists in HTML table
* 
* @param mixed $sections
* @param mixed $returnurl
* @param mixed $buttons
*/
function show_list_sections($items, $returnurl, $buttons = null) {
    global $CFG, $OUTPUT;
    
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    
    if (!$items || empty($items)) {
        echo $OUTPUT->notification(get_string('no_sections', 'resourcelib'), 'redirectmessage');
    } else {
        if (!isset($buttons)) //default buttons
            $buttons = array('delete'=>'delete', 'edit'=>'edit');
        
        $table = new html_table();
        $table->head = array(
            get_string('name'), 
            get_string('description'), 
            get_string('type', 'resourcelib'),
            get_string('resource_count', 'resourcelib'),
        );
        $table->size[4] = '80px';
        
        $first_item = reset($items);
        $last_item = end($items);
        
        foreach ($items as $item) {
            $buttons_column = array();
            // Move up.
            if ($item->sort_order != $first_item->sort_order) {
                $buttons_column[] = get_action_icon($returnurl . '?action=moveup&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
            } else {
                $buttons_column[] = get_spacer();
            }
            // Move down.
            if ($item->sort_order != $last_item->sort_order) {
                $buttons_column[] = get_action_icon($returnurl . '?action=movedown&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
            } else {
                $buttons_column[] = get_spacer();
            }
            // Delete
            if (key_exists('delete', $buttons))
                $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
            if (key_exists('edit', $buttons))
                $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
            $table->data[] = array(
                //$item->name, 
                html_writer::link(new moodle_url($CFG->wwwroot.'/mod/resourcelib/sections.php', array('action'=>'view', 'id'=>$item->section_id)), $item->name),
                $item->display_name, 
                //$type->icon_path, 
                html_writer::empty_tag('img', array(
                    'src'=>$item->icon_path, 
                    'alt'=>$item->icon_path, 
                    'title'=>get_string('icon'),
                    'class'=>'iconsmall', 
                    'style'=>'width: 30px; height: 30px;')),
                ($item->r_count ? $item->r_count : ''), 
                implode(' ', $buttons_column) 
            );
        }
        echo html_writer::table($table);
    }
}


/**
* Move Section down in Sections List
* 
* @param mixed $section
* @return bool
*/
function resourcelib_section_move_down($section) {
    return move_section($section, 'down');
}

/**
* Move Section up in Sections List
* 
* @param mixed $section
* @return bool
*/
function resourcelib_section_move_up($section) {
    return move_section($section, 'up');
}

/**
* Move Section
* 
* @param mixed $section
* @param mixed $direction - direction of moving ("down" or "up")
* @return bool
*/
function move_section($section, $direction = 'down') {
    global $DB;
    $sql = 'SELECT * FROM {resource_list_sections}
            WHERE resource_list_id = ? 
                  AND sort_order ' . ($direction == 'down' ? '>' : '<') . ' ?
            ORDER BY sort_order ' . ($direction == 'down' ? 'ASC' : 'DESC') . '
            LIMIT 1';
    $other_section = $DB->get_record_sql($sql, array($section->resource_list_id, $section->sort_order));
    if (!$other_section) { //if other section not exists - return false
        return false;
    }
    $result = $DB->set_field('resource_list_sections', 'sort_order', $other_section->sort_order, array('id' => $section->id))
           && $DB->set_field('resource_list_sections', 'sort_order', $section->sort_order,  array('id' => $other_section->id));
    return $result;
}

/**
* Move resource down in resources List
* 
* @param mixed $resource
* @return bool
*/
function resourcelib_resource_move_down($resource) {
    return move_resource($resource, 'down');
}

/**
* Move resource up in resources List
* 
* @param mixed $resource
* @return bool
*/
function resourcelib_resource_move_up($resource) {
    return move_resource($resource, 'up');
}

/**
* Move Section
* 
* @param mixed $resource
* @param mixed $direction - direction of moving ("down" or "up")
* @return bool
*/
function move_resource($resource, $direction = 'down') {
    global $DB;
    $sql = 'SELECT * FROM {resource_section_items}
            WHERE resource_section_id = ? 
                  AND sort_order ' . ($direction == 'down' ? '>' : '<') . ' ?
            ORDER BY sort_order ' . ($direction == 'down' ? 'ASC' : 'DESC') . '
            LIMIT 1';
    $other_resource = $DB->get_record_sql($sql, array($resource->resource_section_id, $resource->sort_order));
    if (!$other_resource) { //if other section not exists - return false
        return false;
    }
    $result = $DB->set_field('resource_section_items', 'sort_order', $other_resource->sort_order, array('id' => $resource->id))
           && $DB->set_field('resource_section_items', 'sort_order', $resource->sort_order,  array('id' => $other_resource->id));
    return $result;
}

//
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
* take sorting column, if need
* 
* @param mixed $returnurl
* @param mixed $title
* @param mixed $sort
* @param mixed $dir
* @return string
*/
function resourcelib_get_column_title($returnurl, $columnname, $columntitle, $sort = '', $dir = '') {
    global $OUTPUT;
    
    if (!empty($sort)) {
        if ($sort != $columnname) {
            $columnicon = '';
            $columndir = "ASC";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
            $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";
            $dir = !empty($dir) ? $dir : 'ASC';
            $columndir = $dir == "ASC" ? "DESC":"ASC";
        }
        $column_title = html_writer::link(new moodle_url($returnurl, array('sort'=>$columnname, 'dir'=>$columndir)), $columntitle . $columnicon);
    } else {
        $column_title = $columntitle;
    }
    return $column_title;
}
    
/**
* show Resource items in HTML table
* 
* @param mixed $items - array of resource instances
*/
function resourcelib_show_resource_items($items, $returnurl, $buttons = null, $sort = '', $dir = '') {
    global $OUTPUT;
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    
    if (!$items || empty($items)) {
        echo $OUTPUT->notification(get_string('no_resources', 'resourcelib'), 'redirectmessage');
        //echo '<div class="alert alert-warning">' . get_string('no_resources', 'resourcelib') . '</div>';
    } else {
        if (!isset($buttons)) //default buttons
            $buttons = array('delete'=>'delete', 'edit'=>'edit');
        
        // take sorting column, if need
        $title_column = resourcelib_get_column_title($returnurl, 'title', get_string('name'), $sort, $dir);

        // build table header
        $table = new html_table();
        $table->head = array( //sorting in first column!
            $title_column,
            //get_string('description'), 
            get_string('type', 'resourcelib')
        );
        $table->size[2] = '120px';
        
        $first_item = reset($items);
        $last_item = end($items);
        
        foreach ($items as $item) {
            $buttons_column = array();
            // Move up.
            if ($item->sort_order != $first_item->sort_order) {
                $buttons_column[] = get_action_icon($returnurl . '?action=moveup&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
            } else {
                $buttons_column[] = get_spacer();
            }
            // Move down.
            if ($item->sort_order != $last_item->sort_order) {
                $buttons_column[] = get_action_icon($returnurl . '?action=movedown&amp;id=' . $item->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
            } else {
                $buttons_column[] = get_spacer();
            }
            if (key_exists('delete', $buttons))
                $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
            if (key_exists('edit', $buttons))
                $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
            $table->data[] = array(
                $item->title, 
                //$item->description, 
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
function resourcelib_show_addbutton($url, $label, $attributes = array('class' => 'mdl-right')) {
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
function resourcelib_show_editbutton($url, $label, $attributes = array('class' => 'mdl-right')) {
    global $OUTPUT;
    echo html_writer::start_tag('div', $attributes);
    echo html_writer::tag('a', $OUTPUT->pix_icon('t/editstring', '') . ' ' . $label, array('href' => $url->out(false)));
    echo html_writer::end_tag('div');
}