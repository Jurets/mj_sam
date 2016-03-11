<?php
global $CFG;
require_once($CFG->dirroot . '/grade/report/user/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
* This class extends base class of User Report
* 
*/
class grade_tree_teacher extends grade_tree {
    
    public function get_element_header(&$element, $withlink=false, $icon=true, $spacerifnone=false) {
        $header = '';

        if ($icon) {
            $header .= $this->get_element_icon($element, $spacerifnone);
        }

        $header .= $element['object']->get_name();

        if ($element['type'] != 'item' and $element['type'] != 'categoryitem' and
            $element['type'] != 'courseitem') {
            return $header;
        }

        if ($withlink) {
            $url = $this->get_activity_link($element);
            if ($url) {
                $a = new stdClass();
                $a->name = get_string('modulename', $element['object']->itemmodule);
                $title = get_string('linktoactivity', 'grades', $a);

                $header = html_writer::link($url, $header, array('title' => $title));
            }
        }

        return $header;
    }
        
    private function get_activity_link($element) {
        global $CFG;
        /** @var array static cache of the grade.php file existence flags */
        static $hasgradephp = array();

        $itemtype = $element['object']->itemtype;
        $itemmodule = $element['object']->itemmodule;
        $iteminstance = $element['object']->iteminstance;
        $itemnumber = $element['object']->itemnumber;

        // Links only for module items that have valid instance, module and are
        // called from grade_tree with valid modinfo
        if ($itemtype != 'mod' || !$iteminstance || !$itemmodule || !$this->modinfo) {
            return null;
        }

        // Get $cm efficiently and with visibility information using modinfo
        $instances = $this->modinfo->get_instances();
        if (empty($instances[$itemmodule][$iteminstance])) {
            return null;
        }
        $cm = $instances[$itemmodule][$iteminstance];

        // Do not add link if activity is not visible to the current user
        if (!$cm->uservisible) {
            return null;
        }

        if (!array_key_exists($itemmodule, $hasgradephp)) {
            if (file_exists($CFG->dirroot . '/mod/' . $itemmodule . '/grade.php')) {
                $hasgradephp[$itemmodule] = true;
            } else {
                $hasgradephp[$itemmodule] = false;
            }
        }

        // If module has grade.php, link to that, otherwise view.php
        if ($hasgradephp[$itemmodule]) {
            $args = array('id' => $cm->id, 'itemnumber' => $itemnumber);
            if (isset($element['userid'])) {
                $args['userid'] = $element['userid'];
            }
            return new moodle_url('/mod/' . $itemmodule . '/grade.php', $args);
        } else {
            $args = array('id' => $cm->id);
            if ($itemmodule == 'assign') {
                $rownum = $this->get_rownum($cm, $element['userid']);
                if ($rownum !== FALSE && $rownum >= 0) {
                    $args = array_merge($args, array('rownum'=>$rownum, 'action'=>'grade'));
                }
            }
            return new moodle_url('/mod/' . $itemmodule . '/view.php', $args);
        }
    }
    
    
    /**
     * Find the rownum for a userid and assign mod to user for grading url
     *
     * @param stdClass $cm course module object
     * @param in $userid the id of the user whose rownum we are interested in
     *
     * @return int
      */
    function get_rownum($cm, $userid){
        global $COURSE;
        $mod_context = context_module::instance($cm->id);
        $assign = new assign($this->context, $cm, $COURSE);
        $filter = get_user_preferences('assign_filter', '');
        $table = new assign_grading_table($assign, 0, $filter, 0, false);
        $useridlist = $table->get_column_data('userid');
        $rownum = array_search($userid, $useridlist);
        return $rownum;
    }
    
}

/**
*  This class extends Grade User Report
*/
class grade_report_teacher extends grade_report_user {
    
    private $activities;
    private $sections;
    
    // constructor
    public function __construct($courseid, $gpr, $context, $userid) {
        global $DB, $CFG;
        grade_report::__construct($courseid, $gpr, $context);

        $this->showrank        = grade_get_setting($this->courseid, 'report_user_showrank', $CFG->grade_report_user_showrank);
        $this->showpercentage  = grade_get_setting($this->courseid, 'report_user_showpercentage', $CFG->grade_report_user_showpercentage);
        $this->showhiddenitems = grade_get_setting($this->courseid, 'report_user_showhiddenitems', $CFG->grade_report_user_showhiddenitems);
        $this->showtotalsifcontainhidden = array($this->courseid => grade_get_setting($this->courseid, 'report_user_showtotalsifcontainhidden', $CFG->grade_report_user_showtotalsifcontainhidden));

        $this->showgrade       = grade_get_setting($this->courseid, 'report_user_showgrade',       !empty($CFG->grade_report_user_showgrade));
        $this->showrange       = grade_get_setting($this->courseid, 'report_user_showrange',       !empty($CFG->grade_report_user_showrange));
        $this->showfeedback    = grade_get_setting($this->courseid, 'report_user_showfeedback',    !empty($CFG->grade_report_user_showfeedback));
        $this->showweight      = grade_get_setting($this->courseid, 'report_user_showweight',      !empty($CFG->grade_report_user_showweight));
        $this->showlettergrade = grade_get_setting($this->courseid, 'report_user_showlettergrade', !empty($CFG->grade_report_user_showlettergrade));
        $this->showaverage     = grade_get_setting($this->courseid, 'report_user_showaverage',     !empty($CFG->grade_report_user_showaverage));

        // The default grade decimals is 2
        $defaultdecimals = 2;
        if (property_exists($CFG, 'grade_decimalpoints')) {
            $defaultdecimals = $CFG->grade_decimalpoints;
        }
        $this->decimals = grade_get_setting($this->courseid, 'decimalpoints', $defaultdecimals);

        // The default range decimals is 0
        $defaultrangedecimals = 0;
        if (property_exists($CFG, 'grade_report_user_rangedecimals')) {
            $defaultrangedecimals = $CFG->grade_report_user_rangedecimals;
        }
        $this->rangedecimals = grade_get_setting($this->courseid, 'report_user_rangedecimals', $defaultrangedecimals);

        $this->switch = grade_get_setting($this->courseid, 'aggregationposition', $CFG->grade_aggregationposition);

        // Grab the grade_tree for this course
        $this->gtree = new grade_tree_teacher($this->courseid, false, $this->switch, null, !$CFG->enableoutcomes);

        // Determine the number of rows and indentation
        $this->maxdepth = 1;
        //////////$this->inject_rowspans($this->gtree->top_element);
        $this->maxdepth++; // Need to account for the lead column that spans all children
        for ($i = 1; $i <= $this->maxdepth; $i++) {
            $this->evenodd[$i] = 0;
        }

        $this->tabledata = array();

        $this->canviewhidden = has_capability('moodle/grade:viewhidden', context_course::instance($this->courseid));
        
        // Get the user (for full name).
        $this->user = $DB->get_record('user', array('id' => $userid));

        // base url for sorting by first/last name
        $this->baseurl = $CFG->wwwroot.'/grade/report?id='.$courseid.'&amp;userid='.$userid;
        $this->pbarurl = $this->baseurl;

        // no groups on this report - rank is from all course users
        $this->setup_table();

        //optionally calculate grade item averages
        $this->calculate_averages();
//        DebugBreak();
        // get array of activities and filter only Assigns and Labels 
        $this->activities = get_array_of_activities($courseid);
        //$sections = get_all_sections($courseid);
        $this->sections = $DB->get_records('course_sections', array('course' => $courseid), 'section', 'section, id, course, name, sequence, visible');
        $this->activities = array_values(array_filter($this->activities, 
            function($item){
                return in_array($item->mod, array('assign', 'label'));
            }
        ));
        
    }    
    
    /**
    *  fill table for HTML
    */
    function fill_table() {
        $this->fill_table_recursive($this->gtree->top_element);
        return true;
    }

    /**
    * recursive fill table for HTML
    * 
    * @param mixed $element
    */
    private function fill_table_recursive(&$element) {
        global $DB, $CFG;

        $type = $element['type'];
        $depth = $element['depth'];
        $grade_object = $element['object'];
        $eid = $grade_object->id;
        $element['userid'] = $this->user->id;
        $fullname = $this->gtree->get_element_header($element, true, true, true);
        $data = array();
        $hidden = '';
        $excluded = '';
        $class = '';
        $classfeedback = '';

        // If this is a hidden grade category, hide it completely from the user
        if ($type == 'category' && $grade_object->is_hidden() && !$this->canviewhidden && (
                $this->showhiddenitems == GRADE_REPORT_USER_HIDE_HIDDEN ||
                ($this->showhiddenitems == GRADE_REPORT_USER_HIDE_UNTIL && !$grade_object->is_hiddenuntil()))) {
            return false;
        }

        /*if ($type == 'category') {
            $this->evenodd[$depth] = (($this->evenodd[$depth] + 1) % 2);
        }*/
        $alter = ($this->evenodd[$depth] == 0) ? 'even' : 'odd';

        /// Process those items that have scores associated
        if ($type == 'item' or $type == 'categoryitem' or $type == 'courseitem') {
            $header_row = "row_{$eid}_{$this->user->id}";
            $header_cat = "cat_{$grade_object->categoryid}_{$this->user->id}";

            if (! $grade_grade = grade_grade::fetch(array('itemid'=>$grade_object->id,'userid'=>$this->user->id))) {
                $grade_grade = new grade_grade();
                $grade_grade->userid = $this->user->id;
                $grade_grade->itemid = $grade_object->id;
            }

            $grade_grade->load_grade_item();

            /// Hidden Items
            if ($grade_grade->grade_item->is_hidden()) {
                $hidden = ' hidden';
            }

            $hide = false;
            // If this is a hidden grade item, hide it completely from the user.
            if ($grade_grade->is_hidden() && !$this->canviewhidden && (
                    $this->showhiddenitems == GRADE_REPORT_USER_HIDE_HIDDEN ||
                    ($this->showhiddenitems == GRADE_REPORT_USER_HIDE_UNTIL && !$grade_grade->is_hiddenuntil()))) {
                $hide = true;
            } else if (!empty($grade_object->itemmodule) && !empty($grade_object->iteminstance)) {
                // The grade object can be marked visible but still be hidden if...
                //  1) "enablegroupmembersonly" is on and the activity is assigned to a grouping the user is not in.
                //  2) the student cannot see the activity due to conditional access and its set to be hidden entirely.
                $instances = $this->gtree->modinfo->get_instances_of($grade_object->itemmodule);
                if (!empty($instances[$grade_object->iteminstance])) {
                    $cm = $instances[$grade_object->iteminstance];
                    if (!$cm->uservisible) {
                        // Further checks are required to determine whether the activity is entirely hidden or just greyed out.
                        if ($cm->is_user_access_restricted_by_group() || $cm->is_user_access_restricted_by_conditional_access() ||
                                $cm->is_user_access_restricted_by_capability()) {
                            $hide = true;
                        }
                    }
                }
            }

            if (!$hide) {
                /// Excluded Item
                if ($grade_grade->is_excluded()) {
                    $fullname .= ' ['.get_string('excluded', 'grades').']';
                    $excluded = ' excluded';
                }

                /// Other class information
                $class = "$hidden $excluded";
                if ($this->switch) { // alter style based on whether aggregation is first or last
                   $class .= ($type == 'categoryitem' or $type == 'courseitem') ? " ".$alter."d$depth baggt b2b" : " item b1b";
                } else {
                   $class .= ($type == 'categoryitem' or $type == 'courseitem') ? " ".$alter."d$depth baggb" : " item b1b";
                }
                if ($type == 'categoryitem' or $type == 'courseitem') {
                    $header_cat = "cat_{$grade_object->iteminstance}_{$this->user->id}";
                }

                /// Name
                $data['itemname']['content'] = $fullname;
                $data['itemname']['class'] = $class;
                $data['itemname']['colspan'] = ($this->maxdepth - $depth);
                $data['itemname']['celltype'] = 'th';
                $data['itemname']['id'] = $header_row;

                /// Actual Grade
                $gradeval = $grade_grade->finalgrade;

                if ($this->showfeedback) {
                    // Copy $class before appending itemcenter as feedback should not be centered
                    $classfeedback = $class;
                }
                $class .= " itemcenter ";
                if ($this->showweight) {
                    $data['weight']['class'] = $class;
                    $data['weight']['content'] = '-';
                    $data['weight']['headers'] = "$header_cat $header_row weight";
                    // has a weight assigned, might be extra credit
                    if ($grade_object->aggregationcoef > 0 && $type <> 'courseitem') {
                        $data['weight']['content'] = number_format($grade_object->aggregationcoef,2).'%';
                    }
                }

                if ($this->showgrade) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['grade']['class'] = $class.' gradingerror';
                        $data['grade']['content'] = get_string('error');
                    } else if (!empty($CFG->grade_hiddenasdate) and $grade_grade->get_datesubmitted() and !$this->canviewhidden and $grade_grade->is_hidden()
                           and !$grade_grade->grade_item->is_category_item() and !$grade_grade->grade_item->is_course_item()) {
                        // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                        $class .= ' datesubmitted';
                        $data['grade']['class'] = $class;
                        $data['grade']['content'] = get_string('submittedon', 'grades', userdate($grade_grade->get_datesubmitted(), get_string('strftimedatetimeshort')));

                    } else if ($grade_grade->is_hidden()) {
                        $data['grade']['class'] = $class.' hidden';
                        $data['grade']['content'] = '-';
                    } else {
                        $data['grade']['class'] = $class;
                        $gradeval = $this->blank_hidden_total($this->courseid, $grade_grade->grade_item, $gradeval);
                        $data['grade']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true);
                    }
                    $data['grade']['headers'] = "$header_cat $header_row grade";
                }

                // Range
                if ($this->showrange) {
                    $data['range']['class'] = $class;
                    $data['range']['content'] = $grade_grade->grade_item->get_formatted_range(GRADE_DISPLAY_TYPE_REAL, $this->rangedecimals);
                    $data['range']['headers'] = "$header_cat $header_row range";
                }

                // Percentage
                if ($this->showpercentage) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['percentage']['class'] = $class.' gradingerror';
                        $data['percentage']['content'] = get_string('error');
                    } else if ($grade_grade->is_hidden()) {
                        $data['percentage']['class'] = $class.' hidden';
                        $data['percentage']['content'] = '-';
                    } else {
                        $data['percentage']['class'] = $class;
                        $data['percentage']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
                    }
                    $data['percentage']['headers'] = "$header_cat $header_row percentage";
                }

                // Lettergrade
                if ($this->showlettergrade) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['lettergrade']['class'] = $class.' gradingerror';
                        $data['lettergrade']['content'] = get_string('error');
                    } else if ($grade_grade->is_hidden()) {
                        $data['lettergrade']['class'] = $class.' hidden';
                        if (!$this->canviewhidden) {
                            $data['lettergrade']['content'] = '-';
                        } else {
                            $data['lettergrade']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
                        }
                    } else {
                        $data['lettergrade']['class'] = $class;
                        $data['lettergrade']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
                    }
                    $data['lettergrade']['headers'] = "$header_cat $header_row lettergrade";
                }

                // Rank
                if ($this->showrank) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['rank']['class'] = $class.' gradingerror';
                        $data['rank']['content'] = get_string('error');
                        } elseif ($grade_grade->is_hidden()) {
                            $data['rank']['class'] = $class.' hidden';
                            $data['rank']['content'] = '-';
                    } else if (is_null($gradeval)) {
                        // no grade, no rank
                        $data['rank']['class'] = $class;
                        $data['rank']['content'] = '-';

                    } else {
                        /// find the number of users with a higher grade
                        $sql = "SELECT COUNT(DISTINCT(userid))
                                  FROM {grade_grades}
                                 WHERE finalgrade > ?
                                       AND itemid = ?
                                       AND hidden = 0";
                        $rank = $DB->count_records_sql($sql, array($grade_grade->finalgrade, $grade_grade->grade_item->id)) + 1;

                        $data['rank']['class'] = $class;
                        $data['rank']['content'] = "$rank/".$this->get_numusers(false); // total course users
                    }
                    $data['rank']['headers'] = "$header_cat $header_row rank";
                }

                // Average
                if ($this->showaverage) {
                    $data['average']['class'] = $class;
                    if (!empty($this->gtree->items[$eid]->avg)) {
                        $data['average']['content'] = $this->gtree->items[$eid]->avg;
                    } else {
                        $data['average']['content'] = '-';
                    }
                    $data['average']['headers'] = "$header_cat $header_row average";
                }

                // Feedback
                if ($this->showfeedback) {
                    if ($grade_grade->overridden > 0 AND ($type == 'categoryitem' OR $type == 'courseitem')) {
                    $data['feedback']['class'] = $classfeedback.' feedbacktext';
                        $data['feedback']['content'] = get_string('overridden', 'grades').': ' . format_text($grade_grade->feedback, $grade_grade->feedbackformat);
                    } else if (empty($grade_grade->feedback) or (!$this->canviewhidden and $grade_grade->is_hidden())) {
                        $data['feedback']['class'] = $classfeedback.' feedbacktext';
                        $data['feedback']['content'] = '&nbsp;';
                    } else {
                        $data['feedback']['class'] = $classfeedback.' feedbacktext';
                        $data['feedback']['content'] = format_text($grade_grade->feedback, $grade_grade->feedbackformat);
                    }
                    $data['feedback']['headers'] = "$header_cat $header_row feedback";
                }
            }
        }

        /// Category
        /*if ($type == 'category') {
            $data['leader']['class'] = $class.' '.$alter."d$depth b1t b2b b1l";
            if (isset($element['rowspan'])) {
                $data['leader']['rowspan'] = $element['rowspan'];
            }
            if ($this->switch) { // alter style based on whether aggregation is first or last
               $data['itemname']['class'] = $class.' '.$alter."d$depth b1b b1t";
            } else {
               $data['itemname']['class'] = $class.' '.$alter."d$depth b2t";
            }
            $data['itemname']['colspan'] = ($this->maxdepth - $depth + count($this->tablecolumns) - 1);
            $data['itemname']['content'] = $fullname;
            $data['itemname']['celltype'] = 'th';
            $data['itemname']['id'] = "cat_{$grade_object->id}_{$this->user->id}";
        } else*/ 
        if ($type == 'item') {
            /*foreach ($this->sections as $index=>$item) {
                if ($cm->section == $item->id) {
                    // add Label to table
                    $section = array();
                    $section['itemname']['colspan'] = ($this->maxdepth - $depth + count($this->tablecolumns) - 1);
                    $section['itemname']['content'] = $item->name;
                    $section['itemname']['celltype'] = 'th';
                    $section['itemname']['id'] = "section_{$item->id}_{$this->user->id}";
                    $section['itemname']['class'] = $class;
                    $this->tabledata[] = $section;
                    unset($this->sections[$index]);
                    break;
                }
            }*/
            $this->insertSection($cm, $depth, $class);
            foreach ($this->activities as $index=>$item) {
                if ($item->mod == 'assign' && $item->cm == $cm->id) {
                    //$this->tabledata[] = $data; ///////
                    unset($this->activities[$index]);
                    break;
                } else if ($item->mod == 'label') {
                    $this->insertSection($cm, $depth, $class);
                    // add Label to table
                    $label = array(); 
                    $label['itemname']['colspan'] = ($this->maxdepth - $depth + count($this->tablecolumns) - 1);
                    $label['itemname']['content'] = strip_tags($item->content); //$item->name;
                    $label['itemname']['celltype'] = 'th';
                    $label['itemname']['id'] = "label_{$item->cm}_{$this->user->id}";
                    $label['itemname']['class'] = $class;
                    $this->tabledata[] = $label;
                    unset($this->activities[$index]);
                    break;
                }
            }
        }
        /// Add this row to the overall system
        if ($type == 'item')
            $this->tabledata[] = $data;

        /// Recursively iterate through all child elements
        if (isset($element['children'])) {
            foreach ($element['children'] as $key=>$child) {
                $this->fill_table_recursive($element['children'][$key]);
            }
        }
    }
    
    //
    private function insertSection($cm, $depth, $class) {
        foreach ($this->sections as $index=>$item) {
            if ($cm->section == $item->id) {
                // add Label to table
                $section = array();
                $section['itemname']['colspan'] = ($this->maxdepth - $depth + count($this->tablecolumns) - 1);
                $section['itemname']['content'] = $item->name;
                $section['itemname']['celltype'] = 'th';
                $section['itemname']['id'] = "section_{$item->id}_{$this->user->id}";
                $section['itemname']['class'] = $class;
                $this->tabledata[] = $section;
                unset($this->sections[$index]);
                break;
            }
        }
    }
    
    /**
     * Prints or returns the HTML from the flexitable.
     * @param bool $return Whether or not to return the data instead of printing it directly.
     * @return string
     */
    public function print_table($return=false) {
         $maxspan = $this->maxdepth - 1;

        /// Build table structure
        $html = "
            <table cellspacing='0'
                   cellpadding='0'
                   summary='" . s($this->get_lang_string('tablesummary', 'gradereport_user')) . "'
                   class='boxaligncenter generaltable user-grade'>
            <thead>
                <tr>
                    <th id='".$this->tablecolumns[0]."' class=\"header\" colspan='$maxspan'>".$this->tableheaders[0]."</th>\n";

        for ($i = 1; $i < count($this->tableheaders); $i++) {
            $html .= "<th id='".$this->tablecolumns[$i]."' class=\"header\">".$this->tableheaders[$i]."</th>\n";
        }

        $html .= "
                </tr>
            </thead>
            <tbody>\n";

        /// Print out the table data
        for ($i = 0; $i < count($this->tabledata); $i++) {
            $html .= "<tr>\n";
            if (isset($this->tabledata[$i]['leader'])) {
                $rowspan = isset($this->tabledata[$i]['leader']['rowspan']) ? $this->tabledata[$i]['leader']['rowspan'] : 0;
                $class = $this->tabledata[$i]['leader']['class'];
                $html .= "<td class='$class' rowspan='$rowspan'></td>\n";
            }
            for ($j = 0; $j < count($this->tablecolumns); $j++) {
                $name = $this->tablecolumns[$j];
                $class = (isset($this->tabledata[$i][$name]['class'])) ? $this->tabledata[$i][$name]['class'] : '';
                $colspan = (isset($this->tabledata[$i][$name]['colspan'])) ? "colspan='".$this->tabledata[$i][$name]['colspan']."'" : '';
                $content = (isset($this->tabledata[$i][$name]['content'])) ? $this->tabledata[$i][$name]['content'] : null;
                $celltype = (isset($this->tabledata[$i][$name]['celltype'])) ? $this->tabledata[$i][$name]['celltype'] : 'td';
                $id = (isset($this->tabledata[$i][$name]['id'])) ? "id='{$this->tabledata[$i][$name]['id']}'" : '';
                $headers = (isset($this->tabledata[$i][$name]['headers'])) ? "headers='{$this->tabledata[$i][$name]['headers']}'" : '';
                if (isset($content)) {
                    $html .= "<$celltype $id $headers class='$class' $colspan>$content</$celltype>\n";
                }
            }
            $html .= "</tr>\n";
        }

        $html .= "</tbody></table>";

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    
    
}