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
 * Defines the view event.
 *
 * @package    mod_resourcelib
 * @copyright  2014 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_resourcelib\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The resource_viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - PUT INFO HERE
 * }
 *
 * @since     Moodle MOODLEVERSION
 * @copyright 2014 YOUR NAME
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
 
class resource_viewed extends \core\event\base
{
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'resourcelib';
    }
 
    public static function get_name() {
        return get_string('eventresourceviewed', 'resourcelib');
    }
 
    // get description of logged event (used for the page /report/log/index.php)
    public function get_description() {
        global $DB;
        $sql = 'SELECT si.id, r.id as resource_id, r.internal_title
                FROM {resource_section_items} si LEFT JOIN
                     {resource_items} r ON r.id = si.resource_item_id
                WHERE si.id = ?';
        $object = $DB->get_record_sql($sql, array('id'=>$this->objectid));
        return 'The user with id '.$this->userid.' view Resource "'.$object->internal_title.'" (id = '.$this->objectid.').';
    }
 
    public function get_url() {
        return new \moodle_url('/mod/resourcelib/view.php', array('id' => $this->contextinstanceid));
    }
 
    /*public function get_legacy_logdata() {
        // Override if you are migrating an add_to_log() call.
        return array($this->courseid, 'resourcelib', 'view resorce', 'view.php?id=' . $this->objectid, $this->objectid, $this->contextinstanceid);
    }*/
 
    /*public static function get_legacy_eventname() {
        // Override ONLY if you are migrating events_trigger() call.
        return 'MYPLUGIN_OLD_EVENT_NAME';
    }*/
 
    /*protected function get_legacy_eventdata() {
        // Override if you migrating events_trigger() call.
        $data = new \stdClass();
        $data->id = $this->objectid;
        $data->userid = $this->relateduserid;
        return $data;
    }*/
}