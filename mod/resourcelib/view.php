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
 * Prints a particular instance of resourcelib
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_resourcelib
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace resourcelib with the name of your module and remove this line.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

require_once($CFG->dirroot.'/rating/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... resourcelib instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('resourcelib', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $resourcelib  = $DB->get_record('resourcelib', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $resourcelib  = $DB->get_record('resourcelib', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $resourcelib->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('resourcelib', $resourcelib->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$event = \mod_resourcelib\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
////////$event->add_record_snapshot($PAGE->cm->modname, $activityrecord);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/resourcelib/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($resourcelib->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('resourcelib-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($resourcelib->intro) {
    echo $OUTPUT->box(format_module_intro('resourcelib', $resourcelib, $cm->id), 'generalbox mod_introbox', 'resourcelibintro');
}

// Replace the following lines with you own code.
//echo $OUTPUT->heading('Yay! It works!');

require_once(dirname(__FILE__).'/locallib.php');

$contents = $DB->get_records('resourcelib_content', array('resourcelib_id'=>$resourcelib->id));
foreach($contents as $content) {
    //echo $content->type . ' - '. $content->instance_id . '<br>';
    if ($content->type == 'list') {
        //get List instance
        $list = get_list($content->instance_id);
        echo html_writer::tag('h2', $list->display_name);
        echo html_writer::start_div('collection');
        echo html_writer::div($list->heading, 'collection_heading');
        //get Sections of this List
        if ($list->s_count > 0) {
            $sections = get_list_sections($list);
            foreach($sections as $section) {
                echo html_writer::tag('h3', $section->display_name);
                echo html_writer::div($section->heading, 'list_heading');
                //get Resources of this Section
                if ($section->r_count > 0) {
                    $resources = get_section_items($section);
                    
                    foreach($resources as $resource) {
                        echo html_writer::start_div('resource_item_title');
                        echo html_writer::empty_tag('img', array(
                            'src'=>$resource->icon_path, 
                            'alt'=>$resource->icon_path, 
                            'class'=>'iconsmall', 
                            'style'=>'width: 30px; height: 30px;'));
                        echo html_writer::link($resource->url, $resource->title, array('target'=>'__blank'));
                        echo html_writer::end_div();

                        if (!empty($resource->author)) {
                            echo html_writer::start_div('resource_metadata');
                            echo html_writer::tag('strong', 'Author');
                            echo ': ' . $resource->author;
                            echo html_writer::end_div();
                        }
                        if (!empty($resource->source)) {
                            echo html_writer::start_div('resource_metadata');
                            echo html_writer::tag('strong', 'Source');
                            echo ': ' . $resource->source;
                            echo html_writer::end_div();
                        }
                        echo html_writer::div($resource->description, 'resource_description');
                        
                        //render rating element
                        $ratingoptions = new stdClass;
                        $ratingoptions->context = $context; //$modcontext;
                        $ratingoptions->component = 'mod_resourcelib';
                        $ratingoptions->ratingarea = 'resource'; //
                        $ratingoptions->items = array($resource); //
                        $ratingoptions->aggregate = $resourcelib->assessed; //1;//the aggregation method
                        $ratingoptions->scaleid = $resourcelib->scale;//5;
                        $ratingoptions->userid = $USER->id;
                        $ratingoptions->returnurl = "$CFG->wwwroot/mod/resourcelib/view.php?id=$id";
                        $rm = new rating_manager();
                        $items = $rm->get_ratings($ratingoptions);
                        $item = $items[0];
                        if(isset($item->rating)) {
                            $rate_html = html_writer::tag('div', $OUTPUT->render($item->rating), array('class'=>'forum-post-rating'));
                            echo $rate_html;
                        }
                    }
                }
            }
        }
        echo html_writer::end_div();
    }
}

// Finish the page.
echo $OUTPUT->footer();
