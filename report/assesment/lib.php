<?php
/**
*  This lib is intended for work with 
*  Some hints for working with File API
*  - Use file_storage ($fs) for file operations
*  - Use $fs->get_file_instance($filerecord) to get file instance from DB
*/

defined('MOODLE_INTERNAL') || die;

/**
*  This class allow to collect all Files from Submissions made by User 
*  for all mod_assign instances and put they to Zip archive for downloading
*/
class assesment_download {

    private $thereareno;
    
    public function __construct() {
         //any code for initing
         $this->thereareno = "<script>alert('".get_string('thereareno', 'report_assesment')."');</script>";
    }

    public function start($userid, $mod='') {

        global $CFG, $DB;

        $component = 'assignsubmission_file';
        $filearea = 'submission_files';
        
        //ob_start();
        // loop each file in modul data while generating files array
        $countfiles = 0;

        $fs = get_file_storage();
        
        // get user info, define folder structure
        $userinfo = $DB->get_record('user', array('id'=>$userid), 'id, username, firstname, lastname');
        $uname = $this->encodeFilenames($userinfo->lastname." ".$userinfo->firstname);
        $folder = $this->encodeFilenames($uname);
        
        $sql =
            "SELECT f.id AS itemid, f.contextid, f.component, f.filearea, f.itemid, f.filepath, f.filename, f.userid, f.filesize, f.mimetype, f.status, f.source, f.author, f.timecreated, f.timemodified, f.sortorder
            , c.shortname AS coursename, a.name as cmname
            FROM mdl_files f
             LEFT JOIN mdl_context x ON x.id = f.contextid
             LEFT JOIN mdl_course_modules cm ON cm.id = x.instanceid
             LEFT JOIN mdl_assign a ON cm.instance = a.id
             LEFT JOIN mdl_course c ON c.id = cm.course
            WHERE f.component = :component AND f.filearea = :filearea AND f.userid = :userid AND f.filesize > 0
        ";
        $conditions = array('component'=>$component, 'filearea'=>$filearea, 'userid'=>$userid);
        $filerecords = $DB->get_records_sql($sql, $conditions);
        // build file structure array
        $files = array();
        foreach ($filerecords as $file) {
            $subfolder = $this->encodeFilenames($file->coursename) . "/" . $this->encodeFilenames($file->cmname);
            if (isset($_GET['nosort'])) {
                $subfolder = ""; $folder="";
            }
            $fileexists = $fs->file_exists($file->contextid, $component, $filearea, $file->itemid, $file->filepath, $file->filename);
            if ($fileexists == 1) {
                $file->folder = $folder;
                $file->subfolder = $subfolder;
                $files[] = $file;
            }
        }
        if (empty($files)) {
            // there are no files to download
            echo $this->thereareno;
        } else {
            // prepare temp directories for zip creating
            $temppath = $CFG->tempdir . "/assesments_download/" . time() . "_" . $userid;
            $source_path = $temppath . "/source/";
            $zip_path = $temppath . "/zip/";
            $zipfile = $zip_path . "/user_" . $userid . ".zip";
            make_writable_directory($zip_path); //new dir
            make_writable_directory($source_path); //new dir

            // get moodle files
            foreach ($files as $key => $file) {
                $path = $source_path . $file->folder;
                make_writable_directory($path, false); //new dir
                // Get and copy file
                $fileInstance = $fs->get_file($file->contextid, $component, $filearea, $file->itemid, $file->filepath, $file->filename);
                $path = $path . '/' . $file->subfolder;
                make_writable_directory($path, false); //new dir
                $filename = $fileInstance->get_filename();
                $cp = $fileInstance->copy_content_to($path . "/" . $file->itemid . "-" . $this->encodeFilenames($filename));
                $countfiles++;
            }
            // Move files to Zip archive 
            if ($countfiles > 0) {
                // make zip archiv of fetched files
                $zip = new ZipArchive();
                if ($zip->open($zipfile, ZIPARCHIVE::CREATE) !== TRUE) {
                    die ("Could not open archive");
                }
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_path));
                foreach ($iterator as $key=>$value) {
                    if ($value->getFilename() != '.' & $value->getFilename() != '..') {
                        $zip->addFile(realpath($key), (substr($key,strlen($source_path)))) or die ("ERROR: Could not add file: $key");
                    }
                }
                $zip->close();
                
                // send file to download
                if (is_file($zipfile)) {
                    send_temp_file($zipfile, basename($zipfile));
                }
            }  else {
                // there are no files to download
                echo $this->thereareno;
            }
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