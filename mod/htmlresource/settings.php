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
    // operations with Html instances                             
    $settings->add(new admin_setting_heading('index_html', get_string('index_html', 'htmlresource'), get_string('index_html_desc', 'htmlresource', $CFG->wwwroot.'/mod/htmlresource/html.php?action=index')));
    $settings->add(new admin_setting_heading('add_html', get_string('add_html', 'htmlresource'), get_string('add_html_desc', 'htmlresource', $CFG->wwwroot.'/mod/htmlresource/html.php?action=add')));
}  
?>
