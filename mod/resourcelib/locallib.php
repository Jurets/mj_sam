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
* Internal library of functions for module resourcelib
*
* All the resourcelib specific functions, needed to implement the module
* logic, should go here. Never include this file from your lib.php!
*
* @package    mod_resourcelib
* @copyright  2011 Your Name
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/*
* Does something really useful with the passed things
*
* @param array $things
* @return object
*function resourcelib_do_something_useful(array $things) {
*    return new stdClass();
*}
*/

/**
* put your comment there...
* 
*/
function get_resourcetypes() {
    global $DB;
    return $DB->get_records('resource_types');
}

/**
* create resource type
*  
* @param mixed $data
*/
function create_resourcetype($data) {
    global $DB;
    $id = $DB->insert_record('resource_types', $data);
    //$DB->insert_records('resource_types', array($data));
    return $id;
}


function deletete_resourcetype($data) {
    global $DB;
    // Make sure nobody sends bogus record type as parameter.
    if (!property_exists($data, 'id') /*or !property_exists($user, 'name')*/) {
        throw new coding_exception('Invalid $data parameter in deletete_resourcetype() detected');
    }
    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    if (!$type = $DB->get_record('resource_types', array('id' => $data->id))) {
        debugging('Attempt to delete unknown Resource Type.');
        return false;
    }
    return $DB->delete_records('resource_types', array('id' => $data->id));
}