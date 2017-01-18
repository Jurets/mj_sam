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
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');
require_once($CFG->dirroot.'/lib/gradelib.php');

/**
 * Add course module.
 *
 * The function does not check user capabilities.
 * The function creates course module, module instance, add the module to the correct section.
 * It also trigger common action that need to be done after adding/updating a module.
 *
 * @param object $moduleinfo the moudle data
 * @param object $course the course of the module
 * @param object $mform this is required by an existing hack to deal with files during MODULENAME_add_instance()
 * @return object the updated module info
 */
function import_module($moduleinfo, $course, $mform = null) {
    global $DB, $CFG;

    // Attempt to include module library before we make any changes to DB.

    $moduleinfo->course = $course->id;
    $moduleinfo = set_moduleinfo_defaults($moduleinfo);

    if (!empty($course->groupmodeforce) or !isset($moduleinfo->groupmode)) {
        $moduleinfo->groupmode = 0; // Do not set groupmode.
    }

    // First add course_module record because we need the context.
    $newcm = new stdClass();
    $newcm->course           = $course->id;
    $newcm->module           = $moduleinfo->module;
    $newcm->instance         = 0; // Not known yet, will be updated later (this is similar to restore code).
    $newcm->visible          = $moduleinfo->visible;
    $newcm->visibleold       = $moduleinfo->visible;
    if (isset($moduleinfo->cmidnumber)) {
        $newcm->idnumber         = $moduleinfo->cmidnumber;
    }
    $newcm->groupmode        = $moduleinfo->groupmode;
    $newcm->groupingid       = $moduleinfo->groupingid;
    $newcm->groupmembersonly = $moduleinfo->groupmembersonly;
    $completion = new completion_info($course);
    if ($completion->is_enabled()) {
        $newcm->completion                = $moduleinfo->completion;
        $newcm->completiongradeitemnumber = $moduleinfo->completiongradeitemnumber;
        $newcm->completionview            = $moduleinfo->completionview;
        $newcm->completionexpected        = $moduleinfo->completionexpected;
    }
    if (isset($moduleinfo->showdescription)) {
        $newcm->showdescription = $moduleinfo->showdescription;
    } else {
        $newcm->showdescription = 0;
    }

    // begin process
    if (!$moduleinfo->coursemodule = add_imported_module($newcm)) {
        print_error('cannotaddcoursemodule');
    }

    if (plugin_supports('mod', $moduleinfo->modulename, FEATURE_MOD_INTRO, true) &&
            isset($moduleinfo->introeditor)) {
        $introeditor = $moduleinfo->introeditor;
        unset($moduleinfo->introeditor);
        $moduleinfo->intro       = $introeditor['text'];
        $moduleinfo->introformat = $introeditor['format'];
    }
    try {
        if ($moduleinfo->modulename == 'assign') {
            $assignment = new assign(context_module::instance($moduleinfo->coursemodule), null, null);
            // false means don't call plugin settings by assign module
            // if true to call plugin settings
            $returnfromfunc = $assignment->add_instance($moduleinfo, false);
            // --- so we need do it manually ...
            // 1) onlinetext
            $config = new stdClass();
            $config->assignment = $returnfromfunc;
            $config->subtype = 'assignsubmission';
            $config->plugin = 'onlinetext';
            $config->name = 'enabled';
            $config->value = 1;
            $return = $DB->insert_record('assign_plugin_config', $config);
            // 2) enable file submissions
            $config->plugin = 'file';
            $return = $DB->insert_record('assign_plugin_config', $config);
            // 3) maximum number of  files to submission
            $config->plugin = 'file';
            $config->name = 'maxfilesubmissions';
            $config->value = 10;
            $return = $DB->insert_record('assign_plugin_config', $config);
            // 4) Grade Book settings
            //$assignment->update_gradebook(false, $moduleinfo->coursemodule);
            $assign = $assignment->get_instance();
            $params = array(
                'itemname' => $assign->name,
                'idnumber' => $assign->id, //$assign->cmidnumber,
                'gradetype'=> GRADE_TYPE_SCALE,
                'scaleid'  => -$assign->grade,
            );
            grade_update('mod/assign', $moduleinfo->course, 'mod', 'assign', $assign->id, 0, $grades, $params);

            $params = array(
                'itemtype'=>'mod',
                'itemmodule'=>'assign',
                'iteminstance'=>$assign->id,
                'courseid'=>$moduleinfo->course,
            );
            //if ($grade_items = grade_item::fetch_all(array('iteminstance' => $assign->id, /*'idnumber' => $assign->id*/))) {
            if ($grade_items = grade_item::fetch_all($params)) {
                if (count($grade_items) == 1){
                    $grade_item = reset($grade_items);
                    unset($grade_items); //release memory
                    // if gradepass is not setted in CSV - get grademax (2 for Acceptance)
                    $grade_item->gradepass = isset($moduleinfo->gradepass) ? $moduleinfo->gradepass : $grade_item->grademax;
                    $grade_item->update();
                }
            }

        } else {
            $addinstancefunction = $moduleinfo->modulename."_add_instance";
            $returnfromfunc = $addinstancefunction($moduleinfo, $mform);
        }
    } catch (moodle_exception $e) {
        $returnfromfunc = $e;
    }
    // Undo everything we can. This is not necessary for databases which
    // support transactions, but improves consistency for other databases.
    if (!$returnfromfunc or !is_number($returnfromfunc)) {
        $modcontext = context_module::instance($moduleinfo->coursemodule);
        context_helper::delete_instance(CONTEXT_MODULE, $moduleinfo->coursemodule);
        $DB->delete_records('course_modules', array('id'=>$moduleinfo->coursemodule));

        if ($e instanceof moodle_exception) {
            throw $e;
        } else if (!is_number($returnfromfunc)) {
            print_error('invalidfunction', '', course_get_url($course, $moduleinfo->section));
        } else {
            print_error('cannotaddnewmodule', '', course_get_url($course, $moduleinfo->section), $moduleinfo->modulename);
        }
    }
    $moduleinfo->instance = $returnfromfunc;
    $DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$moduleinfo->coursemodule));
    // Update embedded links and save files.
    $modcontext = context_module::instance($moduleinfo->coursemodule);
    // Course_modules and course_sections each contain a reference to each other.
    // So we have to update one of them twice.
    $sectionid = course_add_cm_to_section($course, $moduleinfo->coursemodule, $moduleinfo->section);

    // Trigger event based on the action we did.
    // Api create_from_cm expects modname and id property, and we don't want to modify $moduleinfo since we are returning it.
    $eventdata = clone $moduleinfo;
    $eventdata->modname = $eventdata->modulename;
    $eventdata->id = $eventdata->coursemodule;
    $event = \core\event\course_module_created::create_from_cm($eventdata, $modcontext);
    $event->trigger();

    $moduleinfo = edit_module_post_actions($moduleinfo, $course);

    return $moduleinfo;
}

// new
function add_imported_module($mod) {
    global $DB;

    $mod->added = time();
    unset($mod->id);
    $cmid = $DB->insert_record("course_modules", $mod);
    return $cmid;
}

