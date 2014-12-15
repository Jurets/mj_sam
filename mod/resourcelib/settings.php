<?php

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
