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
 * Survey enrolment plugin.
 *
 * @package    enrol_survey
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class enrol_survey_plugin extends enrol_plugin {

	/**
	* Add new instance of enrol plugin with default settings.
	* @param object $course
	* @return int id of new instance
	*/
	public function add_default_instance($course) {
		$fields = array(
		    'status'          => $this->get_config('status'),
		    'roleid'          => $this->get_config('roleid', 0)
		);
		return $this->add_instance($course, $fields);
	}

	public function allow_unenrol(stdClass $instance) {
		// users with unenrol cap may unenrol other users manually manually
		return true;
	}

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * All plugins allowing this must implement 'enrol/xxx:manage' capability
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to change enrol period and status in user_enrolments table
     */
    public function allow_manage(stdClass $instance) {
        return true;
    }

	public function get_newinstance_link($courseid) {
		$context =  context_course::instance($courseid, MUST_EXIST);

		if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/manual:config', $context)) {
			return NULL;
		}
		// multiple instances supported - different roles with different password
		return new moodle_url('/enrol/survey/edit.php', array('courseid'=>$courseid));
	}

    /**
    * Hook before user enrol process
    * 
    * @param stdClass $instance
    * @return string
    */
	public function enrol_page_hook(stdClass $instance) {
		global $CFG, $OUTPUT, $SESSION, $USER, $DB;

		if (isguestuser()) {
			// can not enrol guest!!
			return null;
		}
		if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
			//TODO: maybe we should tell them they are already enrolled, but can not access the course
			//return null;
			return $OUTPUT->notification(get_string('notification', 'enrol_survey'));
		}
		if ($instance->enrolstartdate != 0 and $instance->enrolstartdate > time()) {
			//TODO: inform that we can not enrol yet
			return null;
		}
		if ($instance->enrolenddate != 0 and $instance->enrolenddate < time()) {
			//TODO: inform that enrolment is not possible any more
			return null;
		}
		if ($instance->customint3 > 0) {
			// max enrol limit specified
			$count = $DB->count_records('user_enrolments', array('enrolid'=>$instance->id));
			if ($count >= $instance->customint3) {
				// bad luck, no more self enrolments here
				return $OUTPUT->notification(get_string('maxenrolledreached', 'enrol_self'));
			}
		}
		require_once("$CFG->dirroot/enrol/survey/locallib.php");
        // build survey form for user enrolment
        $form = new enrol_survey_enrol_form(null, $instance);
        //
		$instanceid = optional_param('instance', 0, PARAM_INT);
		if ($instance->id == $instanceid) {
            if ($data = $form->get_data()) {
                $url = new moodle_url("$CFG->wwwroot/enrol/survey/survey.php", array('enrolid'=>$data->instance));
                redirect($url);
			}
		}
		ob_start();
		$form->display(); // show survey form for user enrolment
		$output = ob_get_clean();
		return $OUTPUT->box($output);
	}

    /**
    * Action Icons, which shown in grid on page "course->Users->Enrolment methods"
    * 
    * @param stdClass $instance
    * @return []
    */
	public function get_action_icons(stdClass $instance) {
		global $OUTPUT;

		if ($instance->enrol !== 'survey') {
			throw new coding_exception('invalid enrol instance!');
		}
		$context =  context_course::instance($instance->courseid);

		$icons = array();

		if (has_capability('enrol/manual:config', $context)) {
            $editlink = new moodle_url("/enrol/survey/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core', array('class' => 'iconsmall')));
        }
		if (has_capability('enrol/survey:manage', $context)) {
			$managelink = new moodle_url("/enrol/survey/questions.php", array('enrolid'=>$instance->id));
			$icons[] = $OUTPUT->action_icon($managelink, new pix_icon('i/report', get_string('manage_questions', 'enrol_survey'), 'core', array('class'=>'iconsmall')));
		}
		return $icons;
	}

    /**
    * Action Icons, which shown in grid on page "course->Users->Enrolled Users"
    * 
    * @param course_enrolment_manager $manager
    * @param mixed $ue
    * @return user_enrolment_action[]
    */
	public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
		$actions = array();
		$context = $manager->get_context();
		$instance = $ue->enrolmentinstance;
		$params = $manager->get_moodlepage()->url->params();
		$params['ue'] = $ue->id;
		if ($this->allow_unenrol($instance) && has_capability("enrol/survey:unenrol", $context)) {
			$url = new moodle_url('/enrol/survey/unenroluser.php', $params);
			$actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
		}
		if ($this->allow_manage($instance) && has_capability("enrol/survey:manage", $context)) {
			$url = new moodle_url('/enrol/editenrolment.php', $params);
			$actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class'=>'editenrollink', 'rel'=>$ue->id));
		}
        if ($this->allow_manage($instance) && has_capability("enrol/survey:manage", $context)) {
            $str = get_string('user_answers', 'enrol_survey');
            $url = new moodle_url('/enrol/survey/answers.php', array('action'=>'view', 'ue'=>$ue->id));
            $actions[] = new user_enrolment_action(new pix_icon('t/switch_whole', '', '', array('title'=>$str)), $str, $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
		return $actions;
	}
    
    /**
     * Adds navigation links into course admin block.
     *
     * By defaults looks for manage links only.
     *
     * @param navigation_node $instancesnode
     * @param stdClass $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        global $USER, $DB;
        // usually adds manage users
        if ($instance->enrol !== 'survey') {
             throw new coding_exception('Invalid enrol instance type!');
        }
        
        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/survey:manage', $context)) {
            $menu_label = get_string('pluginname', 'enrol_'.$this->get_name());
            $managelink = new moodle_url('/enrol/survey/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
                
            $node = $instancesnode->parent;
            $survey_node = $node->add($menu_label, $managelink, navigation_node::TYPE_SETTING);
            $survey_node->collapse = true;
            
            $managelink = new moodle_url('/enrol/survey/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $str = get_string('settings');
            $survey_node->add($str, $managelink, navigation_node::TYPE_SETTING, null, null, new pix_icon('t/edit', '', '', array('title'=>$str)));
            
            $managelink = new moodle_url('/enrol/survey/questions.php', array('action'=>'index', 'enrolid'=>$instance->id));
            $str = get_string('manage_questions', 'enrol_survey');
            $survey_node->add($str, $managelink, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', '', '', array('title'=>$str)));
        } else {
            // check: if current user is enrolled by survey
            $enrol_user = $DB->get_record('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$USER->id));
            if ($enrol_user && isset($enrol_user)) {
                $node = $instancesnode->parent->parent;
                $str = get_string('my_answers', 'enrol_survey');
                $link = new moodle_url('/enrol/survey/answers.php', array('action'=>'view', 'ue'=>$enrol_user->id));
                $node->add($str, $link, navigation_node::TYPE_SETTING, null, null, new pix_icon('t/switch_whole', '', '', array('title'=>$str)));
            }
        }
    }

}