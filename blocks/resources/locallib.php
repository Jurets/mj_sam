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
 * Helper functions for block_resources
 *
 * @package    block_resources
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
* get all resources!
* 
*/
function block_resources_get_all_resources() {
    global $DB;
    // get courses list in wich logged user was enrolled
    $courses = enrol_get_my_courses();
    
    if (empty($courses)) {
        return false;
    }
    $ids = implode(',',array_keys($courses));

    // --------- cycle by courses 
    foreach ($courses as $key=>$course) {
        if (!isset($courses[$key]->resources)) 
            $courses[$key]->resources = array();
        // get videoresources list from courses and then render it 
        // * link to videoresource = link to course modules
        $courses[$key]->videoresources = get_coursemodules_in_course('videoresource', $course->id);
    }
    
    // get resources list from all courses with AVG rating
    $resources = $DB->get_records_sql('
        SELECT DISTINCT rl.course * 10000 + si.id as id, si.id as section_id, rl.course, r.id as resource_id, r.url, r.title, r.internal_title, r.description, r.author, r.source
               , (SELECT AVG(t.rating) FROM mdl_rating t 
                    LEFT JOIN mdl_resource_section_items si ON si.id = t.itemid WHERE si.resource_item_id = r.id) as avgrate
        FROM mdl_resourcelib rl
             RIGHT JOIN mdl_resourcelib_content rc ON rl.id = rc.resourcelib_id
             RIGHT JOIN mdl_resource_lists l ON rc.instance_id = l.id
             RIGHT JOIN mdl_resource_list_sections ls ON l.id = ls.resource_list_id
             RIGHT JOIN mdl_resource_section_items si ON ls.resource_section_id = si.resource_section_id
             RIGHT JOIN mdl_resource_items r ON r.id = si.resource_item_id
        WHERE rl.course IN ('.$ids.')
        ORDER BY ls.sort_order, si.sort_order'
    );
    foreach ($resources as $key=>$resource) {
        if (!isset($courses[$resource->course]->resources[$resource->resource_id])) {
            $courses[$resource->course]->resources[$resource->resource_id] = $resource;
        }
    }
    return $courses;
}

