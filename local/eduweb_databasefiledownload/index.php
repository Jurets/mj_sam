<?php
/**
 *
 * @package    local
 * Author       michael.egli@phz.ch
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/local/eduweb_databasefiledownload/lib.php');

// disable any error oder notice because of streaming application/octet header later
ini_set('display_errors','off');

// modul data is required for this local plugin
if (is_file($CFG->dirroot . '/mod/data/view.php')) {} else {
    print_error('module data was not found');
}

// execute database file download functions
$export = new eduweb_databasefiledownload();
$export->start();
