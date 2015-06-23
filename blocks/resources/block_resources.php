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
* @package   block_resources
* @category  navigation
* @copyright 2015 Jurets
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('locallib.php');
//require_once($CFG->dirroot.'/blocks/course_overview/locallib.php');

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
        global $OUTPUT, $CFG;
        
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content =  new stdClass;
        $this->content->text = '';
        
        // get courses list in wich logged user was enrolled
        $courses = block_resources_get_all_resources();
        if (!$courses) {
            $this->content->text .= 'There are no courses';
            return $this->content;
        }
        
        // --------- cycle by courses 
        foreach ($courses as $course) {
            
            if ($course->resources || $course->videoresources)
            {   // render corse box
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
                
                // render resources
                foreach($course->resources as $resource) {
                    /// --- Render one resource item                    
                    $this->content->text .= 
                        html_writer::start_div('resource_item')
                      . html_writer::start_div('resource_body')
                      . html_writer::start_div('resource_title')
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
                
                // render videoresources
                foreach($course->videoresources as $videoresource) {
                    /// --- Render one resource item                    
                    $url = new moodle_url("$CFG->wwwroot/mod/videoresource/view.php", array('id'=>$videoresource->id));
                    $this->content->text .= 
                        html_writer::start_div('resource_item')
                      . html_writer::start_div('resource_body')
                      . html_writer::start_div('resource_title')
                      . html_writer::link($url->out(false), $videoresource->name, array(
                        'target'=>'_blank',
                        'class'=>'resourcelink',
                        'data-objectid'=>$videoresource->id, //'data-resourcelibid'=>$cm->id,
                    ));
                    $this->content->text .= html_writer::end_div(); // end of resource_title
                    
                    //echo html_writer::div($resource->description, 'resource_description');
                    $this->content->text .= html_writer::end_div(); // end of Resource body ---
                    
                    $this->content->text .= html_writer::end_div(); // end of Resource Item ---
                }
                
                //$this->content->text .= $OUTPUT->box('', 'flush');
                $this->content->text .= html_writer::end_tag('div');  
                $this->content->text .= $OUTPUT->box_end();
            } 
        }
        
        if (!empty($this->content->text)) {
            $this->content->text .= html_writer::link($CFG->wwwroot.'/blocks/resources/tocsv.php', 'Download list (CSV)');
        } else {
            $this->content->text .= 'There are no resources';
            //$this->content->text .= $OUTPUT->notification(get_string('no_resources', 'resourcelib'), 'redirectmessage');
        }
        
        return $this->content;
    }

}
