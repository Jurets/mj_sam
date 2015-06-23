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
*  script for download CSV-file with resources list
*/

require_once("../../config.php");
require_once($CFG->dirroot.'\lib\enrollib.php');
require_once('locallib.php');

$delimiter = ';';
$quote = '"';
$eol = "\r\n";

$fileName = 'resources.csv';
$mimeType = 'text/csv';
$terminate = true;
$content = '';

$courses = block_resources_get_all_resources(); //get content

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
            $quote.$quote)) . $eol;
    }
    foreach ($course->videoresources as $resource) {
        $content .= implode($delimiter, array(
            $quote.str_replace('"', '""', $resource->name).$quote, 
            $quote.$quote, $quote.$quote, $quote.$quote)) . $eol;
    }
}

// send file
//sendFile('resources.csv', $content, 'text/csv');

// send content function
//function sendFile($fileName, $content, $mimeType=null, $terminate=true) 
{
    if ($mimeType===null) {
        if (($mimeType=CFileHelper::getMimeTypeByExtension($fileName))===null)

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
        ob_start();
        ob_end_clean();
        echo $content;
        exit(0);
    } else
        echo $content;
}
    

?>