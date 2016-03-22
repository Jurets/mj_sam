<?php
/**
 * Admin Report plugin for Downloading of User Assignments
 *
 * @package    report
 * @subpackage assesment
 * @copyright  2016 Jurets
 * @author     Jurets <jurets75@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    global $CFG;
    require_once('../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/user/filters/lib.php');

    $sort = optional_param('sort', 'name', PARAM_ALPHANUM);
    $dir  = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 30, PARAM_INT);        // how many per page
    
    $assignments_userid = optional_param('assignments', 0, PARAM_INT);        // how many per page
    
    admin_externalpage_setup('reportassesment');

    $sitecontext = context_system::instance();
    $site = get_site();

    if (!has_capability('moodle/user:update', $sitecontext) and !has_capability('moodle/user:delete', $sitecontext)) {
        print_error('nopermissions', 'error', '', 'edit/delete users');
    }

    $stredit   = get_string('edit');
    $strassignments = get_string('assignments', 'report_assesment');
    $strconfirm = get_string('confirm');

    $returnurl = new moodle_url('/report/assesment/index.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));

    // ---------- Main process of GET params --
    if ($assignments_userid) {
        //echo '$assignments_userid = '.$assignments_userid;
        require_once($CFG->dirroot.'/report/assesment/lib.php');
        
        /*$files = report_assesment_getzip($assignments_userid, 'mod_assignment');
        echo '<ul>';
        foreach ($files as $file) {
            echo '<li>'.$file->get_filename().'</li>';
        }
        echo '</ul>';*/        //DebugBreak();
        // testing of file download
        //$file = array_shift($files);
        //send_stored_file($file, 86400, 0, true, array('dontdie'));
        $export = new assesment_download();
        $export->start($assignments_userid);
    }
    // -- end of process
    
    $ufiltering = new user_filtering();
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'report_assesment'));
    
    // Carry on with the user listing
    $context = context_system::instance();
    $extracolumns = get_extra_user_fields($context);
    $columns = array_merge(array('firstname', 'lastname'), $extracolumns, array('city', 'country', 'lastaccess'));

    foreach ($columns as $column) {
        $string[$column] = get_user_field_name($column);
        if ($sort != $column) {
            $columnicon = "";
            if ($column == "lastaccess") {
                $columndir = "DESC";
            } else {
                $columndir = "ASC";
            }
        } else {
            $columndir = $dir == "ASC" ? "DESC":"ASC";
            if ($column == "lastaccess") {
                $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
            } else {
                $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
            }
            $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

        }
        $$column = "<a href=\"user.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
    }
    
    $override = new stdClass();
    $override->firstname = 'firstname';
    $override->lastname = 'lastname';
    $fullnamelanguage = get_string('fullnamedisplay', '', $override);
    if (($CFG->fullnamedisplay == 'firstname lastname') or
        ($CFG->fullnamedisplay == 'firstname') or
        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
        $fullnamedisplay = "$firstname / $lastname";
        if ($sort == "name") { // If sort has already been set to something else then ignore.
            $sort = "firstname";
        }
    } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname').
        $fullnamedisplay = "$lastname / $firstname";
        if ($sort == "name") { // This should give the desired sorting based on fullnamedisplay.
            $sort = "lastname";
        }
    }

    list($extrasql, $params) = $ufiltering->get_sql_filter();
    $users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params, $context);
    $usercount = get_users(false);
    $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

    $strall = get_string('all');

    $baseurl = new moodle_url('/report/assesment/index.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
    //echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    
    flush();
    if (!$users) {
        $match = array();
        echo $OUTPUT->heading(get_string('nousersfound'));

        $table = NULL;
    } else {
        $countries = get_string_manager()->get_list_of_countries(false);
        if (empty($mnethosts)) {
            $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
        }
        foreach ($users as $key => $user) {
            if (isset($countries[$user->country])) {
                $users[$key]->country = $countries[$user->country];
            }
        }
        if ($sort == "country") {  // Need to resort by full country name, not code
            foreach ($users as $user) {
                $susers[$user->id] = $user->country;
            }
            asort($susers);
            foreach ($susers as $key => $value) {
                $nusers[] = $users[$key];
            }
            $users = $nusers;
        }

        $table = new html_table();
        $table->head = array();
        $table->colclasses = array();
        $table->head[] = $fullnamedisplay;
        $table->attributes['class'] = 'admintable generaltable';
        foreach ($extracolumns as $field) {
            $table->head[] = ${$field};
        }
        $table->head[] = $city;
        $table->head[] = $country;
        $table->head[] = $lastaccess;
        $table->head[] = get_string('download');
        $table->colclasses[] = 'centeralign';
        $table->head[] = "";
        $table->colclasses[] = 'centeralign';

        $table->id = "users";
        foreach ($users as $user) {
            $buttons = array();
            $lastcolumn = '';
            // download button
            if (is_mnet_remote_user($user) /*or $user->id == $USER->id*/ or is_siteadmin($user)) {
                // no operation of self, mnet accounts or admins allowed
            } else {
                $buttons[] = html_writer::link(
                    new moodle_url($returnurl, array('assignments'=>$user->id)), 
                    html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/download'), 'alt'=>$strassignments, 'class'=>'iconsmall')), 
                    array('title'=>$strassignments)
                );
            }

            // the last column - confirm or mnet info
            if (is_mnet_remote_user($user)) {
                // all mnet users are confirmed, let's print just the name of the host there
                if (isset($mnethosts[$user->mnethostid])) {
                    $lastcolumn = get_string($accessctrl, 'mnet').': '.$mnethosts[$user->mnethostid]->name;
                } else {
                    $lastcolumn = get_string($accessctrl, 'mnet');
                }

            } else if ($user->confirmed == 0) {
                if (has_capability('moodle/user:update', $sitecontext)) {
                    $lastcolumn = html_writer::link(new moodle_url($returnurl, array('confirmuser'=>$user->id, 'sesskey'=>sesskey())), $strconfirm);
                } else {
                    $lastcolumn = "<span class=\"dimmed_text\">".get_string('confirm')."</span>";
                }
            }

            if ($user->lastaccess) {
                $strlastaccess = format_time(time() - $user->lastaccess);
            } else {
                $strlastaccess = get_string('never');
            }
            $fullname = fullname($user, true);

            $row = array ();
            $row[] = "<a href=\"../user/view.php?id=$user->id&amp;course=$site->id\">$fullname</a>";
            foreach ($extracolumns as $field) {
                $row[] = $user->{$field};
            }
            $row[] = $user->city;
            $row[] = $user->country;
            $row[] = $strlastaccess;
            if ($user->suspended) {
                foreach ($row as $k=>$v) {
                    $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
                }
            }
            $row[] = implode(' ', $buttons);
            $row[] = $lastcolumn;
            $table->data[] = $row;
        }
    }

    // add filters
    $ufiltering->display_add();
    $ufiltering->display_active();

    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class'=>'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    }
    
    echo $OUTPUT->footer();