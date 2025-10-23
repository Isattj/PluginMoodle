<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->libdir/enrollib.php");

use context_user;
use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetUserCourseGrades extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'coursename' => new external_value(PARAM_RAW, 'Course full name'),
                'grades' => new external_multiple_structure(
                    new external_single_structure([
                        'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                        'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Grade percentage', VALUE_OPTIONAL),
                    ]),
                    'Grades per competency',
                    VALUE_OPTIONAL
                ),
            ])
        );
    }

    public static function execute() {
        global $DB, $USER;

        $userid = $USER->id;
        $usercontext = context_user::instance($userid);
        self::validate_context($usercontext);

        $courses = enrol_get_users_courses($userid, true, 'id, fullname');
        $result = [];

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);

            $competencies_data = [];
            $competencymodule = $DB->get_records('competency_coursecomp', ['courseid' => $course->id]);
            foreach ($competencymodule as $comp) {
                $competency = $DB->get_record('competency', ['id' => $comp->competencyid]);
                if ($competency) {
                    $usercomp = $DB->get_record('competency_usercompcourse', [
                        'userid' => $userid,
                        'courseid' => $course->id,
                        'competencyid' => $competency->id
                    ]);

                    $percentage = null;
                    if ($usercomp && isset($usercomp->grade)) {
                        $percentage = round($usercomp->grade * 100, 2);
                    }

                    $competencies_data[] = [
                        'competencyid' => (int)$competency->id,
                        'competencyname' => $competency->shortname ?? $competency->name ?? 'Sem nome',
                        'percentage' => $percentage,
                    ];
                }
            }

            $result[] = [
                'courseid' => $course->id,
                'coursename' => $course->fullname,
                'grades' => $competencies_data,
            ];
        }

        return $result;
    }
}
