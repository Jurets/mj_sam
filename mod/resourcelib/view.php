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
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace resourcelib with the name of your module and remove this line.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->dirroot.'/rating/lib.php');
require_once(dirname(__FILE__).'/classes/mooc_lib.php');  // New class!!!

// process input params
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

$baseurl = $CFG->wwwroot.'/mod/resourcelib';
$returnurl = new moodle_url($baseurl . '/view.php', array('id' => $cm->id));

// Print the page header.
$PAGE->set_url($returnurl);
$PAGE->set_title(format_string($resourcelib->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Attach jQuery 
//uncomment string below in case if non-bootstrap theme (without jQuery) will use
//$PAGE->requires->jquery();

// Include js-script for star rating
// !TODO: learn more for working with js-files
//$PAGE->requires->js('/mod/resourcelib/raty-master/jquery.raty.js', true);

// Include CSS file
$PAGE->requires->css('/mod/resourcelib/styles.css');

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('resourcelib-'.$somevar);
 */
// Output starts here.
echo $OUTPUT->header();

// --- button for bookmark
//echo resourcelib_button_bookmark($bookmark);

// Conditions to show the intro can change to look for own settings or whatever.
if ($resourcelib->intro) {
    echo $OUTPUT->box(format_module_intro('resourcelib', $resourcelib, $cm->id), 'generalbox mod_introbox', 'resourcelibintro');
}


/// Reinitialise global $OUTPUT for correct Rating renderer
$OUTPUT = new mooc_renderer($PAGE, RENDERER_TARGET_MAINTENANCE);

// ------------- Main process of resources
$contents = $DB->get_records('resourcelib_content', array('resourcelib_id'=>$resourcelib->id, 'type'=>'list'), 'sort_order ASC');
$isTabs = count($contents) > 1;

if ($isTabs) {
    echo '<div role="tabpanel"">';
        //output of tabs
        echo '<ul class="nav nav-tabs resource-tabs" role="tablist">';
            $index = 0;
            foreach($contents as $content) {
                $list = get_list($content->instance_id);
                echo '<li role="presentation"' . ($index++ == 0 ? 'class="active"' : '') . '><a href="#'.$index.'" aria-controls="'.$index.'" role="tab" data-toggle="tab">'.$list->display_name.'</a></li>';
            }
        echo '</ul>';
    echo '</div>';
}

if ($isTabs) {
    //output of tab content
    echo '<div class="tab-content" style="overflow: unset;">';
    $index = 0;
}

// output mail content
foreach($contents as $content)
{
    if ($content->type == 'list') 
    {
        //get List instance
        $list = get_list($content->instance_id);
        
        if ($isTabs) {
            echo '<div role="tabpanel" class="tab-pane ' . ($index++ == 0 ? 'active' : '') . '" id="'.$index.'">';
        }
        
        //list head
        if($list->s_count>1){
            echo html_writer::start_div('list_title');
            if (strlen($list->icon_path)>0) echo html_writer::empty_tag('img', array(
                'src'=>$list->icon_path, 
                'alt'=>$list->icon_path, 
                'class'=>'iconsmall', 
                'style'=>'width: 30px; height: 30px; float: left;'));
            echo html_writer::tag('h2', $list->display_name, array('style'=>'margin-top: 0;'));
            echo html_writer::end_div();
        }
        //
        echo html_writer::start_div('list_content');
        echo html_writer::div($list->heading, 'list_heading');
        //get Sections of this List
        if ($list->s_count > 0) {
            $sections = get_list_sections($list);
            foreach($sections as $section) {
                echo html_writer::start_tag('section', array('class'=>'course-section course_resource'));
                echo html_writer::start_div('section-header');
                if (strlen($section->icon_path)>0) echo html_writer::empty_tag('img', array(
                    'src'=>$section->icon_path, 
                    'alt'=>$section->icon_path, 
                    'class'=>'iconsmall', 
                    'style'=>'width: 30px; height: 30px; float: left;'));
                echo html_writer::tag('h2', $section->display_name, array('class'=>'section-label'));
                echo html_writer::tag('h3',$section->heading, array('class'=>'section-support-text'));
                echo html_writer::end_div();
                
                
                echo html_writer::start_div('section-content');
                //get Resources of this Section
                if ($section->r_count > 0) {
                    $resources = resourcelib_get_section_items($section);
                    foreach($resources as $resource)
                    {
                        /// --- Render one resource item                    
                        echo html_writer::start_div('resource_item');
                        //
                        echo html_writer::start_div('resource_body');
                        echo html_writer::start_div('resource_title');
                        echo html_writer::empty_tag('img', array(
                            'src'=>$resource->icon_path, 
                            'alt'=>$resource->icon_path, 
                            'class'=>'iconsmall', 
                            'style'=>'width: 30px; height: 30px;'));
                        echo html_writer::link($resource->url, $resource->title, array(
                            'target'=>'_blank',
                            'class'=>'resourcelink',
                            'data-objectid'=>$resource->id, //'data-resourcelibid'=>$cm->id,
                        ));
                        echo html_writer::end_div(); // end of resource_title
                        
                        // render Author and source
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
                        echo html_writer::end_div(); // end of Resource body ---
                        
                        //render rating element
                        $ratingoptions = new stdClass;
                        $ratingoptions->context = $context; //$modcontext;
                        $ratingoptions->component = 'mod_resourcelib';
                        $ratingoptions->ratingarea = 'resource'; //
                        $ratingoptions->items = array($resource); //
                        $ratingoptions->aggregate = $resourcelib->assessed; //1;//the aggregation method
                        $ratingoptions->scaleid = $resourcelib->scale;//5;
                        $ratingoptions->userid = $USER->id;
                        $ratingoptions->returnurl = $returnurl;
                        $rm = new rating_manager();
                        $items = $rm->get_ratings($ratingoptions);
                        $item = $items[0];

                        if (isset($item->rating)) {
                            //$rendered_rating = is_null($item->rating->rating) ? $OUTPUT->render($item->rating) : get_string('your_rate', 'resourcelib') . get_string('labelsep', 'langconfig') . ' ' . $item->rating->rating;
                            $rendered_rating = $OUTPUT->render($item->rating);
                            $rate_html = html_writer::tag('div', $rendered_rating, array('class'=>'forum-post-rating', 'style'=>'margin: 5px 10px 5px 0; float: left;'));
                            echo $rate_html;
                        }
                        
                        // bookmark link
                        $bookmark_added = ($bookmark = $DB->get_record('resbookmarks', array('user_id'=>$USER->id, 'url'=>$resource->url)));
                        $add_bookmark = optional_param('add_bookmark', '', PARAM_TEXT);
                        if (!$bookmark_added && $add_bookmark) {
                            $data = new stdClass();
                            $data->timecreated = time();
                            $data->user_id = $USER->id;
                            $data->url = $returnurl->out(false);
                            $data->title = $videoresource->name;
                            $bookmark_added = $DB->insert_record('resbookmarks', $data);
                        }
                        echo resourcelib_button_bookmark($resource->resource_id, $bookmark);
                        
                        // AddToAny
                        echo html_writer::start_div('addtoany', array('style'=>'margin: 5px 0;'));
                        
                        echo html_writer::start_div('a2a_kit a2a_default_style');
                        	echo html_writer::link('https://www.addtoany.com/share_save?linkurl='.$resource->url.'&linkname='.$resource->title, 'Share', array('class'=>'a2a_dd'));
                        	echo html_writer::span('', 'a2a_divider');
                        	echo html_writer::tag('a','',array('class'=>'a2a_button_facebook'));
                        	echo html_writer::tag('a','',array('class'=>'a2a_button_twitter'));
                        	echo html_writer::tag('a','',array('class'=>'a2a_button_google_plus'));
            
						echo html_writer::end_div();
                        echo html_writer::script('var a2a_config = a2a_config || {};
                                a2a_config.linkname = "'.$resource->title.'";
                                a2a_config.linkurl = "'.$resource->url.'";');
                        echo html_writer::tag('script', null, array('src'=>'//static.addtoany.com/menu/page.js'));
                        echo html_writer::end_div(); //

                        echo html_writer::end_div(); // end of Resource Item ---
                    }
                    echo html_writer::end_div();
                }
                echo html_writer::end_tag('section');
            }
        }
        echo html_writer::end_div();
        if ($isTabs) {
            echo '</div>';
        }
    }
}
if ($isTabs) {
    echo '</div>';
}

//output of script, this jQuery click process command need for event storing
$sesskey = sesskey();
$cm_id = $cm->id;
//$baseurl = $CFG->wwwroot;
echo <<<EOD
    <script type="text/javascript">
    //<![CDATA[

    var loader = "<img src='$CFG->wwwroot/pix/i/loading_small.gif' alt='...process'>";
    
    function ajaxSend(action, objectid) {
        $.ajax({
          type: "GET",
          url: "$baseurl/ajax.php",
          data: {"action": action, "id": "$cm_id", "objectid": objectid, "bookmarkid": bookmarkid, "sesskey": "$sesskey"},
          dataType: "json",
          beforeSend: function() {
                $("#bookmark_container_"+objectid).html(loader);
                return true;
          },
          success: function (response) {
                if (!response.success) {
                    Y.log(response.error, 'debug', 'moodle-mod_resourcelib-logview');
                } else if (response.html) {
                    $("#bookmark_container_"+objectid).html(response.html);
                }
          },
        });
        return true;
    }
    
    function clickResource(element){
        //id = $(this).attr("data-resourcelibid");
        objectid = $(element).attr("data-objectid");
        $.ajax({
          type: "GET",
          url: "$baseurl/ajax.php",
          data: {"action": "logview", "id": "$cm_id", "objectid": objectid, "sesskey": "$sesskey"},
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
        $(document).on("click", ".resourcelink", function(){
            clickResource(this);
        });
        
        $(document).on('contextmenu', '.resourcelink', function(e) {
            e.stopPropagation();
            //clickResource(this);
            //$(this).click();
            //url = $(this).attr("href");
            //$("<a>").attr("href", url).attr("target", "_blank").click();
            //location.target = "_blank";
            //location.href = url;
            //window.open(url,'_blank');
            return false;
        });

        $(document).on('mousedown', '.resourcelink', function(e) {
            if (e.which == 2) {
                e.stopPropagation();
                clickResource(this);
                //open(this.getAttribute("data-anotherhref"), null)
                return false;
            }
            return true;
        });
        
        
        $(document).on("click", ".bookmarklink", function(){
            elem = $(this);
            action = elem.attr("data-action");
            objectid = elem.attr("data-objectid");
            bookmarkid = elem.attr("data-bookmarkid");
            return ajaxSend(action, objectid);
        })
    });
    
    //]]>
    </script>
EOD;

// ----------- show another activity (forum, questionnaire)
if ($activity = $DB->get_record_select('resourcelib_content', 'resourcelib_id = :resourcelib_id AND type = :type', array('resourcelib_id'=>$resourcelib->id, 'type'=>'forum')))
    switch ($activity->type) {
    case 'forum':
        echo html_writer::start_tag('div', array('class'=>'panel panel-default course-element course-element-discussion'));
        require_once('../forum/lib.php');
        //
        $forum_id = $activity->instance_id;
        $forum = $DB->get_record("forum", array("id" => $forum_id));
        $cm = get_coursemodule_from_instance("forum", $forum->id, $course->id);

        echo html_writer::start_tag('div',array('class'=>'panel-heading'));
        echo html_writer::start_tag('h3',array('class'=>'panel-title'));
        echo ($forum->name);
        echo html_writer::end_tag('h3');
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div',array('class'=>'panel-body'));

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
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        break;
}

// --- show questionnaire
if ($activity = $DB->get_record_select('resourcelib_content', 'resourcelib_id = :resourcelib_id AND type = :type', array('resourcelib_id'=>$resourcelib->id, 'type'=>'questionnaire'))) {
    require_once('questionnaire.php'); // include file for questionnaire showing
    echo '<br>';
    showQuestionnaire($activity->instance_id, $course);
}

// Finish the page.
echo $OUTPUT->footer();

if ($file_content = file_get_contents($CFG->dirroot.'/mod/resourcelib/rating.js')) {
    echo   //wrap contant of js-file into <script> tag
    '<script type="text/javascript">
        //<![CDATA[
        ' . $file_content . '
        
        M.resource_rating.init(Y);
        //]]>
    </script>';
}
