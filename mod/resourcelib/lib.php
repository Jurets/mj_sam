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
 * Library of interface functions and constants for module resourcelib
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the resourcelib specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_resourcelib
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Example constant:
 * define('resourcelib_ULTIMATE_ANSWER', 42);
 */

/**
 * Moodle core API
 */

require_once($CFG->dirroot.'/rating/lib.php'); 
 
/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function resourcelib_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE; //
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the resourcelib into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $resourcelib An object from the form in mod_form.php
 * @param mod_resourcelib_mod_form $mform
 * @return int The id of the newly inserted resourcelib record
 */
function resourcelib_add_instance(stdClass $resourcelib, mod_resourcelib_mod_form $mform = null) {
    global $DB;
    
    try {
        $transaction = $DB->start_delegated_transaction();
        //firstly, insert record
        $resourcelib->timecreated = time();
        //rating params
        $resourcelib->assessed = (int)RATING_AGGREGATE_AVERAGE;
        $resourcelib->assesstimestart = 0;
        $resourcelib->assesstimefinish = 0;
        $resourcelib->scale = (int)RATING_AGGREGATE_SUM;
        //insert record
        $resourcelib->id = $DB->insert_record('resourcelib', $resourcelib, true);
        //process form data
        $form_content = $mform->get_data();
        if (isset($form_content->list_id)) {
            $items = prepare_items($form_content->list_id, $resourcelib->id);
            $DB->insert_records('resourcelib_content', $items);
        }
         // Assuming the both inserts work, we get to the following line.
        $transaction->allow_commit();
        $success = true;
    } catch(Exception $e) {
        $transaction->rollback($e);
        $success = false;
    }
    return $resourcelib->id;
}

/**
 * Updates an instance of the resourcelib in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $resourcelib An object from the form in mod_form.php
 * @param mod_resourcelib_mod_form $mform
 * @return boolean Success/Fail
 */
function resourcelib_update_instance(stdClass $resourcelib, mod_resourcelib_mod_form $mform = null) {
    global $DB;
    //set module fields
    $resourcelib->timemodified = time();
    $resourcelib->id = $resourcelib->instance;

    try {
        $transaction = $DB->start_delegated_transaction();
        //process form data
        $form_content = $mform->get_data();
        if (isset($form_content->list_id)) {
            //firstly, delete the current elements
            $DB->delete_records('resourcelib_content', array('resourcelib_id'=>$resourcelib->id));
            $items = prepare_items($form_content->list_id, $resourcelib->id);
            $DB->insert_records('resourcelib_content', $items);
        }
        // You may have to add extra stuff in here.
        $DB->update_record('resourcelib', $resourcelib);
         // Assuming the both inserts work, we get to the following line.
        $transaction->allow_commit();
        $success = true;
    } catch(Exception $e) {
        $transaction->rollback($e);
        $success = false;
    }
    return $success;
}

//prepare data from form for inserting to DB
function prepare_items($list_ids, $rlib_id) {
    $items = array();
    foreach($list_ids as $list_id) {
        $item = new stdClass();
        $item->resourcelib_id = $rlib_id;
        $item->type = 'list';
        $item->instance_id = $list_id;
        //$DB->insert_record('resourcelib_content', $content);
        $items[] = $item;
    }
    return $items;
}

/**
 * Removes an instance of the resourcelib from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function resourcelib_delete_instance($id) {
    global $DB;
    if (! $resourcelib = $DB->get_record('resourcelib', array('id' => $id))) {
        return false;
    }
    try {
        $transaction = $DB->start_delegated_transaction();
        //firstly, delete the elements of module
        $DB->delete_records('resourcelib_content', array('resourcelib_id'=>$resourcelib->id));
        //delete module instance from course
        $DB->delete_records('resourcelib', array('id' => $resourcelib->id));
         // Assuming the both inserts work, we get to the following line.
        $transaction->allow_commit();
        $success = true;
    } catch(Exception $e) {
        $transaction->rollback($e);
        $success = false;
    }
    return $success;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function resourcelib_user_outline($course, $user, $mod, $resourcelib) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $resourcelib the module instance record
 * @return void, is supposed to echp directly
 */
function resourcelib_user_complete($course, $user, $mod, $resourcelib) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in resourcelib activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function resourcelib_print_recent_activity($course, $viewfullnames, $timestart) {
    return false; // True if anything was printed, otherwise false.
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link resourcelib_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function resourcelib_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see resourcelib_get_recent_mod_activity()}
 *
 * @return void
 */
function resourcelib_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function resourcelib_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function resourcelib_get_extra_capabilities() {
    return array();
}

/**
 * Gradebook API                                                              //
 */

/**
 * Is a given scale used by the instance of resourcelib?
 *
 * This function returns if a scale is being used by one resourcelib
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $resourcelibid ID of an instance of this module
 * @return bool true if the scale is used by the given resourcelib instance
 */
function resourcelib_scale_used($resourcelibid, $scaleid) {
    global $DB;

    /* @example */
    if ($scaleid and $DB->record_exists('resourcelib', array('id' => $resourcelibid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of resourcelib.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any resourcelib instance
 */
function resourcelib_scale_used_anywhere($scaleid) {
    global $DB;

    /* @example */
    if ($scaleid and $DB->record_exists('resourcelib', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the give resourcelib instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $resourcelib instance object with extra cmidnumber and modname property
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function resourcelib_grade_item_update(stdClass $resourcelib, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    /* @example */
    $item = array();
    $item['itemname'] = clean_param($resourcelib->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $resourcelib->grade;
    $item['grademin']  = 0;

    grade_update('mod/resourcelib', $resourcelib->course, 'mod', 'resourcelib', $resourcelib->id, 0, null, $item);
}

/**
 * Update resourcelib grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $resourcelib instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function resourcelib_update_grades(stdClass $resourcelib, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $grades = array(); // Populate array of grade objects indexed by userid. @example .

    grade_update('mod/resourcelib', $resourcelib->course, 'mod', 'resourcelib', $resourcelib->id, 0, $grades);
}

/**
 * File API                                                                   //
 */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function resourcelib_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for resourcelib file areas
 *
 * @package mod_resourcelib
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function resourcelib_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the resourcelib file areas
 *
 * @package mod_resourcelib
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the resourcelib's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function resourcelib_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/**
 * Navigation API                                                             //
 */

/**
 * Extends the global navigation tree by adding resourcelib nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the resourcelib module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function resourcelib_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the resourcelib settings
 *
 * This function is called when the context for the page is a resourcelib module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $resourcelibnode {@link navigation_node}
 */
function resourcelib_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $resourcelibnode=null) {
}




function resourcelib_rating_validate($params) {
    if (!array_key_exists('itemid', $params) || !array_key_exists('context', $params) || !array_key_exists('rateduserid', $params)) {
        throw new rating_exception('missingparameter');
    }
    return true;
}

function resourcelib_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_resourcelib' || $ratingarea != 'resource') {
        // We don't know about this component/ratingarea so just return null to get the
        // default restrictive permissions.
        return null;
    }
    return array(
        'view'    => has_capability('mod/resourcelib:viewrating', $context),
        'viewany' => has_capability('mod/resourcelib:viewanyrating', $context),
        'viewall' => has_capability('mod/resourcelib:viewallratings', $context),
        'rate'    => has_capability('mod/resourcelib:rate', $context)
    );
}