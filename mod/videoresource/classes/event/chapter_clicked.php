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

namespace mod_videoresource\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The chapter_clicked event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - PUT INFO HERE
 * }
 *
 * @since     Moodle MOODLEVERSION
 * @copyright 2014 Jurets
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
 
class chapter_clicked extends \core\event\base
{
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videoresource';
    }
 
    public static function get_name() {
        return get_string('eventchapterclicked', 'videoresource');
    }
 
    // get description of logged event (used for the page /report/log/index.php)
    public function get_description() {
        global $DB;
        return 'The user with id '.$this->userid.' click Chapter of VideoResource (id: '.$this->objectid.').';
    }
 
    public function get_url() {
        return new \moodle_url('/mod/videoresource/view.php', array('id' => $this->contextinstanceid));
    }
 
}