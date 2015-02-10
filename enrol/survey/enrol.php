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

class enrolment_plugin_apply {
    /**
     * Prints out the configuration form for this plugin. All we need
     * to provide is the form fields. The <form> tags and submit button will
     * be provided for us by Moodle.
     *
     * @param object $formdata Equal to the global $CFG variable, or if
     *      process_config() returned false, the form contents
     * @return void
     */
    public function config_form( $formdata ){
        return;
    }
 
    /**
     * Process the data from the configuration form.
     *
     * @param object $formdata
     * @return boolean True if configuration was successful, False if the user
     *      should be kicked back to config_form() again.
     */
    public function process_config( $formdata ){
        return true;
    }
}
?>