<?php

require_once("../../config.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/lib/gradelib.php');
require_once($CFG->dirroot.'/mod/assign/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once("$CFG->dirroot/course/lib.php");
require_once('import_form.php');

$id = required_param ('id', PARAM_INT);           // Course Module ID
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course);
//print_r($course);
$context = context_course::instance($course->id);
require_capability('block/import_assign:addinstance', $context);
$PAGE->navbar->add(get_string("import", "block_import_assign"));
/// If we are in approval mode, prit special header
$PAGE->set_title(format_string(get_string("title", "block_import_assign")));
$PAGE->set_heading($course->fullname);
$url = new moodle_url('/blocks/import_assign/index.php', array('id'=>$id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$options = array('maxfiles'=>1, 'maxbytes'=>$course->maxbytes, 'accepted_types' =>  array('*.csv'));

$std_fields = array('Name', 'Description', 'Section','grade','Allow submitions from','Due date','Cut of date',
    'Submission drafts','No submissions','Attempt reopen method','Onlinetext enabled','File enabled',
    'Comments enabled','Onlinetext wordlimit','file maxfiles','Modgrade scale');
// array of results with the headers
$prf_fields = array();
$aux = count($std_fields);
$i=1;
$msg =' '; 
$timenow =  time();
$table ='<table border="1" ><tr>';
foreach($std_fields as $r){
    $prf_fields[] =  $r ;
    $msg.= $r ;
    $table .='<td>'. $r .'</td>';
    if($i<$aux)$msg.=';';
    $i++;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string(get_string('title','block_import_assign'))); 
//include form
$form = new import_assign_import_form(null, array('id'=>$id,'options'=>$options));
if ( !$data = $form->get_data() ) {
    echo $OUTPUT->box_start(' generalbox');
    // display upload form
    $data = new stdClass();
    $data->id = $id;
    $form->set_data($data);
    $form->display();    
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}
// to free up memory.
@set_time_limit(0);
raise_memory_limit(MEMORY_EXTRA);
//Get data from file
$result = $form->get_file_content('file');
if (!empty($result)) {
    echo $OUTPUT->box_start(' generalbox');
    //else echo '<pre>'.$id;print_r($result);echo '</pre>';
    // load csv
    $file = csv_import_reader::get_new_iid('file');
    $cir = new csv_import_reader($file, 'file'); 
    $readcount = $cir->load_csv_content($result, 'ISO-8859-2', 'semicolon'); 
    if ($readcount === false) print_error('csvloaderror', '', $url);
    else if ($readcount == 0) print_error('csvemptyfile', 'error', $url);
    //$columns = $cir->get_columns();
    $filecolumns = assign_validate_upload_columns($cir, $std_fields, $prf_fields, $url);
    // Process
    $cir->init();
    $entry = new stdClass();
    //array to create assigns in the course    
    $assign = array();
    $i=0;
    /*insert new assigns for course */
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->libdir.'/completionlib.php');
    require_once($CFG->libdir.'/plagiarismlib.php');
    require_once($CFG->dirroot . '/course/modlib.php');
    $cm = null;
    $data = new stdClass();
    while ($line = $cir->next()) {
        //echo '----';print_r($line);
        $table .='<tr>';
        foreach($line as $r){
            $table .='<td>'.$r.'</td>';            
        }
        //insert assign
        //trim($line[5])
        $add ='assign'; 
        $sectionreturn=0;
        $section=trim($line[2]);
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
        $data->id               = '';
        $data->instance         = '';
        $data->coursemodule     = '';
        $data->add              = $add;
        $data->return           = 0; //must be false if this is an add, go back to course view on cancel
        $data->sr               = $sectionreturn;

        if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
            $draftid_editor = file_get_submitted_draft_itemid('introeditor');
            file_prepare_draft_area($draftid_editor, null, null, null, null, array('subdirs'=>true));
            $data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
        }

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
        
        $data->introeditor = array('text'=>trim($line[1]), 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
        
        //echo '<pre>';print_r($data); print_r($fromform1);print_r($context);print_r($assign);print_r($newassign);die;
        /* NEW FORM FROM*/
        $fromform1 = new stdClass();
        $fromform1->name = '';   
        $fromform1->introeditor = array();       
        $fromform1->grade = trim($line[3]); 
        $fromform1->visible = '1';   
        $fromform1->cmidnumber = '';   
        $fromform1->groupmode = $course->groupmode;
        $fromform1->course = $course->id;  
        $fromform1->coursemodule = '0';   
        $fromform1->section = $section;   
        $fromform1->module = $module->id;   
        $fromform1->modulename = $module->name;
        $fromform1->instance = '0';
        $fromform1->add = $add;
        $fromform1->update = '0';
        $fromform1->return = '0';
        $fromform1->sr = '0';
        $fromform1->submissiondrafts = trim($line[7]);
        $fromform1->nosubmissions = trim($line[8]);        
        $fromform1->attemptreopenmethod = trim($line[9]);
        $fromform1->requiresubmissionstatement = 0;
        $fromform1->sendnotifications = 0;
        $fromform1->sendlatenotifications = 0;
        if(trim($line[5])!=0){
            $fromform1->duedate = strtotime(trim($line[5]));
        }else{
            $fromform1->duedate = time()+ (60 * 60 * 24 * 365);
        }
        if(trim($line[6])!=0){
            $fromform1->cutoffdate = strtotime(trim($line[6]));
        }else{
            $fromform1->cutoffdate = time()+ (60 * 60 * 24 * 365);
        }
        if(trim($line[4])!=0){
            $fromform1->allowsubmissionsfromdate = strtotime(trim($line[4]));
        }else{
            $fromform1->allowsubmissionsfromdate = time()+ (60 * 60 * 24 * 365);
        }
        $fromform1->teamsubmission = 0;
        $fromform1->requireallteammemberssubmit = 0;
        $fromform1->blindmarking  = 0;
        $fromform1->markingworkflow  = 0;
        $fromform1->markingallocation  = 0;
        $fromform1->assignsubmission_onlinetext_enabled = trim($line[10]);
        $fromform1->assignsubmission_file_enabled = trim($line[11]);
        $fromform1->assignsubmission_comments_enabled = trim($line[12]);
        if(trim($line[13])>0)$fromform1->assignsubmission_onlinetext_wordlimit = trim($line[13]);
        $fromform1->assignsubmission_file_maxfiles = trim($line[14]);
        $fromform1->modgrade_scale = trim($line[15]);
        $fromform1->submitbutton = get_string('savechangesanddisplay'); 

        //echo "<pre>course ";/*print_r($COURSE);echo "get ";print_r($_GET);echo ' post ';print_r($_POST);echo ' params ';*/print_r($fromform1);echo "</pre>";die();
        //$returnid = $DB->insert_record("assign", $fromform1); 
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
        //ADD new assign
        $dupassign = $DB->get_record( "assign", array('name'=>trim($line[0]),'grade'=>trim($line[3]),'course'=>$course->id) );
        if( !$dupassign ){
            $fromform1->name = trim($line[0]);
            $fromform1->introeditor = array('text'=>trim($line[1]), 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default 
             //echo '<pre> DATA ';print_r($data);echo'<pre> from form '; print_r($fromform1);//die;
            $mform->set_data($data); //edit $fromform1;            
            $fromform1 = add_moduleinfo($fromform1, $course, $mform); 
            $table .='<td >';
            $table .= trim($line[0]).' '.  get_string('created','block_import_assign');
            $table .='</td>';
            //echo "<pre>all data ";print_r($mform);echo "</pre>";die();
        }else{
        //
            //insert categories        
            $table .='<td>'.get_string('duplicate', 'block_import_assign') ;
            $table .='</td>';  
        }      
            $table .='</tr>';
        continue;
    }
    $table .='</tr></table>';
    echo $table;
    
}
//echo '<pre>';print_r($filecolumns);echo '</pre>';

//images process
// Create a unique temporary directory, to process the zip file
// contents.
//print_r($results);
echo $OUTPUT->continue_button('index.php?id='.$id);
echo $OUTPUT->box_end();
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
        echo $OUTPUT->notification(get_string('cannotprocessdir', 'assign'));
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
    if (!($entry = $DB->get_record('assign_entries', array ('concept' => $entryvalue)))) {
        $a = new stdClass();
        $a->userfield = clean_param('concept', PARAM_CLEANHTML);
        $a->uservalue = clean_param($entryvalue, PARAM_CLEANHTML);
        echo $OUTPUT->notification($entryvalue.': '.get_string('entrynotfound', 'assign', $a));
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
            $DB->update_record('assign_entries', $entry);
            //return $file1->get_id();
            return 1;
        }else{
            $entry->audio='';
            $DB->update_record('assign_entries', $entry);
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
            $DB->update_record('assign_entries', $entry);
            return 1;            
            //return $file1->get_id();
        }else{
            $entry->image='';
            $DB->update_record('assign_entries', $entry);
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
