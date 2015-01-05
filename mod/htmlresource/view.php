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
 * Prints a particular instance of htmlresource
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_htmlresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace htmlresource with the name of your module and remove this line.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->dirroot.'/rating/lib.php');

//process input params
$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... htmlresource instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('htmlresource', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $htmlresource  = $DB->get_record('htmlresource', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $htmlresource  = $DB->get_record('htmlresource', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $htmlresource->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('htmlresource', $htmlresource->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$event = \mod_htmlresource\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
////////$event->add_record_snapshot($PAGE->cm->modname, $activityrecord);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/htmlresource/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($htmlresource->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

//include js-script for chapter markers
$PAGE->requires->js('/mod/htmlresource/js/chapter_marker_player.js', true);


/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('htmlresource-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($htmlresource->intro) {
    echo $OUTPUT->box(format_module_intro('htmlresource', $htmlresource, $cm->id), 'generalbox mod_introbox', 'htmlresourceintro');
}

/// Render Video Resource Data
$video = htmlresource_get_video($htmlresource->resource_videos_id);

//render media frame

//$mediarenderer = $PAGE->get_renderer('core', 'media');  //previous version
//$url = 'http://youtu.be/'.$video->video_id;
//echo $mediarenderer->embed_url(new moodle_url($url));

echo html_writer::div('', 'mediaplugin mediaplugin_youtube', array('id'=>'iframe-session-player'));
$video_id = $video->video_id;
$video_chapters = '';
foreach ($video->chapters as $chapter) {
    $video_chapters .= $chapter->timecode . ': "' . $chapter->title . '",';
}
echo <<<EOD
    <script type="text/javascript">
    //<![CDATA[
    ChapterMarkerPlayer.insert({
      container: 'iframe-session-player',
      videoId: '$video_id',
      width: 600,
      chapters:{ $video_chapters }
    });

    function chapter_marker(time) {
        player.seekTo(time);
    }
    //]]>
    </script>
EOD;

//render podcast and transcript block
echo html_writer::start_div('video_metadata', array('style'=>'text-align: center'));
$video_metadata = array();
if (!empty($video->podcast_url)) {
    $video_metadata[] = html_writer::link($video->podcast_url, get_string('podcast_url', 'htmlresource'), array('target'=>'_blank'));
}
if (!empty($video->transcript)) {
    $url = new moodle_url(VR_URL_MAIN, array('action'=>'transcript', 'id'=>$video->id));
    $video_metadata[] = html_writer::link($url, get_string('transcript', 'htmlresource'), array('target'=>'_blank'));
}
$video_metadata = implode(' | ', $video_metadata);
$video_metadata = '[ ' . $video_metadata . ' ]';
echo $video_metadata;
echo html_writer::end_div();

//render chapters
if (!empty($video->chapters)) {
    echo html_writer::div(get_string('in_this_video', 'htmlresource') . ':', 'video_chapter_header');
    foreach ($video->chapters as $chapter) {
        echo html_writer::start_div('video_chapter');
        $time = htmlresource_time_convert($chapter->timecode);
        echo html_writer::tag('a', $time . ' - ' . $chapter->title, array('onclick'=>'chapter_marker("'.$chapter->timecode.'")'));
        echo html_writer::end_div();
    }
}

//render rating element
$ratingoptions = new stdClass;
$ratingoptions->context = $context; //$modcontext;
$ratingoptions->component = 'mod_htmlresource';
$ratingoptions->ratingarea = 'resource'; //
$ratingoptions->items = array($htmlresource); //
$ratingoptions->aggregate = $htmlresource->assessed; //1;//the aggregation method
$ratingoptions->scaleid = $htmlresource->scale;//5;
$ratingoptions->userid = $USER->id;
$ratingoptions->returnurl = "$CFG->wwwroot/mod/htmlresource/view.php?id=$id";
$rm = new rating_manager();
$items = $rm->get_ratings($ratingoptions);
$item = $items[0];
if(isset($item->rating)) {
    $rate_html = html_writer::tag('div', $OUTPUT->render($item->rating), array('class'=>'forum-post-rating'));
    echo $rate_html;
}

// Finish the page.
echo $OUTPUT->footer();
