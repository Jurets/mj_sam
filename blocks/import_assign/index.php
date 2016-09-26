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
    // load csv
    $file = csv_import_reader::get_new_iid('file');
    $cir = new csv_import_reader($file, 'file'); 
    $readcount = $cir->load_csv_content($result, 'ISO-8859-2', 'semicolon'); 
    if ($readcount === false)
        print_error('csvloaderror', '', $url);
    else if ($readcount == 0)
        print_error('csvemptyfile', 'error', $url);
    
    $filecolumns = assign_validate_upload_columns($cir, $std_fields, $prf_fields, $url);
    /*insert new assigns for course */
    require_once($CFG->dirroot . '/course/modlib.php');
    
    $module_assign = $DB->get_record('modules', array('name'=>'assign'), '*', MUST_EXIST);
    $module_label = $DB->get_record('modules', array('name'=>'label'), '*', MUST_EXIST);
    
    if (file_exists("$CFG->dirroot/mod/assign/mod_form.php") && file_exists("$CFG->dirroot/mod/label/mod_form.php")) {
        require_once("$CFG->dirroot/mod/assign/mod_form.php");
        require_once("$CFG->dirroot/mod/label/mod_form.php");
        include_modulelib('assign');
        include_modulelib('label');
    } else {
        print_error('noformdesc');
    }

    // Process
    $cir->init();
    while ($line = $cir->next()) {
        $table .='<tr>';
        foreach($line as $r){
            $table .='<td>'.$r.'</td>';            
        }
        // common properties
        $section=trim($line[2]);
        if (strtolower(trim($line[0])) == 'label' ) {
            $add ='label';
            $module = $module_label;
        } else {
            $add ='assign';
            $module = $module_assign;
        }
        
        /** <<<< IMPORTANT copied from course/modedit.php*/
        $data = new stdClass();
        $data->course = $course->id;
             
        $fromform1 = new stdClass();
        $fromform1->name = trim($line[0]);
        $fromform1->cmidnumber = '';   
        $fromform1->course = $course->id;  
        $fromform1->coursemodule = '0';   
        $fromform1->section = $section;   
        $fromform1->module = $module->id;   
        $fromform1->modulename = $module->name;
        $fromform1->add = $add;
        $fromform1->sr = '0';
        $fromform1->visible = trim($line[16]);
        $fromform1->submitbutton = 'savechangesanddisplay'; 
        
        if (strtolower(trim($line[0])) == 'label' ) {
            // -------------------------------------- Label ---------------------------------------------------
            $data->intro = trim($line[1]);
            $data->introformat = 1;
            $data->timemodified = time();
            $name = strip_tags(format_string($data->intro,true));
            if (core_text::strlen($name) > 50) {
                $name = core_text::substr($name, 0, 50)."...";
            }
            if (empty($name)) { // arbitrary name
                $name = get_string('modulename','label');
            }
            $data->name = $name;

            $fromform1->introeditor = array('text'=>trim($line[1]), 'format'=>FORMAT_HTML, 'itemid'=>0); // TODO: add better default 
            $fromform1 = add_moduleinfo($fromform1, $course); 
            
            $table .='<td>'.trim($line[0]).' '.  get_string('created','block_import_assign').'</td>'.'</tr>';
        } else { 
        // -------------------------------------- Assignment ---------------------------------------------------
            $fromform1->submissiondrafts = trim($line[7]);
            $fromform1->nosubmissions = trim($line[8]);        
            $fromform1->attemptreopenmethod = trim($line[9]);
            $fromform1->requiresubmissionstatement = 0;
            $fromform1->sendnotifications = 0;
            $fromform1->sendstudentnotifications = 0;  //
            $fromform1->sendlatenotifications = 0;
            if(trim($line[5])!=0){
                $fromform1->duedate = strtotime(trim($line[5]));
            } else {
                $fromform1->duedate = 0; // disabled & 'today'
            }
            if(trim($line[6])!=0){
                $fromform1->cutoffdate = strtotime(trim($line[6]));
            } else {
                $fromform1->cutoffdate = 0; // disabled & 'today'
            }
            if(trim($line[4])!=0){
                $fromform1->allowsubmissionsfromdate = strtotime(trim($line[4]));
            } else {
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

            $fromform1->update = '0';
            $fromform1->return = '0';
            $fromform1->instance = '0';
            $fromform1->groupmode = $course->groupmode;
            $fromform1->grade = trim($line[3]); 
            $fromform1->grade = -2;

             /** <<<<  IMPORTANT copied from course/modedit.php*/
            //ADD new assign
            $dupassign = $DB->get_record("assign", array('name'=>trim($line[0]),'grade'=>trim($line[3]),'course'=>$course->id));
            if( !$dupassign ){
                $fromform1->introeditor = array('text'=>trim($line[1]), 'format'=>FORMAT_HTML, 'itemid'=>0); // TODO: add better default 
                $fromform1 = add_moduleinfo($fromform1, $course);  //////////
                $table .='<td >';
                $table .= trim($line[0]).' '.  get_string('created','block_import_assign');
                $table .='</td>';
            } else {
                $table .='<td>'.get_string('duplicate', 'block_import_assign').'</td>';  
            }      
            $table .='</tr>';
        }
        continue;
    }
    $table .='</tr></table>';
    echo $table;
    
}

echo $OUTPUT->continue_button('index.php?id='.$id);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
exit;