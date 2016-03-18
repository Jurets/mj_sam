<?php
defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportassesment', get_string('pluginname', 'report_assesment'), "$CFG->wwwroot/report/assesment/index.php"));
$settings = null;