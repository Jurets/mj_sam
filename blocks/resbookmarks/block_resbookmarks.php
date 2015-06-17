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
class block_resbookmarks extends block_list {

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
        global $USER, $DB;
        
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         =  new stdClass;
        //$this->content->text   = 'The content of our SimpleHTML block!';
        $this->content->items  = array();
        $this->content->icons  = array();

        $blist = $DB->get_records('resbookmarks', array('user_id'=>$USER->id, 'active'=>1));
        foreach ($blist as $index=>$b_item) {
            $this->content->items[] = html_writer::tag('a', $b_item->title, array('href'=>$b_item->url, 'target'=>'_blank'));
        }
        //$this->content->footer = 'Footer here...';
        return $this->content;
    }

}
