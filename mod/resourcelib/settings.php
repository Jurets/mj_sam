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
* Settings for module resource
*
* All the resourcelib specific functions, needed to implement the module
* logic, should go here. Never include this file from your lib.php!
*
* @package    mod_videoresource
* @copyright  2014 Jurets
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_DOWNLOAD,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_DOWNLOAD,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );
                                  
                                  
    $settings->add(new admin_setting_heading('typeconfig', get_string('manage_types', 'resourcelib'), get_string('manage_types_desc', 'resourcelib', $CFG->wwwroot.'/mod/resourcelib/types.php')));
    $settings->add(new admin_setting_heading('itemconfig', get_string('manage_items', 'resourcelib'), get_string('manage_items_desc', 'resourcelib', $CFG->wwwroot.'/mod/resourcelib/items.php')));
    $settings->add(new admin_setting_heading('sectionconfig', get_string('manage_sections', 'resourcelib'), get_string('manage_sections_desc', 'resourcelib', $CFG->wwwroot.'/mod/resourcelib/sections.php')));
    $settings->add(new admin_setting_heading('listconfig', get_string('manage_lists', 'resourcelib'), get_string('manage_lists_desc', 'resourcelib', $CFG->wwwroot.'/mod/resourcelib/lists.php')));

}  
?>
