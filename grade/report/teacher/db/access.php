<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
       'gradereport/teacher:view' => array(
           'riskbitmask' => RISK_PERSONAL,
           'captype' => 'read',
           'contextlevel' => CONTEXT_COURSE,
           'legacy' => array(
               'student' => CAP_PREVENT,
               'teacher' => CAP_ALLOW,
               'editingteacher' => CAP_ALLOW,
               'admin' => CAP_ALLOW
           )
       ),
   );
?>