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
 * English strings for resourcelib
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_resourcelib
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Resource Library';
$string['modulenameplural'] = 'Resource Library';
$string['modulename_help'] = 'The module will allow the creation on generic web links outside of the course. There are several fields of metadata that will be collected. This is done outside of the course to allow for reusability across courses. Resources will be organized into list subsections, and multiple subsections may be displayed at once.
In the course, a user can insert one or more lists (with subsections) on a single page. If the number of lists is greater than one, the lists (with subsections) will be displayed in tabs (using the Bootstrap framework code). 
Each resource will contain an icon, the title and link to a video, along with other metadata. The system will record clicks on each link in the event log. Users will also have the ability to rate each resource using a star rating system. They may select a rating one time per resource per course, and may not change it. Once a rating has been selected, the user will be able to see the average rating for the resource in that course.';
$string['resourcelibfieldset'] = 'Resource Library Items';
$string['resourcelibname'] = 'resourcelib name';
$string['resourcelibname_help'] = 'This is the content of the help tooltip associated with the resourcelibname field. Markdown syntax is supported.';
$string['resourcelib'] = 'Resource Library';

$string['eventresourceviewed'] = 'Resource was viewed';

$string['resourcelib:addinstance'] = 'Add a new resourcelib';
$string['resourcelib:submit'] = 'Submit resourcelib';
$string['resourcelib:view'] = 'View resourcelib';
$string['resourcelib:viewallratings'] = 'View all raw ratings given by individuals';
$string['resourcelib:viewanyrating'] = 'View total ratings that anyone received';
$string['resourcelib:viewrating'] = 'View the total rating you received';
$string['resourcelib:rate'] = 'Rate entries';
$string['resourcelib:manage'] = 'Manage ResourceLib Items';
$string['resourcelib:edit'] = 'Edit ResourceLib'; 
$string['resourcelib:addforum'] = 'Set forum';
$string['resourcelib:addquestionnaire'] = 'Set questionnaire';

$string['configuration'] = 'Configuration';
$string['manage_types_desc'] = '<a href="{$a}">Resource Types Page</a> allows to configure types of Resources.<br/>';
$string['manage_items_desc'] = '<a href="{$a}">Resources Page</a> allows to add, change and delete Resource Items.<br/>';
$string['manage_sections_desc'] = '<a href="{$a}">Sections Page</a> allows to configure Resource Sections.<br/>';
$string['manage_lists_desc'] = '<a href="{$a}">Lists Page</a> allows to configure Resource Sections.<br/>';

$string['editcontentlabel'] = '';
$string['editcontentlink'] = 'For course module content editing go to <a href="{$a}">Edit page</a>';

$string['pluginadministration'] = 'resourcelib administration';
$string['pluginname'] = 'Resource Library';

$string['resource'] = 'Resource';
$string['section'] = 'Section';

$string['settings'] = 'Resource Library Settings';
$string['administration'] = 'Resource Library Administration';
$string['manage_types'] = 'Manage Resource Types';
$string['manage_items'] = 'Manage Resource Items';
$string['manage_lists'] = 'Manage Resource Lists';
$string['manage_sections'] = 'Manage Resource Library Sections';

$string['addtype'] = 'Add Resource Type';
$string['edittype'] = 'Edit Resource Type';
$string['deletetype'] = 'Delete Resource Type';

$string['addsection'] = 'Add Section';
$string['editsection'] = 'Edit Section';
$string['deletesection'] = 'Delete Section';
$string['viewsection'] = 'View Section';
$string['add_section_resource'] = 'Add Resource to Section';
$string['del_section_resource'] = 'Delete Resource from Section';

$string['addlist'] = 'Add List';
$string['editlist'] = 'Edit List';
$string['deletelist'] = 'Delete List';
$string['viewlist'] = 'View List';
$string['add_list_section'] = 'Add Section to List';
$string['del_list_section'] = 'Delete Section from List';

$string['additem'] = 'Add Resource Item';
$string['edititem'] = 'Edit Resource Item';
$string['deleteitem'] = 'Delete Resource Item';

$string['type'] = 'Type';
$string['internal_title'] = 'Internal Name';
$string['copyright'] = 'Copyright Info';
$string['length'] = 'Resource Length';
$string['public_date'] = 'Publication Date';
$string['author'] = 'Author';
$string['source'] = 'Source';
$string['time_estimate'] = 'Time Estimate';
$string['embed_code'] = 'Embed Code';
$string['display_name'] = 'Display Name';
$string['section_count'] = 'Section count';
$string['resource_count'] = 'Resource count';

$string['missing_resource'] = 'Missing Resource';
$string['missing_section'] = 'Missing Section';
$string['no_resources'] = 'There is no Resources in this Section';
$string['no_sections'] = 'There is no Sections in this List';
$string['there_are_no_forums']  = 'There is no forums in this Course! Firstly add a forum';
$string['there_are_no_questionnaires']  = 'There is no questionnaires in this Course! Firstly add a questionnaire';

$string['all_lists_in_course_module'] = 'All of Lists added in this Course Module';
$string['no_lists_in_course_module']  = 'There are no Lists in this Course Module';
$string['there_are_no_lists']  = 'There is no Lists! Go to <a href="{$a}">Resource List Page</a> for Lists Manage';

$string['deletecheck_item_fromlist'] = 'Are you absolutely sure to delete "{$a}" from content?';

$string['deletecheck_resurce_fromsection'] = 'Are you absolutely sure you want to delete resource {$a} from section?';
$string['deletecheck_section_fromlist'] = 'Are you absolutely sure you want to delete section {$a} from list?';
$string['enter_estimated_time'] = 'Enter estimated time to read this resource IN WHOLE MINUTES';
$string['resources_exists'] = 'There are resources of this type';
$string['section_resource_exists'] = 'There are resources in this section';
$string['section_exists'] = 'There are sections in this list';
$string['resources_exists_in_section'] = 'This resource is present in the sections';

$string['cannotmovesection'] = 'Can not move this Section with ID {$a}';
$string['cannotmoveresource'] = 'Can not move this Resource with ID {$a}';
$string['cannotmoveitem'] = 'Can not move this Item with ID {$a}';
$string['cannotdelitem'] = 'Can not delete this Item with ID {$a}';

$string['listfield'] = 'Resource Library Lists';
$string['listfield_help'] = 'Select list from select element below. You can choose multiple items by pressing button <Ctrl> and mouse clicking';

$string['your_rate'] = 'Your rate is';

$string['bookmark'] = 'Bookmark this Resource';
$string['unbookmark'] = 'Remove from Bookmarks';
$string['bookmarked'] = 'bookmarked';
