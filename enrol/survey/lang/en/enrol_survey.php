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
 * English strings for enroll/survey
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    enroll
 * @subpackage survey
 * @copyright  2015 Jurets
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // The name of your plugin. Displayed on admin menus.
$string['enrol_survey'] = 'enrol_survey';

$string['enrolname'] = 'Survey Enrollment';
$string['pluginname'] = 'Survey Enrollment';
$string['pluginname_desc'] = 'Administrator should be able to set up any number of questions of the different types. Users should be enrolled in the course when they have successfully completed the survey.';

$string['manage_questions'] = 'Manage questions';
$string['addselqtype'] = 'Add selected question type';
$string['enrolusers'] = 'Enrol users';

$string['status'] = 'Allow Course enrol confirmation';
$string['status_desc'] = 'Allow course access of internally enrolled users.';
$string['status_help'] = 'If disabled all existing self enrolments are suspended and new users can not enrol.';

$string['isdeleteanswers'] = 'Delete User Answers';
$string['isdeleteanswers_desc'] = 'Delete User Answers';
$string['isdeleteanswers_help'] = 'Whether delete or not User survey answers if User have unenrolled from course';

$string['editdescription'] = 'Textarea description';

$string['add_question'] = 'Add Question';
$string['edit_question'] = 'Edit Question';
$string['delete_question'] = 'Delete question';

$string['comment'] = 'Comment';
$string['type'] = 'Type';
$string['label'] = 'Label';
$string['is_required'] = 'Is required';
$string['question_text'] = 'Question Text';
$string['question_type'] = 'Question Type';
$string['answer_text'] = 'Answer Text';

$string['required'] = 'Response is required Help with Response is required';
$string['possible_answers'] = 'Possible answers';
$string['user_answers'] = 'User answers';
$string['edit_user_answers'] = 'Edit user answers';
$string['my_answers'] = 'My Survey';

$string['survey:manage'] = 'Manage Survey Enrolment';
$string['survey:unenrol'] = 'Cancel users from course';

$string['notification'] = '<b>You are enrolled successfully to this course</b>. <br/><br/>You will be informed by email as soon as your enrollment has been confirmed. If you want to enroll to other courses, please click "course catalogue" in the top menu.';
$string['missinanswer'] = 'Missing answer';
$string['missing_value'] = 'Missing value! Please enter';
$string['cannotmove'] = 'Can not move this item with ID {$a}';
$string['no_questions'] = 'There is no Questions in this Enrolment';
 
// help buttons
$string['optional_group'] = 'Group label field';
$string['optional_group_help'] = 'Optional parameter. Fill this field if you want to group several questions under a common heading';

$string['optional_name'] = 'Name for question';
$string['optional_name_help'] = 'Optional parameter. Helps to allocate each question for different operations, for example during its removal';

$string['required_question'] = 'Question text';
$string['required_question_help'] = 'Required parameter. This text will be displayed for user as question text in survey form';

$string['required_answers'] = 'Possible answers';
$string['required_answers_help'] = 'Required parameter. Fill list of possible answers. It will be paragraph separated text: each answer must begin from new string.';

$string['groupid'] = 'Group';
$string['groupid_help'] = 'If you select one or several groups, all enrolled users will added to them after enrolment.';

?>