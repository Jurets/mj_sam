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
                                  
                                  
    $settings->add(new admin_setting_heading('index_video', get_string('index_video', 'videoresource'), get_string('index_video_desc', 'videoresource', $CFG->wwwroot.'/mod/videoresource/video.php?action=index')));
    $settings->add(new admin_setting_heading('add_video', get_string('add_video', 'videoresource'), get_string('add_video_desc', 'videoresource', $CFG->wwwroot.'/mod/videoresource/video.php?action=add')));
    //$settings->add(new admin_setting_heading('sectionconfig', get_string('manage_sections', 'resourcelib'), get_string('manage_sections_desc', 'resourcelib', $CFG->wwwroot.'/mod/resourcelib/sections.php')));
    //$settings->add(new admin_setting_heading('listconfig', get_string('manage_lists', 'resourcelib'), get_string('manage_lists_desc', 'resourcelib', $CFG->wwwroot.'/mod/resourcelib/lists.php')));

}  
?>
