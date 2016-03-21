<?php
/**
 * Global settings for the plugin.
 *
 * @package    local
 * Author       michael.egli@phz.ch
 */
/**
 * Author pp@patrickpollet.net
 * 1) nows runs also with Moodle >2.3
 * 2) added downloading of fields of type image
 * 3) removed many PHP notices 
[Sat May 25 16:41:52 2013] [error] [client 127.0.0.1] PHP Notice:  Undefined variable: section in /var/www/moodle24/local/eduweb_databasefiledownload/lib.php on line 80, referer: http://localhost/moodle24/mod/data/field.php
[Sat May 25 16:41:52 2013] [error] [client 127.0.0.1] PHP Notice:  Trying to get property of non-object in /var/www/moodle24/local/eduweb_databasefiledownload/lib.php on line 80, referer: http://localhost/moodle24/mod/data/field.php
[Sat May 25 16:41:52 2013] [error] [client 127.0.0.1] PHP Warning:  mkdir(): File exists in /var/www/moodle24/local/eduweb_databasefiledownload/lib.php on line 111, referer: http://localhost/moodle24/mod/data/field.php

 */

defined('MOODLE_INTERNAL') || die;


function local_eduweb_databasefiledownload_extends_navigation ($nav) {
    global $USER, $PAGE, $SESSION;

    // Check if we need to manipulate the DOM for adding links for managers and users
    if ($PAGE->context->contextlevel == CONTEXT_MODULE and get_config('local_eduweb_databasefiledownload', 'enabled')){
        if ($cm = get_coursemodule_from_id(false, $PAGE->context->instanceid, 0, false) and $cm->modname == 'data') {
            // Hack for adding custom javascript
            $PAGE->requires->js(new moodle_url('/local/eduweb_databasefiledownload/layout.php', array('id' => $PAGE->context->instanceid)));
        }
    }

    if (isset($SESSION->aucontext)) {
        // Limit navigation to the forum context
        if ($PAGE->context->id != $SESSION->aucontext->id) {
            // context instanceid = course module id for this context (forum)
            redirect(new moodle_url('/mod/data/view.php', array('id' => $SESSION->aucontext->instanceid)));
        }
    }
}


class eduweb_databasefiledownload {


    public function __construct() {

    }

    public function start() {

        global $USER, $PAGE, $SESSION, $CFG, $DB, $OUTPUT;

        error_reporting(E_ALL);
        ini_set('display_errors', FALSE);
        ini_set('display_startup_errors', FALSE);
        date_default_timezone_set('Europe/London');
        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');


        // include and execute original module data view
        // we retrive following vars
        // $cm = coursemodule context
        // $course = course
        // $data = course module data
        // $records = all module records from sql execution
        ob_start();
        require_once($CFG->dirroot . '/mod/data/view.php');


        // loop each file in modul data while generating files array
        $countfiles = 0;
        $fields = $DB->get_records('data_fields', array('dataid'=>$data->id), 'id');
        $files = array();
        $images = array(); //added PP
        foreach ($fields as $field) {

            if ($field->type == 'file'  || $field->type == 'picture') {
            	$fieldobj = data_get_field($field, $data);

                reset($records);
                foreach($records as $record) {

                    // get content indexed by fieldid
                    $select = 'SELECT * FROM {data_content} c, {data_records} r WHERE c.recordid = ? AND r.id = c.recordid AND c.fieldid = ?';
                    $where = array($record->id,$fieldobj->field->id);
                    $c = $DB->get_records_sql($select, $where);
                    $content = $DB->get_record('data_content', array('fieldid'=>$fieldobj->field->id, 'recordid'=>$record->id));
                    $uname = $this->encodeFilenames($record->lastname." ".$record->firstname);
                    $subfolder = $this->encodeFilenames($course->shortname)."/".$this->encodeFilenames($cm->name)."/".
                                 $this->encodeFilenames($fieldobj->field->name);
                    $folder = $this->encodeFilenames($uname);
                    if (isset($_GET['nosort'])) {$subfolder = "";$folder="";} else {}
                    if ($field->type == 'file')
                        $files[] = array('id'=>$content->id,'name'=>$content->content,'itemid'=>$content->id,'contextid'=>$context->id,
                        		 'folder'=>$folder,'subfolder'=>$subfolder);
                    else 
                    	$images[] = array('id'=>$content->id,'name'=>$content->content,'itemid'=>$content->id,'contextid'=>$context->id,
                    			'folder'=>$folder,'subfolder'=>$subfolder);
                }

            }

        }


        // prepare temp directories for zip creating
        $temppath = $CFG->tempdir."/eduweb_databasefiledownload/".time()."_".$cm->id."";
        $source_path = $temppath."/source/";
        $zip_path = $temppath."/zip/";
        $zipfile = $zip_path."/".$cm->id.".zip";
        mkdir($zip_path,0777,true);
        mkdir($source_path,0777,true);


        // get moodle files
        $fs = get_file_storage();

       foreach ($files as $key => $myfile) {

           $path = $source_path."/".$myfile['folder'];
           if (!file_exists($path))
	           mkdir($path,0777,true);

                // Prepare file record object
                $fileinfo = array(
                'component' => 'mod_data',
                'filearea' => 'content',     // usually = table name
                'itemid' => $myfile['itemid'],               // usually = ID of row in table
                'contextid' =>$myfile['contextid'], // ID of context
                'filepath' => '/',           // any path beginning and ending in /
                'filename' => $myfile['name']); // any filename

                $fileexists = $fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

                if ($fileexists == 1) {
                    // Get and copy file
                    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                    $path = $path."/".$myfile['subfolder'];
                    if (!file_exists($path))
                    	mkdir($path,0777,true);
                    $f = $file->get_filename();
                    $cp = $file->copy_content_to($path."/".$myfile['id']."-".$this->encodeFilenames($f));
                    $countfiles++;
                 } 



       }
       //added PP for images processing
       foreach ($images as $key => $myfile) {
       
       	$path = $source_path."/".$myfile['folder'];
       	if (!file_exists($path))
       		mkdir($path,0777,true);
       
       	// Prepare file record object
       	$fileinfo = array(
       			'component' => 'mod_data',
       			'filearea' => 'content',     // usually = table name
       			'itemid' => $myfile['itemid'],               // usually = ID of row in table
       			'contextid' =>$myfile['contextid'], // ID of context
       			'filepath' => '/',           // any path beginning and ending in /
       			'filename' => $myfile['name']); // any filename
       
       	$fileexists = $fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
       
       	if ($fileexists == 1) {
       		// Get and copy file
       		$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
       		$path = $path."/".$myfile['subfolder'];
       		if (!file_exists($path))
       			mkdir($path,0777,true);
       		$f = $file->get_filename();
       		$cp = $file->copy_content_to($path."/".$myfile['id']."-".$this->encodeFilenames($f));
       		$countfiles++;
       	}
       
       
       
       }


        if ($countfiles > 0) {
            ob_end_clean();
            ob_start();

            // make zip archiv of fetched files
            $zip = new ZipArchive();
            if ($zip->open($zipfile, ZIPARCHIVE::CREATE) !== TRUE) {
                die ("Could not open archive");
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_path));
            foreach ($iterator as $key=>$value) {
                if ($value->getFilename() != '.' & $value->getFilename() != '..') {
                    $zip->addFile(realpath($key), (substr($key,strlen($source_path)))) or die ("ERROR: Could not add file: $key");
                } else {
                }
            }
            $zip->close();

            if (file_exists($zipfile)) {

                // push octet steam
                $basename = basename($zipfile);
                header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header ("Content-Type: application/zip");
                header ("Content-Length: " . filesize($zipfile));
                header ("Content-Disposition: attachment; filename=$basename");
                ob_clean();
                flush();

                $fp=fopen($zipfile,"rb");

                while(!feof($fp))
                {
                    print(fread($fp,1024*8));

                    flush();
                    ob_flush();
                    if( connection_aborted() )
                    {
                        //do code for handling aborts
                    }
                }

                ob_end_clean();
                ob_start();
                $this->deleteDirectory($temppath);
                ob_end_clean();
                exit;
            }

        }  else {
            // there are no files to download
            echo "<script>alert('".get_string('thereareno', 'local_eduweb_databasefiledownload')."');</script>";
        }

}


    /*
     *  some general function for this class
     */

    // encode file names
    function encodeFilenames($string) {

        $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
        $string = preg_replace('~&([a-z\.]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $string);
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $string = preg_replace(array('~[^0-9a-z\.]~i', '~[ -]+~'), ' ', $string);

        $a = trim($string, ' -');

        if (strlen($a) > 60) {
            $a = substr($a,0,40)."...".substr($a,-20,20);
        } else { }

        return $a;


    }


    //Delete folder function
    function deleteDirectory($dir) {

        if (strlen($dir)<3) return true;
        if (!file_exists($dir)) return true;
        if (!is_dir($dir) || is_link($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . "/" . $item)) {
                chmod($dir . "/" . $item, 0777);
                if (!$this->deleteDirectory($dir . "/" . $item)) return false;
            };
        }


        return rmdir($dir);

    }



}
