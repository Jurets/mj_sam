<?php

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once("$CFG->dirroot/course/lib.php");
require_once('import_form.php');

$id = required_param('id', PARAM_INT);    // Course Module ID
$url = new moodle_url('/mod/wordbank/import.php', array('id'=>$id));
$PAGE->set_url($url);
if (! $cm = get_coursemodule_from_id('wordbank', $id)) print_error('invalidcoursemodule'); 
if (! $course = $DB->get_record("course", array("id"=>$cm->course)))  print_error('coursemisconf'); 
if (! $fromform = $DB->get_record("wordbank", array("id"=>$cm->instance))) print_error('invalidid', 'wordbank'); 
require_login($course, false, $cm);
$context = context_system::instance();//check
require_capability('mod/wordbank:import', $context);
$maxbytes = $course->maxbytes;
$options = array('maxfiles'=>1, 'maxbytes'=>$maxbytes, 'accepted_types' =>  array('*.csv'));
// array of all valid fields for validation
$std_fields = array('Word(s)', 'Definition', 'Example', 'Translation','Category','Level','Topic');
// array of results with the headers
$prf_fields = array();
$aux = count($std_fields);
$i=1;
$msg =' '; 
$timenow =  time();
$table ='<table><tr>';
foreach($std_fields as $r){
    $prf_fields[] =  $r ;
    $msg.= $r ;
    $table .='<td>'. $r .'</td>';
    if($i<$aux)$msg.=';';
    $i++;
}
$table .='<td>'.get_string('importentriesresult', 'wordbank').'</td>';
//echo '<pre>';print_r($std_fields);print_r($prf_fields);echo '</pre>'; 
$strimportentries = get_string('importentriesfromcsv', 'wordbank');
$PAGE->navbar->add($strimportentries);
$PAGE->set_title(format_string($fromform->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strimportentries);
echo '<div id="intro" class="generalbox box"> <div class="wordbankexplain intro">' . get_string("explainimport","wordbank") . '</div>';
echo '<div class="entrylowersection">'.get_string("csvstructure", "wordbank")."<strong>$msg</strong>.".get_string("csvfirstline", "wordbank").'</div></div>';
//echo $OUTPUT->$OUTPUT->box($aux, 'generalbox', 'intro');
echo $OUTPUT->box_start('wordbankdisplay generalbox');

//include form
$form = new import_assign_import_form(null, array('id'=>$id,'options'=>$options));
if ( !$data = $form->get_data() ) {
    //echo $OUTPUT->box_start('wordbankdisplay generalbox');
    // display upload form
    $data = new stdClass();
    $data->id = $id;
    $form->set_data($data);
    $form->display();    
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}
echo 'type: '.$fromform->mainwordbank;
// to free up memory.
@set_time_limit(0);
raise_memory_limit(MEMORY_EXTRA);
//Get data from file
$result = $form->get_file_content('file');
if (!empty($result)) {
    //else echo '<pre>'.$id;print_r($result);echo '</pre>';
    // load csv
    $file = csv_import_reader::get_new_iid('file');
    $cir = new csv_import_reader($file, 'file'); 
    $readcount = $cir->load_csv_content($result, 'ISO-8859-2', 'semicolon'); 
    if ($readcount === false) print_error('csvloaderror', '', $url);
    else if ($readcount == 0) print_error('csvemptyfile', 'error', $url);
    //$columns = $cir->get_columns();
    $filecolumns = validate_upload_columns($cir, $std_fields, $prf_fields, $url);
    // Process
    echo $OUTPUT->box_start('wordbankdisplay generalbox');
    $cir->init();
    $entry = new stdClass();
    //array to create banks in the course    
    $bank = array();
    $i=0;
    //echo 'asdfsdf';
    while ($line = $cir->next()) {
        //echo '----';print_r($line);
        $table .='<tr>';
        foreach($line as $r)$table .='<td>'.$r.'</td>';
        //insert categories        
        $category = new stdClass();
        $category->userid = $USER->id;
        $level = new stdClass();
        $level->userid = $USER->id;
        $topic = new stdClass();
        $topic->userid = $USER->id;
        // category        
        if(trim($line[4])!=''){
            $category->typecategory = 1;
            if(!$dupcategory = $DB->get_record("wordbank_categories", array('typecategory'=>1,'name'=>$line[4]))){            
                $category->name=$line[4];
                $category->id = $DB->insert_record("wordbank_categories", $category);
            }else $category->id = $dupcategory->id;            
            //print_r($dupcategory);die;
        }
        // level 
        if(trim($line[5])!=''){            
            $level->typecategory = 2;
            if( !$dupcategory = $DB->get_record("wordbank_categories", array('typecategory'=>2,'name'=>$line[5]))){ 
                $level->name=$line[5];
                $level->id = $DB->insert_record("wordbank_categories", $level);
            } else $level->id = $dupcategory->id;
        }
        // topic 
        if(trim($line[6])!=''){            
            $topic->typecategory = 3;
            if( !$dupcategory = $DB->get_record("wordbank_categories", array('typecategory'=>3,'name'=>$line[6]))){ 
                $topic->name=$line[6];
                $topic->id = $DB->insert_record("wordbank_categories", $topic);
            } else $topic->id = $dupcategory->id;                     
        }
        //para insertar bancos 
         
        $bank[$i]['category']=$category->id;
        $bank[$i]['level']=$level->id;
        $bank[$i]['topic']=$topic->id;
        $i++; 
        //insert entry 
        $entry->timecreated      = $timenow;
        $entry->userid           = $USER->id;
        $entry->timecreated      = $timenow;
        $entry->concept          = trim($line[0]); 
        $entry->definition       = trim($line[1]); 
        $entry->example          = trim($line[2]); 
        $entry->translation      = trim($line[3]); 
        $entry->timemodified     = $timenow;
        $entry->approved         = 1;  
        
        if ($DB->record_exists_select('wordbank_entries','LOWER(concept) = :concept ', array('concept'=> textlib::strtolower($line[0]) ))) {
            $table .='<td>'.get_string('entryalreadyexist', 'wordbank') ;
            //update
            //  get record check this //////////////////////////////
            //  ////////////////
            $entryexist = $DB->get_record_sql("SELECT id FROM {wordbank_entries} WHERE LOWER(concept) = :concept ",array('concept'=> textlib::strtolower($line[0]) ));
            //print_r($entryexist);die;
            $entry->id = $entryexist->id;
            //print_r($entry);die;
            if($DB->update_record('wordbank_entries', $entry))$table .=' '.get_string('entryupdated', 'wordbank') ;
            else $table .=get_string('entrynotupdated', 'wordbank');
            $table .='</td>';
            //categories
            //
            if(isset($category->id) && isset($level->id) && isset($topic->id)){
                wordbank_add_category_entry($category->id, $level->id, $topic->id, $entry->id);
            }
        }else{            
            if($entry->id = $DB->insert_record('wordbank_entries', $entry)){
                if(isset($category->id) && isset($level->id) && isset($topic->id)){
                    wordbank_add_category_entry($category->id, $level->id, $topic->id, $entry->id);
                }
                $table .='<td>'.get_string('importentriessuccess', 'wordbank').'</td>';            
            }else $table .='<td>'.get_string('importentrieserror', 'wordbank').'</td>';

        }
        $table .='</tr>';
        continue;
    }
    
    
    /*insert new wordbanks for course */
    if($fromform->mainwordbank == 2 ){
        require_once($CFG->libdir.'/filelib.php');
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/completionlib.php');
        require_once($CFG->libdir.'/conditionlib.php');
        require_once($CFG->libdir.'/plagiarismlib.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        $add ='wordbank'; 
        $sectionreturn=0;
        $section=1;
        $return=0;
        $update=0;
        /** IMPORTANT copied from course/modedit.php >>>*/
        list($module, $context, $cw) = can_add_moduleinfo($course, $add, $section);

        $cm = null;

        $data = new stdClass();
        $data->section          = $section;  // The section number itself - relative!!! (section column in course_sections)
        $data->visible          = $cw->visible;
        $data->course           = $course->id;
        $data->module           = $module->id;
        $data->modulename       = $module->name;
        $data->groupmode        = $course->groupmode;
        $data->groupingid       = $course->defaultgroupingid;
        $data->groupmembersonly = 0;
        $data->id               = '';
        $data->instance         = '';
        $data->coursemodule     = '';
        $data->add              = $add;
        $data->return           = 0; //must be false if this is an add, go back to course view on cancel
        $data->sr               = $sectionreturn;

        if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
            $draftid_editor = file_get_submitted_draft_itemid('introeditor');
            file_prepare_draft_area($draftid_editor, null, null, null, null);
            $data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
        }//echo '<pre>z';print_r($data);//die;

        if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
                and has_capability('moodle/grade:managegradingforms', $context)) {
            require_once($CFG->dirroot.'/grade/grading/lib.php');

            $data->_advancedgradingdata['methods'] = grading_manager::available_methods();
            $areas = grading_manager::available_areas('mod_'.$module->name);

            foreach ($areas as $areaname => $areatitle) {
                $data->_advancedgradingdata['areas'][$areaname] = array(
                    'title'  => $areatitle,
                    'method' => '',
                );
                $formfield = 'advancedgradingmethod_'.$areaname;
                $data->{$formfield} = '';
            }
        }

        if (!empty($type)) { //TODO: hopefully will be removed in 2.0
            $data->type = $type;
        }

        $sectionname = get_section_name($course, $cw);
        $fullmodulename = get_string('modulename', $module->name);
        /** <<<< IMPORTANT copied from course/modedit.php*/

        $data->introeditor = array('text'=>'Worbank imported', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
        $newbank = array();
        foreach($bank as $rb){
            if (!in_array($rb,$newbank)){
                $newbank[] = $rb;
            }
        }
        
        //echo '<pre>';print_r($data); print_r($fromform1);print_r($context);print_r($bank);print_r($newbank);die;
        /* NEW FORM FROM*/
        $fromform1 = new stdClass();
        $fromform1->name = '';   
        $fromform1->introeditor = array();       
        $fromform1->entbypage = '10'; 
        $fromform1->categoriescat = '';  
        $fromform1->category = '';    
        $fromform1->categorieslevel = ''; 
        $fromform1->level = '';   
        $fromform1->categoriestopic = ''; 
        $fromform1->topic = '';          
        $fromform1->visible = '1';   
        $fromform1->cmidnumber = '';   
        $fromform1->groupmode = $course->groupmode;
        $fromform1->course = $course->id;  
        $fromform1->coursemodule = '0';   
        $fromform1->section = '1';   
        $fromform1->module = $module->id;   
        $fromform1->modulename = $module->name;
        $fromform1->instance = '0';
        $fromform1->add = $add;
        $fromform1->update = '0';
        $fromform1->return = '0';
        $fromform1->sr = '0';
        $fromform1->submitbutton = get_string('savechangesanddisplay'); 

        //echo "<pre>course ";/*print_r($COURSE);echo "get ";print_r($_GET);echo ' post ';print_r($_POST);echo ' params ';*/print_r($fromform1);echo "</pre>";die();
        //$returnid = $DB->insert_record("wordbank", $fromform1); 
         /** IMPORTANT copied from course/modedit.php >>>>>*/
        if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
                and has_capability('moodle/grade:managegradingforms', $context)) {
            require_once($CFG->dirroot.'/grade/grading/lib.php');

            $data->_advancedgradingdata['methods'] = grading_manager::available_methods();
            $areas = grading_manager::available_areas('mod_'.$module->name);

            foreach ($areas as $areaname => $areatitle) {
                $data->_advancedgradingdata['areas'][$areaname] = array(
                    'title'  => $areatitle,
                    'method' => '',
                );
                $formfield = 'advancedgradingmethod_'.$areaname;
                $data->{$formfield} = '';
            }
        }

        if (!empty($type)) { //TODO: hopefully will be removed in 2.0
            $data->type = $type;
        }
 
        $modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
        
        if (file_exists($modmoodleform)) {
            require_once($modmoodleform);
        } else {
            print_error('noformdesc');
        }

        include_modulelib($module->name);

        $mformclassname = 'mod_'.$module->name.'_mod_form';
        $mform = new $mformclassname($data, $cw->section, $cm, $course);
         /** <<<<  IMPORTANT copied from course/modedit.php*/
        
        foreach($newbank as $r){             
            $dupbank = $DB->get_record( "wordbank", array('category'=>$r['category'],'level'=>$r['level'],'topic'=>$r['topic'],'course'=>$course->id) );
            if( !$dupbank ){
                $dupcategory = $DB->get_record( "wordbank_categories", array('typecategory'=>3,'id'=>$r['topic']) );
                //echo "<pre>dupcategory ";print_r($dupcategory);;
                $fromform1->name = $module->name.' '.$dupcategory->name;
                $fromform1->introeditor = array('text'=>$module->name.' '.$dupcategory->name, 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default 
                $fromform1->category = $r['category'];   
                $fromform1->level = $r['level'];   
                $fromform1->topic = $r['topic'];
                $fromform1->mainwordbank = 0;  
                //echo '<pre> DATA ';print_r($data);echo'<pre> from form '; print_r($fromform1);//die;
                $mform->set_data($data); //edit $fromform1;            
                $fromform1 = add_moduleinfo($fromform1, $course, $mform); 
                $table .='<tr>';
                $table .='<td colspan="8">';
                $table .= $module->name.' '.$dupcategory->name.' '.  get_string('newwordbankcreated','wordbank');
                $table .='</tr>';
                //echo "<pre>all data ";print_r($mform);echo "</pre>";die();
            }
            
        }
                  
        //die();
    }
    /*insert new wordbanks for course */
    $table .='</tr></table>';
    echo $table;
    
}
//echo '<pre>';print_r($filecolumns);echo '</pre>';

//images process
// Create a unique temporary directory, to process the zip file
// contents.
$zipdir = my_mktempdir($CFG->tempdir.'/', 'bankpic');
$dstfile = $zipdir.'/images.zip'; 
if (!$form->save_file('images', $dstfile, true)) {
    echo $OUTPUT->notification(get_string('cannotmovezip', 'wordbank'));
    @remove_dir($zipdir);
    echo $OUTPUT->box_end(); 
} else {
    $fp = get_file_packer('application/zip');
    $unzipresult = $fp->extract_to_pathname($dstfile, $zipdir);
    if (!$unzipresult) {
        echo $OUTPUT->notification(get_string('cannotunzip', 'wordbank'));
        @remove_dir($zipdir);
    } else {
        // We don't need the zip file any longer, so delete it to make
        // it easier to process the rest of the files inside the directory.
        @unlink($dstfile);

        $results = array ('errors' => 0,'updated' => 0);
        //print_r($results);die;
        process_directory($zipdir, $results);


        // Finally remove the temporary directory with all the user images and print some stats.
        remove_dir($zipdir);
        echo $OUTPUT->notification(get_string('entryupdated', 'wordbank') . ": " . $results['updated'], 'notifysuccess');
        echo $OUTPUT->notification(get_string('importerrors', 'wordbank') . ": " . $results['errors'], ($results['errors'] ? 'notifyproblem' : 'notifysuccess'));
        echo '<hr />';
    }
    echo $OUTPUT->box_end();
}
//print_r($results);
echo $OUTPUT->continue_button('view.php?id='.$id);
echo $OUTPUT->box_end(); 
/// Finish the page
echo $OUTPUT->footer();
exit;

// ----------- Internal functions ----------------

/**
 * Create a unique temporary directory with a given prefix name,
 * inside a given directory, with given permissions. Return the
 * full path to the newly created temp directory.
 *
 * @param string $dir where to create the temp directory.
 * @param string $prefix prefix for the temp directory name (default '')
 *
 * @return string The full path to the temp directory.
 */
function my_mktempdir($dir, $prefix='') {
    global $CFG;

    if (substr($dir, -1) != '/')  $dir .= '/'; 
    do {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (file_exists($path));
    check_dir_exists($path);
    return $path;
}

/**
 * Recursively process a directory, picking regular files and feeding
 * them to process_file().
 *
 * @param string $dir the full path of the directory to process
 * @param string $entryfield the prefix_user table field to use to
 *               match picture files to users.
 * @param bool $overwrite overwrite existing picture or not.
 * @param array $results (by reference) accumulated statistics of
 *              users updated and errors.
 *
 * @return nothing
 */
function process_directory ($dir, &$results) {
    global $OUTPUT;
    if(!($handle = opendir($dir))) {
        echo $OUTPUT->notification(get_string('cannotprocessdir', 'wordbank'));
        return;
    }
    while (false !== ($item = readdir($handle))) {
        if ($item != '.' && $item != '..') {
            if (is_dir($dir.'/'.$item)) {
                process_directory($dir.'/'.$item, $results);
            } else if (is_file($dir.'/'.$item))  {
                $result = process_file($dir.'/'.$item );
                switch ($result) {
                    case 0:
                        $results['errors']++;
                        break;
                    case 1:
                        $results['updated']++;
                        break;
                }
            }
            // Ignore anything else that is not a directory or a file (e.g.,
            // symbolic links, sockets, pipes, etc.)
        }
    }
    closedir($handle);
}

/**
 * Given the full path of a file, try to find the user the file
 * corresponds to and assign him/her this file as his/her picture.
 * Make extensive checks to make sure we don't open any security holes
 * and report back any success/error.
 *
 * @param string $file the full path of the file to process
 * @param string $entryfield the prefix_user table field to use to
 *               match picture files to users.
 * @param bool $overwrite overwrite existing picture or not.
 *
 * @return integer either PIX_FILE_UPDATED, PIX_FILE_ERROR or
 *                  PIX_FILE_SKIPPED
 */
function process_file ($file) {
    global $DB, $OUTPUT, $USER, $fromform, $cm, $context; 
         
    // Add additional checks on the filenames, as they are user
    // controlled and we don't want to open any security holes.
    $path_parts = pathinfo(cleardoubleslashes($file));
    $basename  = $path_parts['basename']; 
    $extension = $path_parts['extension'];
    $extension = strtolower($extension);
    // The picture file name (without extension) must match the concept
    $entryvalue = substr($basename, 0,
                        strlen($basename) -
                        strlen($extension) - 1);
    //echo '<p>';print_r($path_parts);
    //echo $file.'<p>';
    // userfield names are safe, so don't quote them.
    if (!($entry = $DB->get_record('wordbank_entries', array ('concept' => $entryvalue)))) {
        $a = new stdClass();
        $a->userfield = clean_param('concept', PARAM_CLEANHTML);
        $a->uservalue = clean_param($entryvalue, PARAM_CLEANHTML);
        echo $OUTPUT->notification($entryvalue.': '.get_string('entrynotfound', 'wordbank', $a));
        return 0;
    }
    if($extension=='mp3'){
        $fs = get_file_storage();
        $icon = array('contextid'=>$context->id, 'component'=>'import_assign', 'filearea'=>'audio', 'itemid'=>$entry->id, 'filepath'=>'/','userid'=>$USER->id);
        ob_start(); 
        $data = ob_get_clean(); 
        $icon['filename'] = $basename;
        //delete previous files
        $fs->delete_area_files($context->id, 'import_assign', 'audio', $entry->id);
        //copi files from temp dir
        if($file1 = $fs->create_file_from_pathname($icon, $file)){
            $entry->audio=1;
            $DB->update_record('wordbank_entries', $entry);
            //return $file1->get_id();
            return 1;
        }else{
            $entry->audio='';
            $DB->update_record('wordbank_entries', $entry);
            return 0;
        }
    }
    if($extension=='jpg' or $extension=='png' or $extension=='jpeg' ){
        $fs = get_file_storage();
        $icon = array('contextid'=>$context->id, 'component'=>'import_assign', 'filearea'=>'image', 'itemid'=>$entry->id, 'filepath'=>'/','userid'=>$USER->id);
        ob_start(); 
        $data = ob_get_clean(); 
        $icon['filename'] = $basename;
        $fs->delete_area_files($context->id, 'import_assign', 'image', $entry->id);
        if($file1 = $fs->create_file_from_pathname($icon, $file)){
            $entry->image=1;
            $DB->update_record('wordbank_entries', $entry);
            return 1;            
            //return $file1->get_id();
        }else{
            $entry->image='';
            $DB->update_record('wordbank_entries', $entry);
            return 0;
        }
    }
    
}

/**
 * Try to save the given file (specified by its full path) as the
 * picture for the user with the given id.
 *
 * @param integer $id the internal id of the user to assign the
 *                picture file to.
 * @param string $originalfile the full path of the picture file.
 *
 * @return mixed new unique revision number or false if not saved
 */
function import_save_image($id, $originalfile) {
    $context = context_user::instance($id);
    return process_new_icon($context, 'user', 'icon', 0, $originalfile);
}

function import_save_audio($id, $originalfile) {
    $context = context_user::instance($id);
    return process_new_icon($context, 'user', 'icon', 0, $originalfile);
}



