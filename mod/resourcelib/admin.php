<?php
	/**
	 *
	 * @author  Frederic GUILLOU
	 * @version 0.0.1
	 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License, mod/sharedresource is a work derived from Moodle mod/resoruce
	 * @package sharedresource
	 *
	 * This php script display the admin part of the classification
	 * configuration. You can add, delete or apply a restriction
	 * on a classification, or configure a specific classification
	 * by accessing another page
	 *-----------------------------------------------------------
	 */

    require_once("../../config.php");
    //require_once($CFG->dirroot.'/mod/resourcelib/lib.php');
    require_once('lib.php');
    require_once('locallib.php');
    //require_once($CFG->libdir.'/formslib.php');
	//require_once($CFG->libdir.'/ddllib.php');
    require_once($CFG->libdir.'/outputcomponents.php');
    
    $action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
    $id = optional_param('id', 0, PARAM_INT); //admin action for mooc-settings
    
    $confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
    
	/*$id 			= optional_param('id', 0, PARAM_TEXT);
	$classname 		= optional_param('classificationname', '', PARAM_TEXT);
	$mode 			= optional_param('mode', 0, PARAM_ALPHA);
	$target 		= optional_param('target', '', PARAM_ALPHANUM);
	$table 			= optional_param('table', '', PARAM_TEXT);
	$parent 		= optional_param('parent', 0, PARAM_TEXT);
	$label 			= optional_param('label', '', PARAM_TEXT);
	$ordering 		= optional_param('ordering', 0, PARAM_TEXT);
	$orderingmin 	= optional_param('orderingmin', 0, PARAM_INT);*/

/// Security

	$systemcontext = context_system::instance();
	require_login();
	require_capability('moodle/site:config', $systemcontext);

/// Build page

	$returnurl = $CFG->wwwroot.'/mod/resourcelib/admin.php';
    $PAGE->set_url($returnurl);
    $PAGE->set_context($systemcontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    //$PAGE->set_focuscontrol(build_navigation(array()));

    $PAGE->set_pagelayout('admin');    
    //DebugBreak();
    /*echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('settings', 'resourcelib'));*/

	/*$recordclassif = true;
	$erroradd = '';
	$errorrestrict = '';
	$classifarray = unserialize(get_config(NULL, 'classifarray'));*/

	/*if (!empty($mode)){
		include $CFG->dirroot.'/mod/resourcelib/classificationconfigure.controller.php';
	}*/	
    //DebugBreak();
    $action = (!empty($action) ? $action : 'index');
    switch($action) {
        case 'index':
            //  $link = new action_link($url, get_string('addtype', 'mod/resourcelib'));
            //echo $OUTPUT->link($link);
            //echo html_link::make($url, get_string('addtype'));
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('settings', 'resourcelib'));

            $url = new moodle_url($returnurl/*$CFG->wwwroot.'/mod/resourcelib/admin.php'*/, array('action' => 'types'));
            echo html_writer::tag('a', get_string('manage_types', 'resourcelib'), array('href' => $url->__toString()));
            
            /*$url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/admin.php', array('action' => 'addtype'));
            echo html_writer::tag('a', get_string('addtype', 'resourcelib'), array('href' => $url->__toString())); 
            $url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/admin.php', array('action' => 'edittype'));
            echo html_writer::tag('a', get_string('edittype', 'resourcelib'), array('href' => $url->__toString())); */
            echo $OUTPUT->footer();
            break;
        case 'types': 
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('manage_types', 'resourcelib'));

            $stredit   = get_string('edit');
            $strdelete = get_string('delete');
            $table = new html_table();
            $table->head = array(get_string('name'), get_string('icon'));
            //DebugBreak();
            $types = get_resourcetypes();
            foreach ($types as $type) {
                $buttons = array();
                $buttons[] = html_writer::link(new moodle_url($returnurl, array('action'=>'deletetype', 'id'=>$type->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
                $table->data[] = array(
                    $type->name, 
                    //$type->icon_path, 
                    html_writer::empty_tag('img', array('src'=>$type->icon_path, 'alt'=>$type->icon_path, 'class'=>'iconmedium')) . ' ' . $type->icon_path,
                    implode(' ', $buttons)
                );
            }
            //add type button
            $url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/admin.php', array('action' => 'addtype'));
            echo html_writer::tag('a', get_string('addtype', 'resourcelib'), array('href' => $url->__toString()));
            //table with types data
            echo html_writer::table($table);
            echo $OUTPUT->footer();
            break;
        case 'addtype':
            require_once($CFG->dirroot.'/mod/resourcelib/form_edittype.php'); //include form_edittype.php  
            
            $url = new moodle_url($returnurl, array('action' => 'addtype'));
            $editform = new mod_resourcelib_form_edittype($url->__toString());
            //DebugBreak();
            if ($editform->is_cancelled()) {
                $url = new moodle_url($CFG->wwwroot.'/course/index.php', array('categoryid' => 1));
                redirect($url);
            } else if ($data = $editform->get_data()) {
                if ($file = $editform->get_file_content('icon_path')) {
                    $realfilename = $editform->get_new_filename('icon_path');
                    $importfile = $CFG->dirroot . '/mod/resourcelib/pix/' . $realfilename;
                    if ($editform->save_file('icon_path', $importfile, true)) {
                        $data->icon_path = $CFG->wwwroot . '/mod/resourcelib/pix/' . $realfilename;
                    }
                }
                if (create_resourcetype($data)){  //call create Resource Type function
                    $url = new moodle_url($returnurl, array('action' => 'types'));
                    redirect($url);
                }
            }
            //show form page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('addtype', 'resourcelib'));
            $editform->display();
            echo $OUTPUT->footer();
            break;
        case 'deletetype': 
            if (isset($id) && confirm_sesskey()) { // Delete a selected resource type, after confirmation
                $type = $DB->get_record('resource_types', array('id'=>$id), '*', MUST_EXIST);
                
                if ($confirm != md5($id)) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading(get_string('deletetype', 'resourcelib'));
                    $optionsyes = array('action'=>'deletetype', 'id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
                    echo $OUTPUT->confirm(get_string('deletecheckfull', '', "$type->name"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    echo $OUTPUT->footer();
                } else if (data_submitted() /*&& !$data->deleted*/){
                    if (deletete_resourcetype($type)) {
                        //\core\session\manager::gc(); // Remove stale sessions.
                        $url = new moodle_url($returnurl, array('action' => 'types'));
                        redirect($url);
                    } else {
                        //\core\session\manager::gc(); // Remove stale sessions.
                        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $type->name));
                    }
                }
            }
            break;
    }
    
    
	//echo '<center><hr><br/><input type="button" value="'.get_string('backadminpage','sharedresource').'" onclick="window.location.href=\''.$CFG->wwwroot.'/admin/settings.php?section=modsettingsharedresource\'"/></center><br/>';
	//echo $OUTPUT->footer();

