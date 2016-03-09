<?php
global $CFG;
require_once($CFG->dirroot . '/grade/report/user/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

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
                $args = array_merge($args, array('rownum'=>$rownum, 'action'=>'grade'));
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

class grade_report_teacher extends grade_report_user {
    
    public function __construct($courseid, $gpr, $context, $userid, $viewasuser = null) {
        global $CFG;
        parent::__construct($courseid, $gpr, $context, $userid, $viewasuser);
        
        // Grab the grade_tree for this course
        $this->gtree = new grade_tree_teacher($this->courseid, false, $this->switch, null, !$CFG->enableoutcomes);
        $this->inject_rowspans($this->gtree->top_element);
        // no groups on this report - rank is from all course users
        //$this->setup_table();
    }    
    
}