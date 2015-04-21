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
 * Prints a particular instance of videoresource
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_videoresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace videoresource with the name of your module and remove this line.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->dirroot.'/rating/lib.php');
// include custom classes for rendering
require_once(dirname(__FILE__).'/classes/mooc_lib.php');  // New class!!!

//process input params
$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... videoresource instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('videoresource', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $videoresource  = $DB->get_record('videoresource', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $videoresource  = $DB->get_record('videoresource', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $videoresource->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('videoresource', $videoresource->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Generate course module view event
$event = \mod_videoresource\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
////////$event->add_record_snapshot($PAGE->cm->modname, $activityrecord);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/videoresource/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($videoresource->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

//include js-script for chapter markers
$PAGE->requires->js('/mod/videoresource/js/chapter_marker_player.js', true);
// Include CSS file
$PAGE->requires->css('/mod/videoresource/styles.css');

//output of script, this jQuery click process command need for event storing
$sesskey = sesskey();
$cm_id = $cm->id;
//$video_id = $video->video_id;
$baseurl = $CFG->wwwroot;
/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('videoresource-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($videoresource->intro) {
    echo $OUTPUT->box(format_module_intro('videoresource', $videoresource, $cm->id), 'generalbox mod_introbox', 'videoresourceintro');
}

/// --- Render secondary description field (activity)
if (!empty($videoresource->activity)) {
    echo html_writer::div($videoresource->activity);
    echo '<br>';
}

/// Reinitialise global $OUTPUT for correct Rating renderer
$OUTPUT = new mooc_renderer($PAGE, RENDERER_TARGET_MAINTENANCE);

// ------------- Main process of video resource
// get content records 
$contents = $DB->get_records('videoresource_content', array('resource_id'=>$videoresource->id, 'type'=>'videoresource'), 'sort_order ASC');

// cycle for content records 
foreach($contents as $video_content)
{
    /// -------------- Render Video Resource Data
    $video = videoresource_get_video($video_content->instance_id);
    $video_id = $video->id;
    $yt_video_id = $video->video_id;

    /// Render description text above
    if (!empty($video_content->textabove)) {
        echo html_writer::div($video_content->textabove);
        echo '<br>';
    }
    
    /// --- Render media frame
    echo html_writer::div('', 'mediaplugin mediaplugin_youtube', array(
        'id'=>'iframe-session-player-'.$video_id,
        'data-objectid'=>$video->id,
    ));

    $video_chapters = '';
    $data_objects = '';
    foreach ($video->chapters as $chapter) {
        $video_chapters .= $chapter->timecode . ': "' . $chapter->title . '",';
        $data_objects   .= $chapter->timecode . ': "' . $chapter->id . '",';
    }
    
    ///  --- below previous version - throug moodle mediarenderer
    //$mediarenderer = $PAGE->get_renderer('core', 'media');  //previous version
    //$url = 'http://youtu.be/'.$video->video_id;
    //echo $mediarenderer->embed_url(new moodle_url($url));

    /// --- Render podcast and transcript block
    echo html_writer::start_div('video_metadata', array('style'=>'text-align: center'));
    $video_metadata = array();
    if (!empty($video->podcast_url)) {
        $video_metadata[] = html_writer::link($video->podcast_url, get_string('podcast_url', 'videoresource'), array(
            'target'=>'_blank',
            'class'=>'podcastlink',
            'data-objectid'=>$video->id,
        ));
    }
    if (!empty($video->transcript)) {
        $url = new moodle_url(VR_URL_MAIN, array('action'=>'transcript', 'id'=>$video->id));
        $video_metadata[] = html_writer::link($url, get_string('transcript', 'videoresource'), array(
            'target'=>'_blank',
            'class'=>'transcriptlink',
            'data-objectid'=>$video->id,
        ));
    }
    $video_metadata = implode(' | ', $video_metadata);
    $video_metadata = '[ ' . $video_metadata . ' ]';
    echo $video_metadata;
    echo html_writer::end_div();

    /// --- Render Follow Up Text
    if (!empty($video->description)) {
        echo html_writer::div($video->description);
        echo '<br>';
    }

    /// Render description text below
    if (!empty($video_content->textbelow)) {
        echo html_writer::div($video_content->textbelow);
        echo '<br>';
    }
    
    //render rating element
    $ratingoptions = new stdClass;
    $ratingoptions->context = $context; //$modcontext;
    $ratingoptions->component = 'mod_videoresource';
    $ratingoptions->ratingarea = 'resource'; //
    $ratingoptions->items = array($video_content); //
    $ratingoptions->aggregate = $videoresource->assessed; //1;//the aggregation method
    $ratingoptions->scaleid = $videoresource->scale;//5;
    $ratingoptions->userid = $USER->id;
    $ratingoptions->returnurl = "$CFG->wwwroot/mod/videoresource/view.php?id=$id";
    $rm = new rating_manager();
    $items = $rm->get_ratings($ratingoptions);
    $item = $items[0];
    if(isset($item->rating)) {
        $rate_html = html_writer::tag('div', $OUTPUT->render($item->rating), array('class'=>'forum-post-rating'));
        echo $rate_html;
    }

    /// --- Render chapters
    echo html_writer::start_div('video_chapters', array('id'=>'video-chapters-'.$video_id));
    if (!empty($video->chapters)) {
        echo html_writer::div(get_string('in_this_video', 'videoresource') . ':', 'video_chapter_header');
    }
    echo html_writer::end_div();

    echo <<<EOD
<script type="text/javascript">
    //<![CDATA[
    
    $(document).ready(function(){
        ChapterMarkerPlayer.insert({
          container: 'iframe-session-player-$video_id',
          containerChapters: 'video-chapters-$video_id',
          videoId: '$yt_video_id',
          width: 600,
          chapters:{ $video_chapters },
          dataobjects:{ $data_objects },
          callbackState: ajaxSend,
        });
    });

    //]]>
</script>
EOD;
}
// -------------


echo <<<EOD
<script type="text/javascript">
    //<![CDATA[
    
    function ajaxSend(action, objectid) {
        $.ajax({
          type: "GET",
          url: "$baseurl/mod/videoresource/ajax.php",
          data: {"action": action, "id": "$cm_id", "objectid": objectid, "sesskey": "$sesskey"},
          dataType: "json",
          success: function(response){
            if (!response.success)
                Y.log(response.error, 'debug', 'moodle-mod_resourcelib-logview');
                //alert("Error during AJAX request: " + response.error);
          }
        });
        return true;
    }
    
    $(document).ready(function(){
        $(".podcastlink, .transcriptlink").click(function(){
            elem = $(this);
            objectid = elem.attr("data-objectid");
            if (elem.hasClass("podcastlink"))
                action = "logpodcast";
            else if (elem.hasClass("transcriptlink"))
                action = "logtranscript";
            ajaxSend(action, objectid);
            return true;
        })
    });
    
    //]]>
</script>
EOD;

// ----------- show another activity (forum, questionnaire)
if ($activity = $DB->get_record_select('videoresource_content', 'resource_id = :resource_id AND type = :type', array('resource_id'=>$videoresource->id, 'type'=>'forum')))
    switch ($activity->type) {
    case 'forum':
        require_once('../forum/lib.php');
        //$forum_id = 5;  //////////// заглушка!
        $forum_id = $activity->instance_id;
        $forum = $DB->get_record("forum", array("id" => $forum_id));
        $cm = get_coursemodule_from_instance("forum", $forum->id, $course->id);

        echo $OUTPUT->heading(format_string($forum->name), 2);

        if ($cm && !empty($forum->intro) && $forum->type != 'single' && $forum->type != 'teacher') {
            echo $OUTPUT->box(format_module_intro('forum', $forum, $cm->id), 'generalbox', 'intro');
        }
        if ($cm) {
            groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forum/view.php?id=' . $cm->id);
        }

        /*if ($forum->type == 'single') {
            $discussion = NULL;
            $discussions = $DB->get_records('forum_discussions', array('forum'=>$forum->id), 'timemodified ASC');
            if (!empty($discussions)) {
                $discussion = array_pop($discussions);
            }
            if ($discussion) {
                if ($mode) {
                    set_user_preference("forum_displaymode", $mode);
                }
                $displaymode = get_user_preferences("forum_displaymode", $CFG->forum_displaymode);
                forum_print_mode_form($forum->id, $displaymode, $forum->type);
            }
        }*/


        switch ($forum->type) {
            case 'single':
                if (!empty($discussions) && count($discussions) > 1) {
                    echo $OUTPUT->notification(get_string('warnformorepost', 'forum'));
                }
                if (! $post = forum_get_post_full($discussion->firstpost)) {
                    print_error('cannotfindfirstpost', 'forum');
                }
                /*if ($mode) {
                    set_user_preference("forum_displaymode", $mode);
                }*/

                $canreply    = forum_user_can_post($forum, $discussion, $USER, $cm, $course, $context);
                $canrate     = has_capability('mod/forum:rate', $context);
                $displaymode = get_user_preferences("forum_displaymode", $CFG->forum_displaymode);

                echo '&nbsp;'; // this should fix the floating in FF
                forum_print_discussion($course, $cm, $forum, $discussion, $post, $displaymode, $canreply, $canrate);
                break;

            default:
                echo '<br />';
                /*if (!empty($showall)) {
                    forum_print_latest_discussions($course, $forum, 0, 'header', '', -1, -1, -1, 0, $cm);
                } else*/ {
                    forum_print_latest_discussions($course, $forum, -1, 'header', '', -1, -1, 0 /*$page*/, $CFG->forum_manydiscussions, $cm);
                }
                break;
        }
        break;
}

// --- show questionnaire
if ($activity = $DB->get_record_select('videoresource_content', 'resource_id = :resource_id AND type = :type', array('resource_id'=>$videoresource->id, 'type'=>'questionnaire'))) {
    require_once('questionnaire.php'); // include file for questionnaire showing
    echo '<br>';
    showQuestionnaire($activity->instance_id, $course);
}

// Finish the page.
echo $OUTPUT->footer();
