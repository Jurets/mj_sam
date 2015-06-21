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
* This file contains classes used to manage the navigation structures in Moodle
* and was introduced as part of the changes occuring in Moodle 2.0
*
* @since     Moodle 2.0
* @package   block_navigation
* @copyright 2009 Sam Hemelryk
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

/**
* The global navigation tree block class
*
* Used to produce the global navigation block new to Moodle 2.0
*
* @package   block_resbookmarks
* @category  navigation
* @copyright 2009 Sam Hemelryk
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class block_resources extends block_base {

    /**
    * Set the initial properties for the block
    */
    function init() {
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    /**
     * All multiple instances of this block
     * @return bool Returns false
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * Set the applicable formats for this block to all
     * @return array
     */
    function applicable_formats() {
        return array('all' => true);
    }
    
    /**
     * Allow the user to configure a block instance
     * @return bool Returns true
     */
    function instance_allow_config() {
        return true;
    }

    /**
    *  get content
    */
    public function get_content() {
        global $OUTPUT, $DB;
        
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content =  new stdClass;
        $this->content->text = '';
        
        $courses = enrol_get_my_courses();
        foreach ($courses as $course) {
            $sql = '
                SELECT si.id, rl.course, r.id as resource_id, r.url, r.title, r.internal_title, r.description, r.author, r.source
                FROM mdl_resourcelib rl
                     RIGHT JOIN mdl_resourcelib_content rc ON rl.id = rc.resourcelib_id
                     RIGHT JOIN mdl_resource_lists l ON rc.instance_id = l.id
                     RIGHT JOIN mdl_resource_list_sections ls ON l.id = ls.resource_list_id
                     RIGHT JOIN mdl_resource_section_items si ON ls.resource_section_id = si.resource_section_id
                     RIGHT JOIN mdl_resource_items r ON r.id = si.resource_item_id
                WHERE rl.course = ?
                ORDER BY ls.sort_order, si.sort_order';
            $resources = $DB->get_records_sql($sql, array($course->id));
            
            if (!empty($resources)) {
                $this->content->text .= 
                    $OUTPUT->box_start('coursebox', "course-{$course->id}")
                  . html_writer::start_tag('div', array('class' => 'course_title'));
                $attributes = array('title' => $course->fullname);
                if ($course->id > 0) {
                    if (empty($course->visible)) {
                        $attributes['class'] = 'dimmed';
                    }
                    $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                    $coursefullname = format_string(get_course_display_name_for_list($course), true, $course->id);
                    $link = html_writer::link($courseurl, $coursefullname, $attributes);
                    $this->content->text .= $OUTPUT->heading($link, 2, 'title');
                } else {
                    $this->content->text .= $this->output->heading(html_writer::link(
                        new moodle_url('/auth/mnet/jump.php', array('hostid' => $course->hostid, 'wantsurl' => '/course/view.php?id='.$course->remoteid)),
                        format_string($course->shortname, true), $attributes) . ' (' . format_string($course->hostname) . ')', 2, 'title');
                }
            
                foreach($resources as $resource) {
                    /// --- Render one resource item                    
                    $this->content->text .= 
                        html_writer::start_div('resource_item')
                      . html_writer::start_div('resource_body')
                      . html_writer::start_div('resource_title')
                    /*echo html_writer::empty_tag('img', array(
                        'src'=>$resource->icon_path, 
                        'alt'=>$resource->icon_path, 
                        'class'=>'iconsmall', 
                        'style'=>'width: 30px; height: 30px;'));*/
                      . html_writer::link($resource->url, $resource->title, array(
                        'target'=>'_blank',
                        'class'=>'resourcelink',
                        'data-objectid'=>$resource->id, //'data-resourcelibid'=>$cm->id,
                    ));
                    $this->content->text .= html_writer::end_div(); // end of resource_title
                    
                    // render Author and source
                    if (!empty($resource->author)) {
                        $this->content->text .= 
                            html_writer::start_div('resource_metadata')
                          . html_writer::tag('strong', 'Author')
                          . ': ' . $resource->author
                          . html_writer::end_div();
                    }
                    if (!empty($resource->source)) {
                        $this->content->text .= 
                            html_writer::start_div('resource_metadata')
                          . html_writer::tag('strong', 'Source')
                          . ': ' . $resource->source
                          . html_writer::end_div();
                    }
                    //echo html_writer::div($resource->description, 'resource_description');
                    $this->content->text .= html_writer::end_div(); // end of Resource body ---
                    
                    $this->content->text .= html_writer::end_div(); // end of Resource Item ---
                }
                //$this->content->text .= $OUTPUT->box('', 'flush');
                $this->content->text .= html_writer::end_tag('div');  
                $this->content->text .= $OUTPUT->box_end();
            }
        }
        //$this->content->footer = 'Footer here...';
        //$this->content->text .= $OUTPUT
        return $this->content;
    }

}
