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
* Types Controller for ResourceLib Module
* 
* @author  Yuriy Hetmanskiy
* @version 0.0.1
* @package mod_resourcelib
* @copyright  2014 Jurets
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*
*-----------------------------------------------------------
*/

/// Includes 
require_once("../../config.php");
require_once('locallib.php');

// ---settings
$delimiter = ';';
$quote = '"';
$eol = "\r\n";

$fileName = 'resources.csv';
$mimeType = 'text/csv';
$terminate = true;
$content = '';

// to prevent output before header
ob_start();

// ---get course-resources structure
$courses = block_resources_get_all_resources(); 

// ---build CSV string
$ifHeader = false;
foreach ($courses as $key=>$course) {
    if (!$ifHeader && (!empty($course->resources) || !empty($course->videoresources))) {
        $content .= implode($delimiter, array('title','author','source','rating')) . $eol;
        $ifHeader = true;
    }              
    foreach ($course->resources as $resource) {
        $content .= implode($delimiter, array(
            $quote.str_replace('"', '""', $resource->title).$quote, 
            $quote.str_replace('"', '""', $resource->author).$quote, 
            $quote.str_replace('"', '""', $resource->source).$quote, 
            $quote.$resource->avgrate.$quote)) . $eol;
    }
    foreach ($course->videoresources as $resource) {
        $content .= implode($delimiter, array(
            $quote.str_replace('"', '""', $resource->name).$quote, 
            $quote.$quote, $quote.$quote, $quote.$quote)) . $eol;
    }
} 

// ---send content 
//function sendFile($fileName, $content, $mimeType=null, $terminate=true)  {
if ($mimeType===null) {
    $mimeType='text/plain';
}
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header("Content-type: $mimeType");
header('Content-Length: '.(function_exists('mb_strlen') ? mb_strlen($content,'8bit') : strlen($content)));
header("Content-Disposition: attachment; filename=\"$fileName\"");
header('Content-Transfer-Encoding: binary');
if ($terminate) {
    // clean up the application first because the file downloading could take long time
    // which may cause timeout of some resources (such as DB connection)
    ////ob_start();
    ob_end_clean();
    echo $content;
    exit(0);
} else
    echo $content;
//}