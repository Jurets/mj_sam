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
* Items Controller for ResourceLib Module
* 
* @author  Yuriy Hetmanskiy
* @version 0.0.1
* @package    mod_resourcelib
* @copyright  2014 Jurets
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*-----------------------------------------------------------
*/

/// Includes 
require_once("../../config.php");
require_once('lib.php');
require_once('locallib.php');
require_once($CFG->libdir.'/outputcomponents.php');

/// Input params
$id = optional_param('id', 0, PARAM_INT); //admin action for mooc-settings
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
//get action name
$action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
$action = (!empty($action) ? $action : 'index');
//get sort param (if present)
$sort = optional_param('sort', 'title', PARAM_TEXT);
$dir = optional_param('dir', 'ASC', PARAM_TEXT);

//actions list
$actionIndex = 'index';
$actionAdd = 'add';
$actionEdit = 'edit';
$actionDelete = 'delete';

/// Security
$systemcontext = context_system::instance();
require_login();
require_capability('moodle/site:config', $systemcontext);

/// Build page
$mainurl = $CFG->wwwroot.'/mod/resourcelib/admin.php';
$returnurl = $CFG->wwwroot.'/mod/resourcelib/items.php';
$PAGE->set_url($returnurl);
$PAGE->set_context($systemcontext);
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

//page layout
$PAGE->set_pagelayout('admin');     
//breadcrumbs
$PAGE->navbar->add(get_string('administration', 'resourcelib'), new moodle_url($CFG->wwwroot.'/admin/settings.php', array('section'=>'modsettingresourcelib'))); 
if ($action == $actionIndex) {
    $PAGE->navbar->add(get_string('manage_items', 'resourcelib'));
} else {
    $PAGE->navbar->add(get_string('manage_items', 'resourcelib'), new moodle_url($returnurl));
}

/// ------------- main process --------------
switch($action) {
    case $actionIndex:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage_items', 'resourcelib'));
        //add type button
        resourcelib_show_addbutton(new moodle_url($returnurl, array('action' => $actionAdd)), get_string('additem', 'resourcelib'));
        //show table with items data
        $items = resourcelib_get_resources($sort, $dir);
        resourcelib_show_resource_items($items, $returnurl, null, $sort, $dir);
        echo $OUTPUT->footer();
        break;
        
    case $actionAdd:
    case $actionEdit:
        require_once($CFG->dirroot.'/mod/resourcelib/form_edititem.php'); //include form_edittype.php  
        
        $head_str = ($action == $actionAdd) ? get_string('additem', 'resourcelib') : get_string('edititem', 'resourcelib');
        
        if ($action == $actionAdd) { //add new type
            $PAGE->navbar->add($head_str);
            $actionurl = new moodle_url($returnurl, array('action' => $actionAdd));
            $item = null;        //empty data
        } else if (isset($id)){     //edit existing type ($id parameter must be present in URL)
            $PAGE->navbar->add(get_string('edititem', 'resourcelib'));
            $actionurl = new moodle_url($returnurl, array('action' => $actionEdit, 'id'=>$id));
            $item = resourcelib_get_resource($id); //get data from DB
        }
        
        //get Resource Types List
        $types = $DB->get_records_menu('resource_types', null, '', 'id,name');
        //build form
        $editform = new mod_resourcelib_form_edititem($actionurl->out(false), array('item'=>$item, 'types'=>$types)); //create form instance
        //$editform->is_submitted()
        if ($editform->is_cancelled()) {  //in cancel form case - redirect to previous page
            $url = new moodle_url($returnurl, array('action' => $actionIndex));
            redirect($url);
        } else if ($data = $editform->get_data()) {
            if ($action == $actionAdd) {
                $inserted_id = resourcelib_add_resource($data);
                $success = isset($id);
            } else if (isset($id)){
                $success = resourcelib_edit_resource($data);
            }
            if ($success){  //call create Resource Type function
                $url = new moodle_url($returnurl, array('action' => $actionIndex));
                redirect($url);
            }
        }
        //show form page
        echo $OUTPUT->header();
        echo $OUTPUT->heading($head_str);
        $editform->display();
        echo $OUTPUT->footer();
        break;
        
    case $actionDelete: 
        //breadcrumbs
        $PAGE->navbar->add(get_string('deleteitem', 'resourcelib'));
        
        if (isset($id) && confirm_sesskey()) { // Delete a selected resource item, after confirmation
            $item = resourcelib_get_resource($id); //get data from DB

            if ($confirm != md5($id)) {
                echo $OUTPUT->header();
                echo $OUTPUT->heading(get_string('deleteitem', 'resourcelib'));
                //before delete do check existing of resources in any section
                if ($item->rs_count > 0) {
                    $str = get_string('deletednot', '', $item->title) . ' ' . get_string('resources_exists_in_section', 'resourcelib');
                    echo $OUTPUT->notification($str);
                } else {
                    $optionsyes = array('action'=>$actionDelete, 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$item->title'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                }
                echo $OUTPUT->footer();
            } else if (data_submitted() /*&& !$data->deleted*/){
                if (resourcelib_delete_resource($item)) {
                    $url = new moodle_url($returnurl, array('action' => $actionIndex));
                    redirect($url);
                } else {
                    echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $item->name));
                }
            }
        }
        break;
}
