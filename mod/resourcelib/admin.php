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
 * Admin page for resourcelib (obsolete)
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_videoresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
//require_once($CFG->dirroot.'/mod/resourcelib/lib.php');
require_once('lib.php');
require_once('locallib.php');
//require_once($CFG->libdir.'/formslib.php');
//require_once($CFG->libdir.'/ddllib.php');
require_once($CFG->libdir.'/outputcomponents.php');

//$action = optional_param('action', 0, PARAM_TEXT); //admin action for mooc-settings
// $id = optional_param('id', 0, PARAM_INT); //admin action for mooc-settings

//$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash

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

$PAGE->set_pagelayout('admin');    
$PAGE->navbar->add(get_string('administration', 'resourcelib'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('administration', 'resourcelib'));

//$url = new moodle_url($returnurl, array('action' => 'types'));
//echo html_writer::tag('a', get_string('manage_types', 'resourcelib'), array('href' => $url->__toString()));
echo html_writer::start_tag('div');
$url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/types.php');
echo html_writer::tag('a', get_string('manage_types', 'resourcelib'), array('href' => $url->out()));
echo html_writer::end_div();

echo html_writer::start_tag('div');
$url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/items.php');
echo html_writer::tag('a', get_string('manage_items', 'resourcelib'), array('href' => $url->out()));
echo html_writer::end_div();

echo html_writer::start_tag('div');
$url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/sections.php');
echo html_writer::tag('a', get_string('manage_sections', 'resourcelib'), array('href' => $url->out()));
echo html_writer::end_div();

echo html_writer::start_tag('div');
$url = new moodle_url($CFG->wwwroot.'/mod/resourcelib/lists.php');
echo html_writer::tag('a', get_string('manage_lists', 'resourcelib'), array('href' => $url->out()));
echo html_writer::end_div();

echo $OUTPUT->footer();
