<?php

namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

use context_user;
use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetCompetenciesByUser extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID')
        ]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_RAW, 'Full course name'),
                'shortname' => new external_value(PARAM_RAW, 'Short course name'),

                'competencies' => new external_multiple_structure(
                    new external_single_structure([
                        'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                        'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                        'competencydesc' => new external_value(PARAM_RAW, 'Competency description', VALUE_OPTIONAL),
                    ]),
                    'Competencies from course',
                    VALUE_OPTIONAL
                ),

                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'userid' => new external_value(PARAM_INT, 'User ID'),
                        'username' => new external_value(PARAM_RAW, 'Username'),
                        'firstname' => new external_value(PARAM_RAW, 'First name'),
                        'lastname' => new external_value(PARAM_RAW, 'Last name'),
                        'email' => new external_value(PARAM_RAW, 'User email'),
                        'competencies' => new external_multiple_structure(
                            new external_single_structure([
                                'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                                'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                                'percentage' => new external_value(PARAM_FLOAT, 'Competency percentage', VALUE_OPTIONAL),
                            ]),
                            'User competencies in this course',
                            VALUE_OPTIONAL
                        ),
                    ]),
                    'Users enrolled in the course',
                    VALUE_OPTIONAL
                ),
            ])
        );
    }

    public static function execute($userid) {
        global $DB, $CFG;

        require_once("$CFG->libdir/enrollib.php");

        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);
        $usercontext = context_user::instance($params['userid']);
        self::validate_context($usercontext);

        $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname, startdate, enddate, timemodified');
        $result = [];

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            self::validate_context($context);

            $roles = get_user_roles($context, $params['userid'], true);
            $rolenames = array_map(fn($r) => $r->shortname, $roles);
            $canviewall = (bool) array_intersect($rolenames, ['editingteacher', 'teacher', 'manager', 'admin']);

            $competencies_data = [];
            $coursecomps = $DB->get_records('competency_coursecomp', ['courseid' => $course->id]);

            foreach ($coursecomps as $comp) {
                $competency = $DB->get_record('competency', ['id' => $comp->competencyid]);
                if ($competency) {
                    $competencies_data[] = [
                        'competencyid' => (int)$competency->id,
                        'competencyname' => $competency->shortname ?? $competency->name ?? 'Sem nome',
                        'competencydesc' => $competency->description ?? '',
                    ];
                }
            }

            $users_data = [];
            $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email');

            foreach ($enrolled_users as $u) {
                if (!$canviewall && $u->id != $params['userid']) {
                    continue;
                }

                $usercompetencies_data = [];
                foreach ($competencies_data as $comp) {
                    $usercomp = $DB->get_record('competency_usercompcourse', [
                        'userid' => $u->id,
                        'courseid' => $course->id,
                        'competencyid' => $comp['competencyid'],
                    ]);

                    $percentage = null;
                    if ($usercomp && isset($usercomp->grade)) {
                        $percentage = round($usercomp->grade * 100, 2);
                    }

                    $usercompetencies_data[] = [
                        'competencyid' => $comp['competencyid'],
                        'competencyname' => $comp['competencyname'],
                        'percentage' => $percentage,
                    ];
                }

                $users_data[] = [
                    'userid' => $u->id,
                    'username' => $u->username,
                    'firstname' => $u->firstname,
                    'lastname' => $u->lastname,
                    'email' => $u->email,
                    'competencies' => $usercompetencies_data,
                ];
            }

            $result[] = [
                'courseid' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'competencies' => $competencies_data,
                'users' => $users_data,
            ];
        }

        return $result;
    }
}
