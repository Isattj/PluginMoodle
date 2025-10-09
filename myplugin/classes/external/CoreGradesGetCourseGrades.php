<?php

namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/course/externallib.php");
require_once("$CFG->libdir/gradelib.php");
require_once($CFG->dirroot . '/grade/querylib.php');

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use stdClass;

class CoreGradesGetCourseGrades extends \core_external\external_api {

    public static function execute_returns() {
        return new external_single_structure(
            [
                'name' => new external_value(PARAM_RAW, 'The module name'),
                'grademax' => new external_value(PARAM_FLOAT, 'Maximum grade'),
                'locked' => new external_value(PARAM_BOOL, '0 means not locked, > 1 is a date to lock until'),
                'hidden' => new external_value(PARAM_BOOL, '0 means not hidden, > 1 is a date to hide until'),
                'grades' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'userid' => new external_value(
                                PARAM_INT, 'Student ID'),
                            'grade' => new external_value(
                                PARAM_FLOAT, 'Student grade'),
                            'grademax' => new external_value(
                                PARAM_FLOAT, 'Max student grade'),
                            'hidden' => new external_value(
                                PARAM_BOOL, '0 means not hidden, 1 hidden, > 1 is a date to hide until'),
                            'feedback' => new external_value(
                                PARAM_RAW, 'Feedback from the grader'),
                            'usermodified' => new external_value(
                                PARAM_INT, 'The ID of the last user to modify this student grade'),
                            'datesubmitted' => new external_value(
                                PARAM_RAW, 'A timestamp indicating when the student submitted the activity'),
                            'dategraded' => new external_value(
                                PARAM_RAW, 'A timestamp indicating when the assignment was grades'),
                            'str_grade' => new external_value(
                                PARAM_RAW, 'A string representation of the grade'),
                        ]
                    ), 'user grades', VALUE_OPTIONAL
                ),
            ]
        );
    }

    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'user ID'),
                    'An array of user IDs, leave empty to just retrieve grade item information', VALUE_DEFAULT, []
                ),
            ]
        );
    }

public static function execute($courseid, $userids = []) {
    $params = self::validate_parameters(self::execute_parameters(),
        ['courseid' => $courseid, 'userids' => $userids]);

    $coursecontext = context_course::instance($params['courseid']);
    self::validate_context($coursecontext);

    global $USER;

    if (empty($params['userids'])) {
        require_capability('moodle/grade:viewall', $coursecontext);

        $enrolledusers = get_enrolled_users($coursecontext, '', 0, 'u.id');
        $userids = array_column($enrolledusers, 'id');
    } else {
        if ($params['userids'][0] != $USER->id) {
            throw new \moodle_exception('nopermission', 'error', '', 'Você não pode ver notas de outros alunos.');
        }
        $userids = $params['userids'];
    }

    $retval = grade_get_course_grades($params['courseid'], $userids);

    $result = [
        'scaleid'   => $retval->scaleid ?? 0,
        'name'      => $retval->name ?? '',
        'grademax'  => $retval->grademax ?? 0,
        'gradepass' => $retval->gradepass ?? 0,
        'locked'    => $retval->locked ?? 0,
        'hidden'    => $retval->hidden ?? 0,
        'grades'    => [],
    ];

    foreach ($retval->grades as $userid => $grade) {
        $result['grades'][] = [
            'userid'        => $userid,
            'grade'         => $grade->grade ?? 0,
            'grademax'      => $retval->grademax ?? 0,
            'locked'        => $grade->locked ?? 0,
            'hidden'        => $grade->hidden ?? 0,
            'overridden'    => $grade->overridden ?? 0,
            'feedback'      => $grade->feedback ?? '',
            'feedbackformat'=> $grade->feedbackformat ?? 0,
            'usermodified'  => $grade->usermodified ?? 0,
            'datesubmitted' => date('d/m/Y H:i:s', $grade->datesubmitted) ?? 0,
            'dategraded'    => date('d/m/Y H:i:s', $grade->dategraded) ?? 0,
            'str_grade'     => $grade->str_grade ?? '',
            'str_long_grade'=> $grade->str_long_grade ?? '',
            'str_feedback'  => $grade->str_feedback ?? '',
        ];
    }

    return $result;
}


}