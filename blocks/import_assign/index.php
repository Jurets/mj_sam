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
    'Comments enabled','Onlinetext wordlimit','file maxfiles','Modgrade scale','Visible');
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
        if (strtolower(trim($line[0])) == 'label' ) {
            // -------------------------------------- Label ---------------------------------------------------
            $add ='label'; 
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
            $data->intro            = trim($line[1]);
            $data->introformat      = 1;
            $data->timemodified     = time();
            $name = strip_tags(format_string($data->intro,true));
            if (core_text::strlen($name) > 50) {
                $name = core_text::substr($name, 0, 50)."...";
            }
            if (empty($name)) {
                // arbitrary name
                $name = get_string('modulename','label');
            }
            $data->name             = $name;
            
            ////---------------------------------------------------------------------------
            if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
                $draftid_editor = file_get_submitted_draft_itemid('introeditor');
                file_prepare_draft_area($draftid_editor, null, null, null, null, array('subdirs'=>true));
                $data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
            }

            $sectionname = get_section_name($course, $cw);
            $fullmodulename = get_string('modulename', $module->name);
            /** <<<< IMPORTANT copied from course/modedit.php*/

            /* NEW FORM FROM*/
            $fromform1 = new stdClass();
            $fromform1->name = '';   
            $fromform1->introeditor = array();       
            $fromform1->visible = '1';   
            $fromform1->cmidnumber = '';   
            $fromform1->course = $course->id;  
            $fromform1->coursemodule = '0';   
            $fromform1->section = $section;   
            $fromform1->module = $module->id;   
            $fromform1->modulename = $module->name;
            $fromform1->add = $add;
            $fromform1->sr = '0';
            $fromform1->visible = trim($line[16]);
            $fromform1->submitbutton = get_string('savechangesanddisplay'); 

            $modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";

            if (file_exists($modmoodleform)) {
                require_once($modmoodleform);
            } else {
                print_error('noformdesc');
            }

            include_modulelib($module->name);

            $mformclassname = 'mod_'.$module->name.'_mod_form';
            $mform = new $mformclassname($data, $cw->section, $cm, $course);
            
            ////-------
            $fromform1->name = trim($line[0]);
            $fromform1->introeditor = array('text'=>trim($line[1]), 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default 
            //echo '<pre> DATA ';print_r($data);echo'<pre> from form '; print_r($fromform1);//die;
            $mform->set_data($data); //edit $fromform1;            
            $fromform1 = add_moduleinfo($fromform1, $course, $mform); 
            $DB->insert_record("label", $data);            
            $table .='<td >';
            $table .= trim($line[0]).' '.  get_string('created','block_import_assign');
            $table .='</td>';

            $table .='</tr>';
        } else { 
        // -------------------------------------- Assignment ---------------------------------------------------
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
            $fromform1->grade = -2;
            /*if ($fromform1->grade > 0) {
                $fromform1->grade = -$fromform1->grade;
                //$fromform1->modgrade_type = GRADE_TYPE_SCALE; // default
                //$fromform1->modgrade_scale = trim($line[15]);
            }*/
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
            $fromform1->sendstudentnotifications = 0;  //
            $fromform1->sendlatenotifications = 0;
            if(trim($line[5])!=0){
                $fromform1->duedate = strtotime(trim($line[5]));
            }else{
                $fromform1->duedate = 0; // disabled & 'today'
            }
            if(trim($line[6])!=0){
                $fromform1->cutoffdate = strtotime(trim($line[6]));
            }else{
                $fromform1->cutoffdate = 0; // disabled & 'today'
            }
            if(trim($line[4])!=0){
                $fromform1->allowsubmissionsfromdate = strtotime(trim($line[4]));
            }else{
                $fromform1->allowsubmissionsfromdate = 0; // disabled & 'today'
            }
            $fromform1->teamsubmission = 0;
            $fromform1->requireallteammemberssubmit = 0;
            $fromform1->blindmarking  = 0;
            $fromform1->markingworkflow  = 0;
            $fromform1->markingallocation  = 0;
            $fromform1->assignsubmission_onlinetext_enabled = trim($line[10]);
            $fromform1->assignfeedback_comments_enabled = 1;
            $fromform1->assignsubmission_file_enabled = trim($line[11]);
            $fromform1->assignsubmission_comments_enabled = trim($line[12]);
            if (trim($line[13])>0) {
                $fromform1->assignsubmission_onlinetext_wordlimit = trim($line[13]);
            }
            $fromform1->assignsubmission_file_maxfiles = trim($line[14]);
            $fromform1->assignsubmission_file_maxsizebytes = 10485760; // 10Mb
            
            $fromform1->attemptreopenmethod = 'untilpass';
            
            $fromform1->visible = trim($line[16]);
            $fromform1->submitbutton = get_string('savechangesanddisplay'); 

            //echo "<pre>course ";/*print_r($COURSE);echo "get ";print_r($_GET);echo ' post ';print_r($_POST);echo ' params ';*/print_r($fromform1);echo "</pre>";die();
            //$returnid = $DB->insert_record("assign", $fromform1); 
             /** IMPORTANT copied from course/modedit.php >>>>>*/
            /*if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
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
            }*/

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
        }
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

// -----------