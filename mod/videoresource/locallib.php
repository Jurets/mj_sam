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
* show Resource items in HTML table
* 
* @param mixed $items - array of resource instances
*/
function show_resource_items($items, $returnurl, $buttons = null) {
    global $OUTPUT;

    if (!$items || empty($items)) {
        echo $OUTPUT->notification(get_string('no_resources', 'resourcelib'), 'redirectmessage');
    } else {
        if (!isset($buttons)) //default buttons
            $buttons = array('delete'=>'delete', 'edit'=>'edit');
        
        $table = new html_table();
        $table->head = array(
            get_string('videoid', 'videoresource'), 
            get_string('internal_name', 'videoresource'), 
            get_string('video_title', 'videoresource')
        );
        
        foreach ($items as $item) {
            $buttons_column = array();
            if (key_exists('delete', $buttons))
                $buttons_column[] = create_deletebutton($returnurl, $buttons['delete'], $item->id);
            if (key_exists('edit', $buttons))
                $buttons_column[] = create_editbutton($returnurl, $buttons['edit'], $item->id);
            $table->data[] = array(
                $item->video_id, 
                $item->internal_title, 
                //$item->internal_notes, 
                $item->title, 
                //$type->description, 
                implode(' ', $buttons_column) 
            );
        }
        echo html_writer::table($table);
    }
}


/**
* get all Resources
* 
*/
function videoresource_get_videos() {
    global $DB;
    return $DB->get_records_sql('SELECT * FROM {resource_videos}');
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
