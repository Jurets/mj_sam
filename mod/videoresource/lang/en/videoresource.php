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
 * English strings for videoresource
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_videoresource
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Video Resource';
$string['modulenameplural'] = 'Video Resource';
$string['modulename_help'] = 'In a place outside of the course (in Moodle, but in administration, to allow for reusability across courses), the video resource will store a YouTube video ID, video description, some additional metadata. The system will also allow for storage of a URL for a podcast, and a long-text transcript. Admins will also be able to enter timecodes and descriptions as chapter markers.';
$string['videoresourcefieldset'] = 'Video Resource Items';
$string['videoresourcename'] = 'videoresource name';
$string['videoresourcename_help'] = 'This is the content of the help tooltip associated with the videoresourcename field. Markdown syntax is supported.';
$string['videoresource'] = 'Video Resource Library';

$string['videoresource:addinstance'] = 'Add a new videoresource';
$string['videoresource:submit'] = 'Submit videoresource';
$string['videoresource:view'] = 'View videoresource';

$string['pluginadministration'] = 'videoresource administration';
$string['pluginname'] = 'Video Resource';

$string['administration'] = 'Video Resource Administration';
$string['manage_videos'] = 'Manage Video Resource Library';

$string['add_video'] = 'Add Video Resource';
$string['edit_video'] = 'Edit Video Resource';
$string['delete_video'] = 'Delete Video Resource'; 

$string['add_video_desc'] = '<a href="{$a}">Add Video Resource</a> allows to add Video Resource.<br/>';

$string['internal_info'] = 'Internal Reference Information';
$string['publicly_info'] = 'Publicly Accessible Fields';

$string['internal_name'] = 'Video Name';
$string['internal_notes'] = 'Notes';
$string['videoid'] = 'Youtube Video ID';
$string['video_title'] = 'Video Title';
$string['description_text'] = 'Description/Followup Text';
$string['podcast_url'] = 'Podcast URL';
$string['transcript'] = 'Transcript';

$string['missing_videoid'] = 'Missing Youtube Video ID';
$string['missing_internal_title'] = 'Missing Internal Title';