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
 * Survey enrolment plugin.
 *
 * @package    enrol_survey
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('edit_form.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT); // instanceid

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context =  context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/self:config', $context);

$PAGE->set_url('/enrol/survey/edit.php', array('courseid'=>$course->id, 'id'=>$instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if (!enrol_is_enabled('survey')) {
    redirect($return);
}

$plugin = enrol_get_plugin('survey');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'survey', 'id'=>$instanceid), '*', MUST_EXIST);
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // no instance yet, we have to add new instance
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));
    $instance = new stdClass();
    $instance->id       = null;
    $instance->courseid = $course->id;
}

// get groups from this course
$_groups = groups_get_all_groups($courseid, 0, 0, 'g.id, g.name');
$groups = array();
foreach ($_groups as $key=>$group) {$groups[$key] = $group->name;}

$mform = new enrol_self_edit_form(NULL, array($instance, $plugin, $context, $course, $groups));

// ---- Form process
if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->roleid         = $data->roleid;
        $instance->timemodified   = time();
        $instance->customtext1    = $data->customtext1;  //comment
        $instance->customint1     = $data->customint1;   //isdeleteanswers
        $instance->customchar1    = !empty($data->groupid) ? implode(',', array_keys($data->groupid)) : null; //groups
        $DB->update_record('enrol', $instance);

    } else {
        $fields = array(
            'status'          =>$data->status,
            'name'            =>$data->name,
            'roleid'          =>$data->roleid,
            'customtext1'     =>$data->customtext1);
        $plugin->add_instance($course, $fields);
    }

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_survey'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_survey'));
$mform->display();
echo $OUTPUT->footer();
