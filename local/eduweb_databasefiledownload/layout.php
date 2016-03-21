<?php
/**
 * Inject proper javascript to the view page 
 * to add a tab and two menu items in settings
 * if fields of type file or picture are present.
 *
 * @package    local
 * Author      michael.egli@phz.ch
 *   original coding
 * Author      pp@patrickpollet.net
 *    simplified code ; removed inclusion of mod/data/view.php to fetch
 */

require_once(dirname(__FILE__) . '/../../config.php');


$id = required_param('id', PARAM_INT); // this is the instance id , not the course module id 
$cm = get_coursemodule_from_id('', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$PAGE->set_url('/local/eduweb_databasefiledownload/layout.php', array('id'=>$id));
require_login($course, false, $cm); // needed to setup proper $COURSE

// die, Plugin is disabled
if (! $eduweb_databasefiledownload = get_config('local_eduweb_databasefiledownload', 'enabled')) {
    die;
}

// die, we are not on required modul page
if ($cm->modname == 'data') {} else {
    die;
}


// check if module has files for downloading
$fields = $DB->get_records('data_fields', array('dataid'=>$cm->instance), 'id');
$filescount=0;
foreach ($fields as $field) {
    if ($field->type == 'file' || $field->type == 'picture') { //picture type added by PP
             $filescount++;
    }
}

// die, if no files for downloading are available
if ($filescount == 0) {
    die;
} else {}


// prepare injection of js code to layout
$jsedit = "";


if (has_capability('moodle/course:manageactivities', $context)) {
// extend admin navigation with two links
    $streduweb_download = get_string('eduweb_databasefiledownload', 'local_eduweb_databasefiledownload');
    $streduweb_all_uploads = get_string('all_uploads', 'local_eduweb_databasefiledownload');
    $streduweb_all_uploads2 = get_string('all_uploads2', 'local_eduweb_databasefiledownload');
    $actionurl = new moodle_url('/local/eduweb_databasefiledownload/index.php',$_GET);
    $actionurl2 = $actionurl."&nosort=1";

    $newnode = "<li class=\"type_unknown collapsed contains_branch\"><p class=\"tree_item branch\"><span tabindex=\"0\">$streduweb_download</span></p>";
    $newnode .= "<ul id=\"yui_3_4_1_1_1326125892104_50\">";
    $newnode .= "<li class=\"type_setting collapsed item_with_icon\"><p class=\"tree_item leaf activesetting\"><span tabindex=\"0\"><img alt=\"moodle\" class=\"smallicon navicon\" title=\"moodle\" src=\"".$CFG->wwwroot."/theme/image.php?theme=standard&amp;image=i%2Fnavigationitem&amp;rev=180\"><a href=\"$actionurl\">$streduweb_all_uploads</a></span></p></li>";
    $newnode .= "<li class=\"type_setting collapsed item_with_icon\"><p class=\"tree_item leaf activesetting\"><span tabindex=\"0\"><img alt=\"moodle\" class=\"smallicon navicon\" title=\"moodle\" src=\"".$CFG->wwwroot."/theme/image.php?theme=standard&amp;image=i%2Fnavigationitem&amp;rev=180\"><a href=\"$actionurl2\">$streduweb_all_uploads2</a></span></p></li>";
    $newnode .= "</ul></li>";

    $jsedit = "
    var stop = false;
    var settingsnav = Y.one('#settingsnav');
    if (settingsnav) {
        var settings = settingsnav.one('.block_tree').all('ul');
        settings.each(function (setting) {
            var lists = setting.all('li');
            lists.each(function (list) {
                if (!stop && list.getContent().indexOf('subscribers.php?id=".$cm->instance."') ) {
                    setting.append('".$newnode."');
                    stop = true;
                    return;
                }
            });
            if(stop){
                return;
            }
        });
    }
";
}


if (has_capability('mod/data:viewentry', $context)) {
// extend modul navigation with one link
$actionurl = new moodle_url('/local/eduweb_databasefiledownload/index.php',$_GET);
$actionurl2 = $actionurl."&nosort=1";
$streduweb_all_uploads_student = get_string('all_uploads_student', 'local_eduweb_databasefiledownload');
$streduweb_download = get_string('eduweb_databasefiledownload', 'local_eduweb_databasefiledownload');
$newnode = "<li>";
$newnode .= "<a title=\"Felder\" href=\"$actionurl2\"><span>$streduweb_download</span></a>";
$newnode .= "</li>";

$jsedit .= "
    var stop = false;
    var settingsnav = Y.one('.region-content');
    if (settingsnav) {
        var settings = settingsnav.one('.tabtree').all('ul');
        settings.each(function (setting) {
            var lists = setting.all('li');
            lists.each(function (list) {

                if (!stop && list.getContent().indexOf('mod/data/export.php?d=".$cm->instance."') ) {
                    setting.append('".$newnode."');
                    stop = true;
                    return;
                }
            });
            if(stop){
                return;
            }
        });
    }
";

}

// do injection of js code to layout
$js = "
YUI().use('node', function (Y) {
".$jsedit."
});
";

$lifetime  = 600;   // Seconds to cache this javascript
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
header('Expires: ' . gmdate("D, d M Y H:i:s", time() - $lifetime) . ' GMT');
header('Cache-control: max_age = '. $lifetime);
header('Pragma: ');
header('Content-type: text/javascript; charset=utf-8');  // Correct MIME type

echo $js;
die;
